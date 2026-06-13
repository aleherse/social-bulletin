#!/usr/bin/env node
import 'source-map-support/register';
import * as cdk from 'aws-cdk-lib';
import { SocialBulletinStack } from '../lib/socialbulletin-stack';

const app = new cdk.App();

new SocialBulletinStack(app, 'SocialBulletinLive', {
  envName: 'live',
  env: { account: process.env.CDK_DEFAULT_ACCOUNT, region: process.env.CDK_DEFAULT_REGION },
});

new SocialBulletinStack(app, 'SocialBulletinPreview', {
  envName: 'preview',
  env: { account: process.env.CDK_DEFAULT_ACCOUNT, region: process.env.CDK_DEFAULT_REGION },
});
