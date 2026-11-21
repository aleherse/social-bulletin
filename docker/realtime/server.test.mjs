import assert from 'node:assert/strict';
import { generateKeyPairSync, sign } from 'node:crypto';
import { after, before, describe, it } from 'node:test';

import { WebSocket } from 'ws';

import { createHandler } from '../../infrastructure/lambda/realtime-authorizer/handler.mjs';
import { createRealtimeServer } from './server.mjs';

const USER = 'user-1';
const keys = generateKeyPairSync('rsa', { modulusLength: 2048 });
const publicPem = keys.publicKey.export({ type: 'spki', format: 'pem' });
const encode = (value) => Buffer.from(JSON.stringify(value)).toString('base64url');

function ticket(sub = USER, { exp = Math.floor(Date.now() / 1000) + 60 } = {}) {
  const input = `${encode({ alg: 'RS256', typ: 'JWT' })}.${encode({ sub, aud: ['realtime'], exp })}`;

  return `${input}.${sign('RSA-SHA256', Buffer.from(input), keys.privateKey).toString('base64url')}`;
}

let server;
let base;

before(async () => {
  server = createRealtimeServer({ authorizer: createHandler({ getPublicKey: async () => publicPem }) });
  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  base = `127.0.0.1:${server.address().port}`;
});

after(() => server.close());

const open = (token) =>
  new Promise((resolve, reject) => {
    const header = `header-${encode({ Authorization: token, host: base })}`;
    const socket = new WebSocket(`ws://${base}/event/realtime`, ['aws-appsync-event-ws', header]);
    socket.messages = [];
    socket.on('message', (data) => socket.messages.push(JSON.parse(data.toString())));
    socket.on('open', () => resolve(socket));
    socket.on('unexpected-response', (_, response) => reject(new Error(`HTTP ${response.statusCode}`)));
    socket.on('error', reject);
  });

const next = async (socket, type) => {
  for (let attempt = 0; attempt < 100; attempt++) {
    const index = socket.messages.findIndex((message) => message.type === type);
    if (index >= 0) {
      return socket.messages.splice(index, 1)[0];
    }
    await new Promise((resolve) => setTimeout(resolve, 10));
  }
  throw new Error(`no ${type} message`);
};

const subscribe = async (socket, channel, token = ticket()) => {
  socket.send(JSON.stringify({ type: 'subscribe', id: 'sub-1', channel, authorization: { Authorization: token } }));
};

const publish = (channel, events) =>
  fetch(`http://${base}/event`, {
    method: 'POST',
    headers: { 'content-type': 'application/json', authorization: 'AWS4-HMAC-SHA256 ignored' },
    body: JSON.stringify({ channel, events }),
  });

describe('local AppSync Events', () => {
  it('acknowledges the connection', async () => {
    const socket = await open(ticket());
    socket.send(JSON.stringify({ type: 'connection_init' }));

    assert.equal((await next(socket, 'connection_ack')).type, 'connection_ack');
    socket.close();
  });

  it('refuses a connection without a valid ticket', async () => {
    await assert.rejects(open(ticket(USER, { exp: 1 })), /401/);
  });

  it('delivers a published event to a subscriber of that channel', async () => {
    const socket = await open(ticket());
    await subscribe(socket, `/users/${USER}/movements`);
    await next(socket, 'subscribe_success');

    const response = await publish(`/users/${USER}/movements`, ['{"change":"created"}']);

    assert.equal(response.status, 200);
    assert.deepEqual(await response.json(), { successful: [{ identifier: '0', index: 0 }], failed: [] });
    assert.deepEqual(await next(socket, 'data'), { type: 'data', id: 'sub-1', event: '{"change":"created"}' });
    socket.close();
  });

  it('delivers to wildcard subscribers of the namespace path', async () => {
    const socket = await open(ticket());
    await subscribe(socket, `/users/${USER}/*`);
    await next(socket, 'subscribe_success');

    await publish(`/users/${USER}/movements`, ['one']);

    assert.equal((await next(socket, 'data')).event, 'one');
    socket.close();
  });

  it("refuses a subscription to another user's channel", async () => {
    const socket = await open(ticket());
    await subscribe(socket, '/users/someone-else/movements');

    assert.equal((await next(socket, 'subscribe_error')).errors[0].errorType, 'UnauthorizedException');
    socket.close();
  });

  it('does not deliver after unsubscribing', async () => {
    const socket = await open(ticket());
    await subscribe(socket, `/users/${USER}/movements`);
    await next(socket, 'subscribe_success');
    socket.send(JSON.stringify({ type: 'unsubscribe', id: 'sub-1' }));
    await next(socket, 'unsubscribe_success');

    await publish(`/users/${USER}/movements`, ['late']);
    await new Promise((resolve) => setTimeout(resolve, 50));

    assert.equal(socket.messages.filter((message) => message.type === 'data').length, 0);
    socket.close();
  });

  it('rejects malformed publishes', async () => {
    assert.equal((await publish(`/users/${USER}/movements`, [])).status, 400);
    assert.equal((await publish(`/users/${USER}/movements`, ['1', '2', '3', '4', '5', '6'])).status, 400);
    assert.equal((await publish(`/users/${USER}/movements`, [{ not: 'a string' }])).status, 400);
  });

  it('answers the health check', async () => {
    assert.equal((await fetch(`http://${base}/health`)).status, 200);
  });
});
