import { Stack } from 'aws-cdk-lib';
import { Construct } from 'constructs';

import { BackendStack } from './stacks/backend-stack.js';
import { DatabaseStack } from './stacks/database-stack.js';
import { DeploymentRoleStack } from './stacks/deployment-role-stack.js';
import { FrontendStack } from './stacks/frontend-stack.js';
import { NetworkStack } from './stacks/network-stack.js';

export class SocialBulletinEnvironment extends Construct {
  constructor(scope: Construct, environment: string) {
    super(scope, `socialbulletin-${environment}`);

    const stackProps = { env: this.resolveAwsEnvironment() };
    const network = new NetworkStack(scope, `socialbulletin-${environment}-network`, { ...stackProps, environment });
    const database = new DatabaseStack(scope, `socialbulletin-${environment}-database`, {
      ...stackProps,
      environment,
      vpc: network.vpc,
    });
    const backend = new BackendStack(scope, `socialbulletin-${environment}-backend`, {
      ...stackProps,
      environment,
      databaseSecretArn: database.secretArn,
      vpc: network.vpc,
    });

    new FrontendStack(scope, `socialbulletin-${environment}-frontend`, {
      ...stackProps,
      backendFunctionUrlDomain: backend.functionUrlDomain,
      environment,
    });
    new DeploymentRoleStack(scope, `socialbulletin-${environment}-deployment-role`, { ...stackProps, environment });
  }

  private resolveAwsEnvironment(): Stack['environment'] {
    return {
      account: process.env.CDK_DEFAULT_ACCOUNT,
      region: process.env.CDK_DEFAULT_REGION,
    };
  }
}
