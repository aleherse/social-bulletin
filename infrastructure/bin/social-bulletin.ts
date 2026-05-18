#!/usr/bin/env node
import * as cdk from 'aws-cdk-lib';

import { ApiStack } from '../lib/api-stack';
import { DatabaseStack } from '../lib/database-stack';
import { environments, PROJECT_SLUG } from '../lib/environment';
import { FrontendStack } from '../lib/frontend-stack';

const app = new cdk.App();

cdk.Tags.of(app).add('project', PROJECT_SLUG);

// CloudFront only accepts ACM certificates from us-east-1, so every stack
// deploys there to keep the walking skeleton single-region.
const env: cdk.Environment = {
  account: process.env.CDK_DEFAULT_ACCOUNT,
  region: 'us-east-1',
};

for (const environment of environments) {
  const prefix = `${PROJECT_SLUG}-${environment.name}`;

  new DatabaseStack(app, `${prefix}-database`, { environment, env });
  new ApiStack(app, `${prefix}-api`, { environment, env });
  new FrontendStack(app, `${prefix}-frontend`, { environment, env });
}
