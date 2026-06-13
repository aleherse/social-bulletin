#!/usr/bin/env node
import * as cdk from 'aws-cdk-lib';
import { SocialBulletinStack } from '../lib/social-bulletin-stack';

const app = new cdk.App();

const sharedProps = {
  hostedZoneId: process.env.HOSTED_ZONE_ID ?? '',
  hostedZoneName: process.env.HOSTED_ZONE_NAME ?? 'example.com',
};

new SocialBulletinStack(app, 'SocialBulletinStack-live', {
  env_name: 'live',
  frontUrl: 'socialbulletin.example.com',
  apiUrl: 'api.socialbulletin.example.com',
  env: {
    account: process.env.CDK_DEFAULT_ACCOUNT,
    region: process.env.CDK_DEFAULT_REGION ?? 'eu-west-1',
  },
  ...sharedProps,
});

new SocialBulletinStack(app, 'SocialBulletinStack-preview', {
  env_name: 'preview',
  frontUrl: 'preview.socialbulletin.example.com',
  apiUrl: 'api.preview.socialbulletin.example.com',
  env: {
    account: process.env.CDK_DEFAULT_ACCOUNT,
    region: process.env.CDK_DEFAULT_REGION ?? 'eu-west-1',
  },
  ...sharedProps,
});
