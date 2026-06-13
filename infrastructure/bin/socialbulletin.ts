#!/usr/bin/env node
import * as cdk from 'aws-cdk-lib';
import { SocialBulletinStack } from '../lib/socialbulletin-stack.js';

const app = new cdk.App();
const targetEnvironment = app.node.tryGetContext('environment') ?? 'preview';
const environments = app.node.tryGetContext('environments') as Record<string, { frontHostname: string; apiHostname: string }>;
const config = environments[targetEnvironment];

if (!config) {
  throw new Error(`Unknown environment "${targetEnvironment}".`);
}

new SocialBulletinStack(app, `SocialBulletin-${targetEnvironment}`, {
  environmentName: targetEnvironment,
  frontHostname: config.frontHostname,
  apiHostname: config.apiHostname,
  env: {
    account: process.env.CDK_DEFAULT_ACCOUNT,
    region: process.env.CDK_DEFAULT_REGION ?? 'eu-west-2',
  },
});
