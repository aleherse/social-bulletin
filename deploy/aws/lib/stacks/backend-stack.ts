import { Duration, Stack, StackProps } from 'aws-cdk-lib';
import { Vpc } from 'aws-cdk-lib/aws-ec2';
import { Architecture, Code, Function, FunctionUrlAuthType, LayerVersion, Runtime } from 'aws-cdk-lib/aws-lambda';
import { StringParameter } from 'aws-cdk-lib/aws-ssm';
import { Construct } from 'constructs';

type BackendStackProps = StackProps & { databaseSecretArn: string; environment: string; vpc: Vpc };

export class BackendStack extends Stack {
  readonly functionUrlDomain: string;

  constructor(scope: Construct, id: string, props: BackendStackProps) {
    super(scope, id, props);

    const brefLayerArn = this.node.tryGetContext('brefLayerArn') as string | undefined;
    const api = new Function(this, 'BackendFunction', {
      functionName: `socialbulletin-${props.environment}-backend`,
      runtime: Runtime.PROVIDED_AL2,
      handler: 'public/index.php',
      architecture: Architecture.X86_64,
      code: Code.fromAsset('../../apps/api'),
      layers: brefLayerArn ? [LayerVersion.fromLayerVersionArn(this, 'BrefLayer', brefLayerArn)] : [],
      timeout: Duration.seconds(30),
      vpc: props.vpc,
      environment: {
        APP_ENV: props.environment === 'live' ? 'prod' : 'prod',
        DATABASE_SECRET_ARN: props.databaseSecretArn,
        ORIGIN_SECRET: 'replace-during-deployment',
      },
    });

    const functionUrl = api.addFunctionUrl({ authType: FunctionUrlAuthType.NONE });
    this.functionUrlDomain = functionUrl.url.replace(/^https:\/\//, '').replace(/\/$/, '');

    new StringParameter(this, 'OriginSecretParameter', {
      parameterName: `/socialbulletin/${props.environment}/backend/origin-secret`,
      stringValue: 'replace-during-deployment',
    });
  }
}
