#!/usr/bin/env node
import * as cdk from 'aws-cdk-lib';

import { ApiStack } from '../lib/api-stack';
import { CertificateStack } from '../lib/certificate-stack';
import { DatabaseStack } from '../lib/database-stack';
import { CERTIFICATE_REGION, environments, PROJECT_SLUG, REGION } from '../lib/environment';
import { FrontendStack } from '../lib/frontend-stack';
import { RealtimeStack } from '../lib/realtime-stack';

const app = new cdk.App();

cdk.Tags.of(app).add('project', PROJECT_SLUG);

// Everything deploys to one region except the certificates, which CloudFront
// and the AppSync custom domain only accept from us-east-1 (ADR-0016).
const account = process.env.CDK_DEFAULT_ACCOUNT;
const env: cdk.Environment = { account, region: REGION };
const certificateEnv: cdk.Environment = { account, region: CERTIFICATE_REGION };

// One Aurora cluster serves every environment, each with its own database.
new DatabaseStack(app, `${PROJECT_SLUG}-database`, { env });

for (const environment of environments) {
  const prefix = `${PROJECT_SLUG}-${environment.name}`;

  const certificates = new CertificateStack(app, `${prefix}-certificates`, {
    environment,
    env: certificateEnv,
    crossRegionReferences: true,
  });
  const realtime = new RealtimeStack(app, `${prefix}-realtime`, {
    environment,
    env,
    certificate: certificates.realtime,
    crossRegionReferences: true,
  });
  new ApiStack(app, `${prefix}-api`, {
    environment,
    env,
    certificate: certificates.api,
    realtimeApi: realtime.api,
    crossRegionReferences: true,
  });
  new FrontendStack(app, `${prefix}-frontend`, {
    environment,
    env,
    certificate: certificates.front,
    crossRegionReferences: true,
  });
}
