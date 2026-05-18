import * as cdk from 'aws-cdk-lib';
import * as ec2 from 'aws-cdk-lib/aws-ec2';
import * as rds from 'aws-cdk-lib/aws-rds';
import * as ssm from 'aws-cdk-lib/aws-ssm';
import { Construct } from 'constructs';

import { DeploymentEnvironment, parameterPath } from './environment';

export interface DatabaseStackProps extends cdk.StackProps {
  readonly environment: DeploymentEnvironment;
}

const DATABASE_NAME = 'bulletin';
const DATABASE_USER = 'bulletin';

/**
 * ADR-0014: Aurora Serverless v2 PostgreSQL with one writer, no reader, and
 * cost-optimised capacity. Connection details are published to Parameter
 * Store under the environment-scoped path.
 */
export class DatabaseStack extends cdk.Stack {
  constructor(scope: Construct, id: string, props: DatabaseStackProps) {
    super(scope, id, props);

    const { environment } = props;

    // Cost optimisation: no NAT gateways or VPC endpoints. The cluster sits
    // in public subnets so the (non-VPC) Lambdas can reach it; access is
    // guarded by the generated credentials.
    const vpc = new ec2.Vpc(this, 'Vpc', {
      maxAzs: 2,
      natGateways: 0,
      subnetConfiguration: [{ name: 'public', subnetType: ec2.SubnetType.PUBLIC }],
    });

    const cluster = new rds.DatabaseCluster(this, 'Cluster', {
      engine: rds.DatabaseClusterEngine.auroraPostgres({
        version: rds.AuroraPostgresEngineVersion.of('17.4', '17'),
      }),
      writer: rds.ClusterInstance.serverlessV2('writer', { publiclyAccessible: true }),
      serverlessV2MinCapacity: 0.5,
      serverlessV2MaxCapacity: 1,
      vpc,
      vpcSubnets: { subnetType: ec2.SubnetType.PUBLIC },
      credentials: rds.Credentials.fromGeneratedSecret(DATABASE_USER),
      defaultDatabaseName: DATABASE_NAME,
      storageEncrypted: true,
      removalPolicy:
        environment.name === 'live' ? cdk.RemovalPolicy.RETAIN : cdk.RemovalPolicy.DESTROY,
    });

    cluster.connections.allowDefaultPortFromAnyIpv4('Non-VPC Lambdas connect over the internet');

    const secret = cluster.secret;
    if (secret === undefined) {
      throw new Error('The database cluster is expected to generate a credentials secret.');
    }

    // ADR-0014: the application reads its configuration from Parameter Store.
    // CloudFormation resolves the Secrets Manager reference at deploy time.
    new ssm.StringParameter(this, 'DatabaseUrlParameter', {
      parameterName: parameterPath(environment, 'database-url'),
      stringValue: `postgresql://${DATABASE_USER}:${secret.secretValueFromJson('password').unsafeUnwrap()}@${cluster.clusterEndpoint.hostname}:${cdk.Token.asString(cluster.clusterEndpoint.port)}/${DATABASE_NAME}?serverVersion=17&charset=utf8`,
    });

    new ssm.StringParameter(this, 'DatabaseHostParameter', {
      parameterName: parameterPath(environment, 'database-host'),
      stringValue: cluster.clusterEndpoint.hostname,
    });

    new cdk.CfnOutput(this, 'ClusterEndpoint', { value: cluster.clusterEndpoint.hostname });
  }
}
