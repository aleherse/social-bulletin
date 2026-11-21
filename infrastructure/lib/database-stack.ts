import * as cdk from 'aws-cdk-lib';
import * as ec2 from 'aws-cdk-lib/aws-ec2';
import * as rds from 'aws-cdk-lib/aws-rds';
import * as secretsmanager from 'aws-cdk-lib/aws-secretsmanager';
import * as ssm from 'aws-cdk-lib/aws-ssm';
import { Construct } from 'constructs';

import { environments, parameterPath, PROJECT_SLUG } from './environment';

const MASTER_USER = 'bulletin';

const databaseName = (environment: string): string => `bulletin_${environment}`;

// Each environment connects as its own role, which owns only its own database.
const roleName = databaseName;

/**
 * ADR-0016: Aurora Serverless v2 PostgreSQL with one writer, no reader, and
 * cost-optimised capacity. One cluster is shared by every environment, each
 * with its own PostgreSQL database (`bulletin_<env>`); the cluster scales to
 * zero capacity (auto-pause) when idle, so the first request after a quiet
 * spell waits for it to resume. Connection details are published to
 * Parameter Store under each environment-scoped path.
 *
 * Each environment connects as its own PostgreSQL role, whose generated
 * password lives in Secrets Manager. CloudFormation cannot create roles or
 * databases, so the one-off bootstrap that does is documented in
 * docs/engineering/infrastructure/database-bootstrap.md.
 */
export class DatabaseStack extends cdk.Stack {
  constructor(scope: Construct, id: string, props: cdk.StackProps) {
    super(scope, id, props);

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
      serverlessV2MinCapacity: 0,
      serverlessV2AutoPauseDuration: cdk.Duration.minutes(5),
      serverlessV2MaxCapacity: 1,
      vpc,
      vpcSubnets: { subnetType: ec2.SubnetType.PUBLIC },
      credentials: rds.Credentials.fromGeneratedSecret(MASTER_USER),
      defaultDatabaseName: databaseName('live'),
      storageEncrypted: true,
      // The cluster holds live data, so it outlives the stack.
      removalPolicy: cdk.RemovalPolicy.RETAIN,
    });

    cluster.connections.allowDefaultPortFromAnyIpv4('Non-VPC Lambdas connect over the internet');

    const secret = cluster.secret;
    if (secret === undefined) {
      throw new Error('The database cluster is expected to generate a credentials secret.');
    }

    // ADR-0016: the application reads its configuration from Parameter Store.
    // CloudFormation resolves the Secrets Manager reference at deploy time.
    for (const environment of environments) {
      const name = databaseName(environment.name);
      const role = roleName(environment.name);

      const credentials = new secretsmanager.Secret(this, `DatabaseSecret-${environment.name}`, {
        secretName: `${PROJECT_SLUG}/${environment.name}/database`,
        generateSecretString: {
          secretStringTemplate: JSON.stringify({ username: role, database: name }),
          generateStringKey: 'password',
          passwordLength: 32,
          excludePunctuation: true,
        },
      });

      new ssm.StringParameter(this, `DatabaseUrlParameter-${environment.name}`, {
        parameterName: parameterPath(environment, 'database-url'),
        stringValue: `postgresql://${role}:${credentials.secretValueFromJson('password').unsafeUnwrap()}@${cluster.clusterEndpoint.hostname}:${cdk.Token.asString(cluster.clusterEndpoint.port)}/${name}?serverVersion=17&charset=utf8`,
      });

      new ssm.StringParameter(this, `DatabaseHostParameter-${environment.name}`, {
        parameterName: parameterPath(environment, 'database-host'),
        stringValue: cluster.clusterEndpoint.hostname,
      });
    }

    new cdk.CfnOutput(this, 'MasterSecretArn', { value: secret.secretArn });
    new cdk.CfnOutput(this, 'ClusterEndpoint', { value: cluster.clusterEndpoint.hostname });
  }
}
