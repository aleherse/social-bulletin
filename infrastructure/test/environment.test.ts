import assert from 'node:assert/strict';
import { describe, it } from 'node:test';

import { environments } from '../lib/environment';

const hostnames = (name: string) => {
  const { apiDomainName, frontDomainName, realtimeDomainName } = environments.find(
    (environment) => environment.name === name,
  )!;

  return { api: apiDomainName, app: frontDomainName, realtime: realtimeDomainName };
};

describe('environments', () => {
  it('serves live on the production hostnames', () => {
    assert.deepEqual(hostnames('live'), {
      api: 'api.social.aleherse.com',
      app: 'app.social.aleherse.com',
      realtime: 'realtime.social.aleherse.com',
    });
  });

  it('prefixes every preview hostname with preview.', () => {
    assert.deepEqual(hostnames('preview'), {
      api: 'preview.api.social.aleherse.com',
      app: 'preview.app.social.aleherse.com',
      realtime: 'preview.realtime.social.aleherse.com',
    });
  });
});
