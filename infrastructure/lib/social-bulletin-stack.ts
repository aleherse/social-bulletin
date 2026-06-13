import * as cdk from 'aws-cdk-lib';
import * as s3 from 'aws-cdk-lib/aws-s3';
import * as cloudfront from 'aws-cdk-lib/aws-cloudfront';
import * as origins from 'aws-cdk-lib/aws-cloudfront-origins';
import * as lambda from 'aws-cdk-lib/aws-lambda';
import * as rds from 'aws-cdk-lib/aws-rds';
import * as ec2 from 'aws-cdk-lib/aws-ec2';
import * as acm from 'aws-cdk-lib/aws-certificatemanager';
import * as route53 from 'aws-cdk-lib/aws-route53';
import * as route53Targets from 'aws-cdk-lib/aws-route53-targets';
import * as ssm from 'aws-cdk-lib/aws-ssm';
import { Construct } from 'constructs';

export interface SocialBulletinStackProps extends cdk.StackProps {
  env_name: 'live' | 'preview';
  frontUrl: string;
  apiUrl: string;
  hostedZoneId: string;
  hostedZoneName: string;
}

export class SocialBulletinStack extends cdk.Stack {
  constructor(scope: Construct, id: string, props: SocialBulletinStackProps) {
    super(scope, id, props);

    const { env_name, frontUrl, apiUrl, hostedZoneId, hostedZoneName } = props;
    const ssmPrefix = `/${env_name}/socialbulletin`;

    // VPC for RDS
    const vpc = new ec2.Vpc(this, 'Vpc', { maxAzs: 2 });

    // Aurora Serverless v2
    const dbCluster = new rds.DatabaseCluster(this, 'Database', {
      engine: rds.DatabaseClusterEngine.auroraPostgres({
        version: rds.AuroraPostgresEngineVersion.VER_16_2,
      }),
      serverlessV2MinCapacity: 0.5,
      serverlessV2MaxCapacity: 4,
      writer: rds.ClusterInstance.serverlessV2('writer'),
      vpc,
      defaultDatabaseName: 'socialbulletin',
    });

    // ACM certificate
    const hostedZone = route53.HostedZone.fromHostedZoneAttributes(this, 'Zone', {
      hostedZoneId,
      zoneName: hostedZoneName,
    });

    const certificate = new acm.Certificate(this, 'Certificate', {
      domainName: frontUrl,
      subjectAlternativeNames: [apiUrl],
      validation: acm.CertificateValidation.fromDns(hostedZone),
    });

    // Frontend: private S3 + CloudFront
    const frontendBucket = new s3.Bucket(this, 'FrontendBucket', {
      blockPublicAccess: s3.BlockPublicAccess.BLOCK_ALL,
      removalPolicy: cdk.RemovalPolicy.RETAIN,
    });

    const frontendDistribution = new cloudfront.Distribution(this, 'FrontendCDN', {
      defaultBehavior: {
        origin: origins.S3BucketOrigin.withOriginAccessControl(frontendBucket),
        viewerProtocolPolicy: cloudfront.ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
      },
      domainNames: [frontUrl],
      certificate,
      defaultRootObject: 'index.html',
      errorResponses: [
        { httpStatus: 404, responsePagePath: '/index.html', responseHttpStatus: 200 },
      ],
    });

    new route53.ARecord(this, 'FrontendAlias', {
      zone: hostedZone,
      recordName: frontUrl,
      target: route53.RecordTarget.fromAlias(
        new route53Targets.CloudFrontTarget(frontendDistribution),
      ),
    });

    // API Lambda (Bref PHP-FPM)
    const apiFunction = new lambda.Function(this, 'ApiFunction', {
      runtime: lambda.Runtime.PROVIDED_AL2023,
      handler: 'public/index.php',
      code: lambda.Code.fromAsset('../apps/api', {
        exclude: ['var/', 'tests/', 'spec/', '.git'],
      }),
      layers: [
        lambda.LayerVersion.fromLayerVersionArn(
          this,
          'BrefPhpLayer',
          // Bref PHP 8.3 FPM layer — update ARN for target region
          `arn:aws:lambda:${this.region}:534081306603:layer:php-83-fpm:latest` as string,
        ),
      ],
      environment: {
        APP_ENV: 'prod',
        APP_SECRET: ssm.StringParameter.valueForStringParameter(this, `${ssmPrefix}/app-secret`),
        DATABASE_URL: ssm.StringParameter.valueForStringParameter(
          this,
          `${ssmPrefix}/database-url`,
        ),
        DATABASE_SCHEMA: 'bulletin',
        JWT_SECRET_KEY: ssm.StringParameter.valueForStringParameter(
          this,
          `${ssmPrefix}/jwt-secret-key`,
        ),
        JWT_PUBLIC_KEY: ssm.StringParameter.valueForStringParameter(
          this,
          `${ssmPrefix}/jwt-public-key`,
        ),
        JWT_PASSPHRASE: '',
      },
      timeout: cdk.Duration.seconds(30),
      memorySize: 512,
    });

    dbCluster.connections.allowDefaultPortFrom(apiFunction);

    const apiFunctionUrl = apiFunction.addFunctionUrl({
      authType: lambda.FunctionUrlAuthType.NONE,
    });

    const apiDistribution = new cloudfront.Distribution(this, 'ApiCDN', {
      defaultBehavior: {
        origin: new origins.FunctionUrlOrigin(apiFunctionUrl),
        viewerProtocolPolicy: cloudfront.ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
        allowedMethods: cloudfront.AllowedMethods.ALLOW_ALL,
        cachePolicy: cloudfront.CachePolicy.CACHING_DISABLED,
        originRequestPolicy: cloudfront.OriginRequestPolicy.ALL_VIEWER_EXCEPT_HOST_HEADER,
      },
      domainNames: [apiUrl],
      certificate,
    });

    new route53.ARecord(this, 'ApiAlias', {
      zone: hostedZone,
      recordName: apiUrl,
      target: route53.RecordTarget.fromAlias(
        new route53Targets.CloudFrontTarget(apiDistribution),
      ),
    });

    // Console Lambda (Bref console)
    new lambda.Function(this, 'ConsoleFunction', {
      runtime: lambda.Runtime.PROVIDED_AL2023,
      handler: 'bin/console',
      code: lambda.Code.fromAsset('../apps/api', {
        exclude: ['var/', 'tests/', 'spec/', '.git'],
      }),
      layers: [
        lambda.LayerVersion.fromLayerVersionArn(
          this,
          'BrefConsoleLayer',
          `arn:aws:lambda:${this.region}:534081306603:layer:php-83:latest` as string,
        ),
      ],
      environment: apiFunction.environment,
      timeout: cdk.Duration.minutes(5),
      memorySize: 512,
    });

    // SSM outputs
    new ssm.StringParameter(this, 'FrontendUrl', {
      parameterName: `${ssmPrefix}/frontend-url`,
      stringValue: `https://${frontUrl}`,
    });
  }
}
