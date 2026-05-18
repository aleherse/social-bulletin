export const PROJECT_SLUG = 'social-bulletin';

/**
 * ADR-0014: two isolated deployment environments. `live` serves the ADR-0000
 * production hostnames; `preview` mirrors it under a `preview.` sub-zone.
 */
export interface DeploymentEnvironment {
  readonly name: 'live' | 'preview';
  readonly apiDomainName: string;
  readonly frontDomainName: string;
}

export const environments: readonly DeploymentEnvironment[] = [
  {
    name: 'live',
    apiDomainName: 'api.social.aleherse.com',
    frontDomainName: 'app.social.aleherse.com',
  },
  {
    name: 'preview',
    apiDomainName: 'api.preview.social.aleherse.com',
    frontDomainName: 'app.preview.social.aleherse.com',
  },
] as const;

/**
 * ADR-0014: configuration and secrets live in AWS Systems Manager Parameter
 * Store under environment-scoped paths: `/social-bulletin/<env>/<key>`.
 */
export function parameterPath(environment: DeploymentEnvironment, key: string): string {
  return `/${PROJECT_SLUG}/${environment.name}/${key}`;
}
