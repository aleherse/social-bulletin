import * as path from 'node:path';

import * as cdk from 'aws-cdk-lib';
import * as acm from 'aws-cdk-lib/aws-certificatemanager';
import * as cloudfront from 'aws-cdk-lib/aws-cloudfront';
import * as origins from 'aws-cdk-lib/aws-cloudfront-origins';
import * as iam from 'aws-cdk-lib/aws-iam';
import * as lambda from 'aws-cdk-lib/aws-lambda';
import * as route53 from 'aws-cdk-lib/aws-route53';
import * as targets from 'aws-cdk-lib/aws-route53-targets';
import { Construct } from 'constructs';

import { DeploymentEnvironment, parameterPath, PROJECT_SLUG } from './environment';
import { lookupHostedZone } from './hosted-zone';

export interface ApiStackProps extends cdk.StackProps {
  readonly environment: DeploymentEnvironment;
}

// Bref publishes its layers from this account; versions are pinned via CDK
// context so upgrades are explicit (https://runtimes.bref.sh for current ids).
const BREF_ACCOUNT = '534081306603';
const DEFAULT_LAYER_VERSIONS: Record<string, string> = {
  'php-84-fpm': '38',
  'php-84': '38',
  console: '133',
};

/**
 * ADR-0014: the API runs as a Bref PHP-FPM Lambda behind CloudFront at the
 * environment's API hostname, and console commands (post-deploy migrations)
 * run through a separate Bref console Lambda.
 */
export class ApiStack extends cdk.Stack {
  constructor(scope: Construct, id: string, props: ApiStackProps) {
    super(scope, id, props);

    const { environment } = props;
    const zone = lookupHostedZone(this);

    const certificate = new acm.Certificate(this, 'Certificate', {
      domainName: environment.apiDomainName,
      validation: acm.CertificateValidation.fromDns(zone),
    });

    // apps/api must be packaged for production (composer install --no-dev)
    // before synth; the deploy workflow does this.
    const apiCode = lambda.Code.fromAsset(path.join(__dirname, '../../apps/api'), {
      exclude: ['var/**', 'tests/**', 'features/**', 'config/jwt/**', '.env.local', '.env.*.local'],
    });

    const functionEnvironment: Record<string, string> = {
      APP_ENV: 'prod',
      // Runtime configuration and secrets are read from Parameter Store
      // under this environment-scoped prefix (ADR-0014).
      SSM_PARAMETER_PREFIX: `/${PROJECT_SLUG}/${environment.name}`,
      DATABASE_URL_PARAMETER: parameterPath(environment, 'database-url'),
    };

    const apiFunction = new lambda.Function(this, 'ApiFunction', {
      runtime: lambda.Runtime.PROVIDED_AL2023,
      handler: 'public/index.php',
      code: apiCode,
      layers: [this.brefLayer('php-84-fpm')],
      memorySize: 1024,
      timeout: cdk.Duration.seconds(28),
      environment: functionEnvironment,
    });

    const consoleFunction = new lambda.Function(this, 'ConsoleFunction', {
      runtime: lambda.Runtime.PROVIDED_AL2023,
      handler: 'bin/console',
      code: apiCode,
      layers: [this.brefLayer('php-84'), this.brefLayer('console')],
      memorySize: 1024,
      timeout: cdk.Duration.minutes(15),
      environment: functionEnvironment,
    });

    const parameterReadAccess = new iam.PolicyStatement({
      actions: ['ssm:GetParameter', 'ssm:GetParameters', 'ssm:GetParametersByPath'],
      resources: [
        cdk.Arn.format(
          {
            service: 'ssm',
            resource: 'parameter',
            resourceName: `${PROJECT_SLUG}/${environment.name}/*`,
          },
          this,
        ),
      ],
    });
    apiFunction.addToRolePolicy(parameterReadAccess);
    consoleFunction.addToRolePolicy(parameterReadAccess);

    const functionUrl = apiFunction.addFunctionUrl({
      authType: lambda.FunctionUrlAuthType.NONE,
    });

    const distribution = new cloudfront.Distribution(this, 'Distribution', {
      defaultBehavior: {
        origin: origins.FunctionUrlOrigin.withOriginAccessControl(functionUrl),
        viewerProtocolPolicy: cloudfront.ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
        allowedMethods: cloudfront.AllowedMethods.ALLOW_ALL,
        cachePolicy: cloudfront.CachePolicy.CACHING_DISABLED,
        originRequestPolicy: cloudfront.OriginRequestPolicy.ALL_VIEWER_EXCEPT_HOST_HEADER,
      },
      domainNames: [environment.apiDomainName],
      certificate,
    });

    new route53.ARecord(this, 'AliasRecord', {
      zone,
      recordName: environment.apiDomainName,
      target: route53.RecordTarget.fromAlias(new targets.CloudFrontTarget(distribution)),
    });

    new cdk.CfnOutput(this, 'ApiFunctionName', { value: apiFunction.functionName });
    new cdk.CfnOutput(this, 'ConsoleFunctionName', { value: consoleFunction.functionName });
  }

  private brefLayer(name: string): lambda.ILayerVersion {
    const version =
      (this.node.tryGetContext(`brefLayer:${name}`) as string | undefined) ??
      DEFAULT_LAYER_VERSIONS[name];

    return lambda.LayerVersion.fromLayerVersionArn(
      this,
      `BrefLayer-${name}`,
      `arn:aws:lambda:${this.region}:${BREF_ACCOUNT}:layer:${name}:${version}`,
    );
  }
}
