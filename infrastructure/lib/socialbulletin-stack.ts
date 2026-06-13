import * as cdk from 'aws-cdk-lib';
import { Construct } from 'constructs';

export type SocialBulletinStackProps = cdk.StackProps & {
  envName: 'live' | 'preview';
};

export class SocialBulletinStack extends cdk.Stack {
  constructor(scope: Construct, id: string, props: SocialBulletinStackProps) {
    super(scope, id, props);

    new cdk.CfnOutput(this, 'EnvironmentName', {
      value: props.envName,
      description: 'Deployment environment identifier',
    });
  }
}
