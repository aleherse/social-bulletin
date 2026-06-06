import { Duration, RemovalPolicy, Stack, StackProps } from 'aws-cdk-lib';
import { Vpc } from 'aws-cdk-lib/aws-ec2';
import { AuroraPostgresEngineVersion, ClusterInstance, DatabaseCluster, DatabaseClusterEngine } from 'aws-cdk-lib/aws-rds';
import { StringParameter } from 'aws-cdk-lib/aws-ssm';
import { Construct } from 'constructs';

type DatabaseStackProps = StackProps & { environment: string; vpc: Vpc };

export class DatabaseStack extends Stack {
  readonly secretArn: string;

  constructor(scope: Construct, id: string, props: DatabaseStackProps) {
    super(scope, id, props);

    const cluster = new DatabaseCluster(this, 'Database', {
      clusterIdentifier: `socialbulletin-${props.environment}-database`,
      engine: DatabaseClusterEngine.auroraPostgres({ version: AuroraPostgresEngineVersion.VER_16_6 }),
      writer: ClusterInstance.serverlessV2('writer'),
      readers: [],
      vpc: props.vpc,
      backup: { retention: Duration.days(props.environment === 'live' ? 14 : 1) },
      deletionProtection: props.environment === 'live',
      removalPolicy: props.environment === 'live' ? RemovalPolicy.RETAIN : RemovalPolicy.DESTROY,
      serverlessV2MinCapacity: props.environment === 'live' ? 0.5 : 0,
      serverlessV2MaxCapacity: props.environment === 'live' ? 4 : 1,
    });

    this.secretArn = cluster.secret?.secretArn ?? '';

    new StringParameter(this, 'DatabaseSecretArnParameter', {
      parameterName: `/socialbulletin/${props.environment}/database/secret-arn`,
      stringValue: this.secretArn,
    });
  }
}
