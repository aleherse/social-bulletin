import assert from 'node:assert/strict';
import { createHmac, generateKeyPairSync, sign } from 'node:crypto';
import { describe, it } from 'node:test';

import { createHandler } from './handler.mjs';

const NOW = 1_800_000_000;
const USER = '0198f2f0-6d2c-7cf0-a2b8-111111111111';

const keys = generateKeyPairSync('rsa', { modulusLength: 2048 });
const otherKeys = generateKeyPairSync('rsa', { modulusLength: 2048 });
const publicPem = keys.publicKey.export({ type: 'spki', format: 'pem' });

const encode = (value) => Buffer.from(JSON.stringify(value)).toString('base64url');

function ticket(claims = {}, { header = { alg: 'RS256', typ: 'JWT' }, privateKey = keys.privateKey } = {}) {
  const payload = { sub: USER, aud: ['realtime'], iat: NOW - 1, exp: NOW + 59, ...claims };
  const signingInput = `${encode(header)}.${encode(payload)}`;

  return `${signingInput}.${sign('RSA-SHA256', Buffer.from(signingInput), privateKey).toString('base64url')}`;
}

const connect = (token) => ({ authorizationToken: token, requestContext: { operation: 'EVENT_CONNECT', channel: null } });
const subscribe = (token, channel) => ({
  authorizationToken: token,
  requestContext: { operation: 'EVENT_SUBSCRIBE', channelNamespaceName: 'users', channel },
});
const publish = (token, channel) => ({
  authorizationToken: token,
  requestContext: { operation: 'EVENT_PUBLISH', channelNamespaceName: 'users', channel },
});

const handler = createHandler({ getPublicKey: async () => publicPem, now: () => NOW });
const authorized = async (event) => (await handler(event)).isAuthorized;

describe('realtime authorizer', () => {
  it('lets a valid ticket connect', async () => {
    assert.equal(await authorized(connect(ticket())), true);
  });

  it('lets a valid ticket subscribe to its own user channel', async () => {
    assert.equal(await authorized(subscribe(ticket(), `/users/${USER}/movements`)), true);
  });

  it('refuses a subscription to another user channel', async () => {
    assert.equal(await authorized(subscribe(ticket(), '/users/0198f2f0-6d2c-7cf0-a2b8-999999999999/movements')), false);
  });

  it('refuses a user id that merely starts like the ticket user', async () => {
    assert.equal(await authorized(subscribe(ticket({ sub: 'abc' }), '/users/abc-evil/movements')), false);
  });

  it('refuses a channel outside the users namespace', async () => {
    assert.equal(await authorized(subscribe(ticket(), `/other/${USER}/movements`)), false);
  });

  it('never lets a browser publish', async () => {
    assert.equal(await authorized(publish(ticket(), `/users/${USER}/movements`)), false);
  });

  it('refuses an unknown operation', async () => {
    assert.equal(await authorized({ authorizationToken: ticket(), requestContext: { operation: 'EVENT_ANYTHING' } }), false);
  });

  it('refuses an expired ticket', async () => {
    assert.equal(await authorized(connect(ticket({ exp: NOW }))), false);
  });

  it('refuses a ticket without the realtime audience', async () => {
    assert.equal(await authorized(connect(ticket({ aud: ['api'] }))), false);
    assert.equal(await authorized(connect(ticket({ aud: undefined }))), false);
  });

  it('accepts the audience as a plain string too', async () => {
    assert.equal(await authorized(connect(ticket({ aud: 'realtime' }))), true);
  });

  it('refuses a ticket without a subject', async () => {
    assert.equal(await authorized(connect(ticket({ sub: undefined }))), false);
    assert.equal(await authorized(connect(ticket({ sub: '' }))), false);
  });

  it('refuses a ticket signed with another key', async () => {
    assert.equal(await authorized(connect(ticket({}, { privateKey: otherKeys.privateKey }))), false);
  });

  it('refuses a ticket whose payload was altered', async () => {
    const [header, , signature] = ticket().split('.');
    const forged = `${header}.${encode({ sub: 'someone-else', aud: ['realtime'], exp: NOW + 59 })}.${signature}`;

    assert.equal(await authorized(connect(forged)), false);
  });

  it('refuses the none algorithm', async () => {
    const unsigned = `${encode({ alg: 'none', typ: 'JWT' })}.${encode({ sub: USER, aud: ['realtime'], exp: NOW + 59 })}.`;

    assert.equal(await authorized(connect(unsigned)), false);
  });

  it('refuses an HMAC token signed with the public key as the secret', async () => {
    const signingInput = `${encode({ alg: 'HS256', typ: 'JWT' })}.${encode({ sub: USER, aud: ['realtime'], exp: NOW + 59 })}`;
    const confused = `${signingInput}.${createHmac('sha256', publicPem).update(signingInput).digest('base64url')}`;

    assert.equal(await authorized(connect(confused)), false);
  });

  it('refuses a token that is not a JWT', async () => {
    for (const token of ['', 'abc', 'a.b', 'a.b.c', undefined, null]) {
      assert.equal(await authorized(connect(token)), false, String(token));
    }
  });

  it('refuses everything, without throwing, when the public key cannot be loaded', async () => {
    const failing = createHandler({
      getPublicKey: async () => {
        throw new Error('ssm unavailable');
      },
      now: () => NOW,
    });

    assert.deepEqual(await failing(connect(ticket())), { isAuthorized: false });
  });

  it('does not let AppSync cache a decision past the ticket', async () => {
    assert.equal((await handler(connect(ticket()))).ttlOverride, 0);
  });
});
