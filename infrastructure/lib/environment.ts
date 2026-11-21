export const PROJECT_SLUG = 'social-bulletin';

/** ADR-0016: every stack deploys here, except the certificates. */
export const REGION = 'eu-west-1';

/** CloudFront and AppSync custom domains only accept ACM certificates from this region. */
export const CERTIFICATE_REGION = 'us-east-1';

/**
 * ADR-0016: two isolated deployment environments. `live` serves the ADR-0000
 * production hostnames; `preview` mirrors it with `preview.` in front of each service name.
 */
export interface DeploymentEnvironment {
  readonly name: 'live' | 'preview';
  readonly apiDomainName: string;
  readonly frontDomainName: string;
  readonly realtimeDomainName: string;
}

export const environments: readonly DeploymentEnvironment[] = [
  {
    name: 'live',
    apiDomainName: 'api.social.aleherse.com',
    frontDomainName: 'app.social.aleherse.com',
    realtimeDomainName: 'realtime.social.aleherse.com',
  },
  {
    name: 'preview',
    apiDomainName: 'preview.api.social.aleherse.com',
    frontDomainName: 'preview.app.social.aleherse.com',
    realtimeDomainName: 'preview.realtime.social.aleherse.com',
  },
] as const;

/**
 * ADR-0016: configuration and secrets live in AWS Systems Manager Parameter
 * Store under environment-scoped paths: `/social-bulletin/<env>/<key>`.
 */
export function parameterPath(environment: DeploymentEnvironment, key: string): string {
  return `/${PROJECT_SLUG}/${environment.name}/${key}`;
}
