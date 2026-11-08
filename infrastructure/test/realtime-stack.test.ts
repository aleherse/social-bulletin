import assert from 'node:assert/strict';
import { before, describe, it } from 'node:test';

import * as cdk from 'aws-cdk-lib';
import { Match, Template } from 'aws-cdk-lib/assertions';

import { environments } from '../lib/environment';
import { RealtimeStack } from '../lib/realtime-stack';

const live = environments.find((environment) => environment.name === 'live')!;

describe('RealtimeStack', () => {
  let template: Template;

  before(() => {
    const stack = new RealtimeStack(new cdk.App(), 'realtime', {
      environment: live,
      env: { account: '123456789012', region: 'us-east-1' },
    });
    template = Template.fromStack(stack);
  });

  it('creates one AppSync Events API', () => {
    template.resourceCountIs('AWS::AppSync::Api', 1);
  });

  it('connects and subscribes through the Lambda authorizer but publishes with IAM', () => {
    template.hasResourceProperties('AWS::AppSync::Api', {
      EventConfig: {
        AuthProviders: Match.arrayWith([
          Match.objectLike({ AuthType: 'AWS_LAMBDA' }),
          Match.objectLike({ AuthType: 'AWS_IAM' }),
        ]),
        ConnectionAuthModes: [{ AuthType: 'AWS_LAMBDA' }],
        DefaultSubscribeAuthModes: [{ AuthType: 'AWS_LAMBDA' }],
        DefaultPublishAuthModes: [{ AuthType: 'AWS_IAM' }],
      },
    });
  });

  it('never lets AppSync cache an authorizer decision', () => {
    template.hasResourceProperties('AWS::AppSync::Api', {
      EventConfig: {
        AuthProviders: Match.arrayWith([
          Match.objectLike({
            AuthType: 'AWS_LAMBDA',
            LambdaAuthorizerConfig: Match.objectLike({ AuthorizerResultTtlInSeconds: 0 }),
          }),
        ]),
      },
    });
  });

  it('serves the users channel namespace', () => {
    template.hasResourceProperties('AWS::AppSync::ChannelNamespace', { Name: 'users' });
  });

  it('is reachable on the environment realtime hostname', () => {
    template.hasResourceProperties('AWS::AppSync::DomainName', {
      DomainName: 'realtime.social.aleherse.com',
    });
    template.hasResourceProperties('AWS::Route53::RecordSet', {
      Name: 'realtime.social.aleherse.com.',
      Type: 'CNAME',
    });
  });

  it('lets AppSync invoke the authorizer', () => {
    template.hasResourceProperties('AWS::Lambda::Permission', {
      Action: 'lambda:InvokeFunction',
      Principal: 'appsync.amazonaws.com',
    });
  });

  it('gives the authorizer the public key parameter and nothing wider', () => {
    template.hasResourceProperties('AWS::Lambda::Function', {
      Runtime: 'nodejs22.x',
      Handler: 'handler.handler',
      Environment: { Variables: { JWT_PUBLIC_KEY_PARAMETER: '/social-bulletin/live/jwt-public-key' } },
    });

    const policies = Object.values(template.findResources('AWS::IAM::Policy'));
    const statements = policies.flatMap((policy) => policy.Properties.PolicyDocument.Statement);
    const parameterReads = statements.filter((statement: { Action: string | string[] }) =>
      [statement.Action].flat().some((action) => action.startsWith('ssm:')),
    );

    assert.equal(parameterReads.length, 1);
    assert.deepEqual(parameterReads[0].Action, 'ssm:GetParameter');
    assert.match(JSON.stringify(parameterReads[0].Resource), /parameter\/social-bulletin\/live\/jwt-public-key"/);
  });
});
