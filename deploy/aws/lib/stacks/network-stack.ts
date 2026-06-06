import { Stack, StackProps } from 'aws-cdk-lib';
import { Vpc } from 'aws-cdk-lib/aws-ec2';
import { Construct } from 'constructs';

type NetworkStackProps = StackProps & { environment: string };

export class NetworkStack extends Stack {
  readonly vpc: Vpc;

  constructor(scope: Construct, id: string, props: NetworkStackProps) {
    super(scope, id, props);

    this.vpc = new Vpc(this, 'Vpc', {
      vpcName: `socialbulletin-${props.environment}-network`,
      maxAzs: 2,
      natGateways: props.environment === 'live' ? 1 : 0,
    });
  }
}
