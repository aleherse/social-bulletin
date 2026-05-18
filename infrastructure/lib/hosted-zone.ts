import * as route53 from 'aws-cdk-lib/aws-route53';
import { Construct } from 'constructs';

/**
 * Both environments' hostnames live in one Route53 zone. The zone name is
 * configurable through the `hostedZoneName` CDK context value.
 */
export function lookupHostedZone(scope: Construct): route53.IHostedZone {
  const domainName =
    (scope.node.tryGetContext('hostedZoneName') as string | undefined) ?? 'aleherse.com';

  return route53.HostedZone.fromLookup(scope, 'HostedZone', { domainName });
}
