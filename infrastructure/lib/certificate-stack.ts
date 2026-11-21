import * as cdk from 'aws-cdk-lib';
import * as acm from 'aws-cdk-lib/aws-certificatemanager';
import { Construct } from 'constructs';

import { DeploymentEnvironment } from './environment';
import { lookupHostedZone } from './hosted-zone';

export interface CertificateStackProps extends cdk.StackProps {
  readonly environment: DeploymentEnvironment;
}

/**
 * ADR-0016: CloudFront and AppSync custom domains only accept certificates from us-east-1,
 * so the certificates live in a stack of their own while the rest deploys to the main region.
 */
export class CertificateStack extends cdk.Stack {
  readonly api: acm.ICertificate;
  readonly front: acm.ICertificate;
  readonly realtime: acm.ICertificate;

  constructor(scope: Construct, id: string, props: CertificateStackProps) {
    super(scope, id, props);

    const { environment } = props;
    const zone = lookupHostedZone(this);
    const certificate = (name: string, domainName: string): acm.ICertificate =>
      new acm.Certificate(this, name, {
        domainName,
        validation: acm.CertificateValidation.fromDns(zone),
      });

    this.api = certificate('ApiCertificate', environment.apiDomainName);
    this.front = certificate('FrontCertificate', environment.frontDomainName);
    this.realtime = certificate('RealtimeCertificate', environment.realtimeDomainName);
  }
}
