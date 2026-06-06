import { RemovalPolicy, Stack, StackProps } from 'aws-cdk-lib';
import { AllowedMethods, CachePolicy, Distribution, OriginRequestPolicy, ViewerProtocolPolicy } from 'aws-cdk-lib/aws-cloudfront';
import { HttpOrigin, S3BucketOrigin } from 'aws-cdk-lib/aws-cloudfront-origins';
import { BlockPublicAccess, Bucket } from 'aws-cdk-lib/aws-s3';
import { StringParameter } from 'aws-cdk-lib/aws-ssm';
import { Construct } from 'constructs';

type FrontendStackProps = StackProps & { backendFunctionUrlDomain: string; environment: string };

export class FrontendStack extends Stack {
  constructor(scope: Construct, id: string, props: FrontendStackProps) {
    super(scope, id, props);

    const bucket = new Bucket(this, 'FrontendBucket', {
      bucketName: `socialbulletin-${props.environment}-frontend`,
      blockPublicAccess: BlockPublicAccess.BLOCK_ALL,
      removalPolicy: props.environment === 'live' ? RemovalPolicy.RETAIN : RemovalPolicy.DESTROY,
      autoDeleteObjects: props.environment !== 'live',
    });

    const distribution = new Distribution(this, 'Distribution', {
      defaultBehavior: {
        origin: S3BucketOrigin.withOriginAccessControl(bucket),
        viewerProtocolPolicy: ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
      },
      additionalBehaviors: {
        '/auth/*': {
          origin: new HttpOrigin(props.backendFunctionUrlDomain, {
            customHeaders: { 'x-socialbulletin-origin-secret': 'replace-during-deployment' },
          }),
          allowedMethods: AllowedMethods.ALLOW_ALL,
          cachePolicy: CachePolicy.CACHING_DISABLED,
          originRequestPolicy: OriginRequestPolicy.ALL_VIEWER_EXCEPT_HOST_HEADER,
          viewerProtocolPolicy: ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
        },
      },
    });

    new StringParameter(this, 'DistributionDomainParameter', {
      parameterName: `/socialbulletin/${props.environment}/frontend/distribution-domain`,
      stringValue: distribution.distributionDomainName,
    });
  }
}
