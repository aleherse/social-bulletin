import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as cdk from 'aws-cdk-lib';
import * as acm from 'aws-cdk-lib/aws-certificatemanager';
import * as cloudfront from 'aws-cdk-lib/aws-cloudfront';
import * as origins from 'aws-cdk-lib/aws-cloudfront-origins';
import * as ec2 from 'aws-cdk-lib/aws-ec2';
import * as lambda from 'aws-cdk-lib/aws-lambda';
import * as rds from 'aws-cdk-lib/aws-rds';
import * as route53 from 'aws-cdk-lib/aws-route53';
import * as targets from 'aws-cdk-lib/aws-route53-targets';
import * as s3 from 'aws-cdk-lib/aws-s3';
import * as ssm from 'aws-cdk-lib/aws-ssm';
import { Construct } from 'constructs';

const dirname = path.dirname(fileURLToPath(import.meta.url));

export interface SocialBulletinStackProps extends cdk.StackProps {
  environmentName: string;
  frontHostname: string;
  apiHostname: string;
}

export class SocialBulletinStack extends cdk.Stack {
  constructor(scope: Construct, id: string, props: SocialBulletinStackProps) {
    super(scope, id, props);

    const hostedZone = route53.HostedZone.fromLookup(this, 'HostedZone', {
      domainName: 'aleherse.com',
    });

    const certificate = new acm.Certificate(this, 'Certificate', {
      domainName: props.frontHostname,
      subjectAlternativeNames: [props.apiHostname],
      validation: acm.CertificateValidation.fromDns(hostedZone),
    });

    const frontendBucket = new s3.Bucket(this, 'FrontendBucket', {
      bucketName: `socialbulletin-${props.environmentName}-frontend`,
      blockPublicAccess: s3.BlockPublicAccess.BLOCK_ALL,
      enforceSSL: true,
      removalPolicy: props.environmentName === 'live' ? cdk.RemovalPolicy.RETAIN : cdk.RemovalPolicy.DESTROY,
      autoDeleteObjects: props.environmentName !== 'live',
    });

    const vpc = new ec2.Vpc(this, 'Vpc', {
      maxAzs: 2,
      natGateways: 0,
    });

    const database = new rds.DatabaseCluster(this, 'Database', {
      engine: rds.DatabaseClusterEngine.auroraPostgres({
        version: rds.AuroraPostgresEngineVersion.VER_16_4,
      }),
      writer: rds.ClusterInstance.serverlessV2('writer'),
      readers: [],
      serverlessV2MinCapacity: 0.5,
      serverlessV2MaxCapacity: 1,
      vpc,
      defaultDatabaseName: 'socialbulletin',
      removalPolicy: props.environmentName === 'live' ? cdk.RemovalPolicy.SNAPSHOT : cdk.RemovalPolicy.DESTROY,
    });

    const apiFunction = new lambda.Function(this, 'ApiFunction', {
      runtime: lambda.Runtime.PROVIDED_AL2023,
      handler: 'public/index.php',
      code: lambda.Code.fromAsset(path.resolve(dirname, '../../apps/api')),
      memorySize: 1024,
      timeout: cdk.Duration.seconds(28),
      vpc,
      environment: {
        APP_ENV: props.environmentName === 'live' ? 'prod' : 'dev',
        DATABASE_SCHEMA: 'bulletin',
      },
      description: 'Bref PHP-FPM API Lambda placeholder. Attach Bref layer during deployment hardening.',
    });
    database.secret?.grantRead(apiFunction);
    database.connections.allowDefaultPortFrom(apiFunction);

    const consoleFunction = new lambda.Function(this, 'ConsoleFunction', {
      runtime: lambda.Runtime.PROVIDED_AL2023,
      handler: 'bin/console',
      code: lambda.Code.fromAsset(path.resolve(dirname, '../../apps/api')),
      memorySize: 1024,
      timeout: cdk.Duration.minutes(5),
      vpc,
      environment: {
        APP_ENV: props.environmentName === 'live' ? 'prod' : 'dev',
        DATABASE_SCHEMA: 'bulletin',
      },
      description: 'Bref console Lambda placeholder for migrations and console tasks.',
    });
    database.secret?.grantRead(consoleFunction);
    database.connections.allowDefaultPortFrom(consoleFunction);

    const apiUrl = apiFunction.addFunctionUrl({
      authType: lambda.FunctionUrlAuthType.NONE,
    });

    const frontendDistribution = new cloudfront.Distribution(this, 'FrontendDistribution', {
      defaultBehavior: {
        origin: new origins.S3Origin(frontendBucket),
        viewerProtocolPolicy: cloudfront.ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
      },
      domainNames: [props.frontHostname],
      certificate,
    });

    const apiDistribution = new cloudfront.Distribution(this, 'ApiDistribution', {
      defaultBehavior: {
        origin: new origins.HttpOrigin(cdk.Fn.select(2, cdk.Fn.split('/', apiUrl.url))),
        viewerProtocolPolicy: cloudfront.ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
        allowedMethods: cloudfront.AllowedMethods.ALLOW_ALL,
        cachePolicy: cloudfront.CachePolicy.CACHING_DISABLED,
        originRequestPolicy: cloudfront.OriginRequestPolicy.ALL_VIEWER_EXCEPT_HOST_HEADER,
      },
      domainNames: [props.apiHostname],
      certificate,
    });

    new route53.ARecord(this, 'FrontendAlias', {
      zone: hostedZone,
      recordName: props.frontHostname,
      target: route53.RecordTarget.fromAlias(new targets.CloudFrontTarget(frontendDistribution)),
    });

    new route53.ARecord(this, 'ApiAlias', {
      zone: hostedZone,
      recordName: props.apiHostname,
      target: route53.RecordTarget.fromAlias(new targets.CloudFrontTarget(apiDistribution)),
    });

    const parameterPrefix = `/socialbulletin/${props.environmentName}`;
    new ssm.StringParameter(this, 'FrontendUrlParameter', {
      parameterName: `${parameterPrefix}/frontend-url`,
      stringValue: `https://${props.frontHostname}`,
    });
    new ssm.StringParameter(this, 'ApiUrlParameter', {
      parameterName: `${parameterPrefix}/api-url`,
      stringValue: `https://${props.apiHostname}`,
    });
    new ssm.StringParameter(this, 'DatabaseSecretArnParameter', {
      parameterName: `${parameterPrefix}/database-secret-arn`,
      stringValue: database.secret?.secretArn ?? 'unmanaged',
    });
  }
}
