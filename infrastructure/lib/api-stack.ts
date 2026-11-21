import * as path from 'node:path';

import { ConsoleFunction, PhpFpmFunction } from '@bref.sh/constructs';
import * as cdk from 'aws-cdk-lib';
import * as acm from 'aws-cdk-lib/aws-certificatemanager';
import * as appsync from 'aws-cdk-lib/aws-appsync';
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
  readonly certificate: acm.ICertificate;
  readonly realtimeApi: appsync.IEventApi;
}

/**
 * ADR-0016: the API runs as a Bref PHP-FPM Lambda behind CloudFront at the
 * environment's API hostname, and console commands (post-deploy migrations)
 * run through a separate Bref console Lambda.
 */
export class ApiStack extends cdk.Stack {
  constructor(scope: Construct, id: string, props: ApiStackProps) {
    super(scope, id, props);

    const { environment, certificate } = props;
    const zone = lookupHostedZone(this);

    // apps/api must be packaged for production (composer install --no-dev)
    // before synth; the deploy workflow does this.
    const apiCode = lambda.Code.fromAsset(path.join(__dirname, '../../apps/api'), {
      exclude: ['var/**', 'tests/**', 'features/**', 'config/jwt/**', '.env.local', '.env.*.local'],
    });

    // Secrets are `bref-ssm:` references that the Bref secrets loader resolves from
    // Parameter Store when the function starts (ADR-0016); only their paths are stored here.
    const secret = (key: string): string => `bref-ssm:${parameterPath(environment, key)}`;

    const functionEnvironment: Record<string, string> = {
      APP_ENV: 'prod',
      APP_SECRET: secret('app-secret'),
      DATABASE_URL: secret('database-url'),
      // The key pair is read as key text rather than file paths, so nothing is packaged with the code.
      JWT_SECRET_KEY: secret('jwt-private-key'),
      JWT_PUBLIC_KEY: secret('jwt-public-key'),
      JWT_PASSPHRASE: secret('jwt-passphrase'),
      DEFAULT_URI: `https://${environment.apiDomainName}`,
      CORS_ALLOW_ORIGIN: `^https://${environment.frontDomainName.replaceAll('.', '\\.')}$`,
      // ADR-0024: the API publishes to AppSync Events and hands browsers the websocket endpoint.
      REALTIME_PUBLISH_URL: `https://${environment.realtimeDomainName}/event`,
      REALTIME_WS_URL: `wss://${environment.realtimeDomainName}/event/realtime`,
    };

    const apiFunction = new PhpFpmFunction(this, 'ApiFunction', {
      phpVersion: '8.5',
      handler: 'public/index.php',
      code: apiCode,
      memorySize: 1024,
      timeout: cdk.Duration.seconds(28),
      environment: functionEnvironment,
    });

    const consoleFunction = new ConsoleFunction(this, 'ConsoleFunction', {
      phpVersion: '8.5',
      handler: 'bin/console',
      code: apiCode,
      memorySize: 1024,
      timeout: cdk.Duration.minutes(15),
      environment: functionEnvironment,
    });

    // The secrets loader reads the `bref-ssm:` parameters with GetParameters.
    const parameterReadAccess = new iam.PolicyStatement({
      actions: ['ssm:GetParameters'],
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

    // Publishing is the only way in; browsers get a ticket, never publish rights (ADR-0024).
    props.realtimeApi.grantPublish(apiFunction);
    props.realtimeApi.grantPublish(consoleFunction);

    const functionUrl = apiFunction.addFunctionUrl({
      authType: lambda.FunctionUrlAuthType.AWS_IAM,
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
}
