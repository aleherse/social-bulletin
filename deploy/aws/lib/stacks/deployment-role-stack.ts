import { Stack, StackProps } from 'aws-cdk-lib';
import { FederatedPrincipal, ManagedPolicy, Role } from 'aws-cdk-lib/aws-iam';
import { Construct } from 'constructs';

type DeploymentRoleStackProps = StackProps & { environment: string };

export class DeploymentRoleStack extends Stack {
  constructor(scope: Construct, id: string, props: DeploymentRoleStackProps) {
    super(scope, id, props);

    new Role(this, 'DeploymentRole', {
      roleName: `socialbulletin-${props.environment}-deployment`,
      assumedBy: new FederatedPrincipal(
        `arn:aws:iam::${this.account}:oidc-provider/token.actions.githubusercontent.com`,
        {
          StringEquals: { 'token.actions.githubusercontent.com:aud': 'sts.amazonaws.com' },
          StringLike: { 'token.actions.githubusercontent.com:sub': 'repo:*/*:*' },
        },
        'sts:AssumeRoleWithWebIdentity',
      ),
      managedPolicies: [ManagedPolicy.fromAwsManagedPolicyName('AdministratorAccess')],
    });
  }
}
