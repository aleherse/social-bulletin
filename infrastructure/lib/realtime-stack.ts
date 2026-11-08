import * as path from 'node:path';

import * as cdk from 'aws-cdk-lib';
import * as acm from 'aws-cdk-lib/aws-certificatemanager';
import * as appsync from 'aws-cdk-lib/aws-appsync';
import * as iam from 'aws-cdk-lib/aws-iam';
import * as lambda from 'aws-cdk-lib/aws-lambda';
import * as route53 from 'aws-cdk-lib/aws-route53';
import { Construct } from 'constructs';

import { DeploymentEnvironment, parameterPath, PROJECT_SLUG } from './environment';
import { lookupHostedZone } from './hosted-zone';

export interface RealtimeStackProps extends cdk.StackProps {
  readonly environment: DeploymentEnvironment;
}

/**
 * ADR-0024: an AppSync Events API pushes notifications to browsers at the environment's realtime hostname.
 *
 * Browsers connect and subscribe with the ticket `POST /api/realtime-tickets` issues, which a Lambda
 * authorizer verifies; only the API publishes, signed with IAM.
 */
export class RealtimeStack extends cdk.Stack {
  readonly api: appsync.EventApi;

  constructor(scope: Construct, id: string, props: RealtimeStackProps) {
    super(scope, id, props);

    const { environment } = props;
    const zone = lookupHostedZone(this);

    // AppSync custom domains, like CloudFront, take their certificate from us-east-1.
    const certificate = new acm.Certificate(this, 'Certificate', {
      domainName: environment.realtimeDomainName,
      validation: acm.CertificateValidation.fromDns(zone),
    });

    // The tickets are signed with the API's JWT key pair; the authorizer only needs the public half.
    const jwtPublicKeyParameter = parameterPath(environment, 'jwt-public-key');

    const authorizer = new lambda.Function(this, 'AuthorizerFunction', {
      runtime: lambda.Runtime.NODEJS_22_X,
      handler: 'handler.handler',
      code: lambda.Code.fromAsset(path.join(__dirname, '../lambda/realtime-authorizer'), {
        exclude: ['*.test.mjs'],
      }),
      memorySize: 128,
      timeout: cdk.Duration.seconds(5),
      environment: { JWT_PUBLIC_KEY_PARAMETER: jwtPublicKeyParameter },
    });
    authorizer.addToRolePolicy(
      new iam.PolicyStatement({
        actions: ['ssm:GetParameter'],
        resources: [
          cdk.Arn.format(
            { service: 'ssm', resource: 'parameter', resourceName: jwtPublicKeyParameter.slice(1) },
            this,
          ),
        ],
      }),
    );

    this.api = new appsync.EventApi(this, 'Api', {
      apiName: `${PROJECT_SLUG}-${environment.name}-realtime`,
      authorizationConfig: {
        authProviders: [
          {
            authorizationType: appsync.AppSyncAuthorizationType.LAMBDA,
            // The ticket outlives a minute at most; a decision must never be cached past it.
            lambdaAuthorizerConfig: { handler: authorizer, resultsCacheTtl: cdk.Duration.seconds(0) },
          },
          { authorizationType: appsync.AppSyncAuthorizationType.IAM },
        ],
        connectionAuthModeTypes: [appsync.AppSyncAuthorizationType.LAMBDA],
        defaultSubscribeAuthModeTypes: [appsync.AppSyncAuthorizationType.LAMBDA],
        defaultPublishAuthModeTypes: [appsync.AppSyncAuthorizationType.IAM],
      },
      domainName: { domainName: environment.realtimeDomainName, certificate },
    });

    // Channels are /users/<user id>/<topic>.
    this.api.addChannelNamespace('users');

    new route53.CnameRecord(this, 'AliasRecord', {
      zone,
      recordName: environment.realtimeDomainName,
      domainName: this.api.appSyncDomainName,
    });

    new cdk.CfnOutput(this, 'PublishUrl', { value: this.api.customHttpEndpoint });
    new cdk.CfnOutput(this, 'RealtimeUrl', { value: this.api.customRealtimeEndpoint });
  }
}
