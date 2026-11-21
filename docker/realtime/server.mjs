// Local stand-in for AppSync Events (ADR-0025). It serves the same two surfaces the application uses:
// `POST /event` to publish and the `/event/realtime` websocket to subscribe, speaking the AppSync Events
// protocol. Publishing ignores the SigV4 signature; connecting and subscribing run through the production
// Lambda authorizer, so a ticket is checked exactly as it is on AWS.
import { readFile } from 'node:fs/promises';
import { createServer } from 'node:http';
import { pathToFileURL } from 'node:url';

import { WebSocketServer } from 'ws';

const SUBPROTOCOL = 'aws-appsync-event-ws';
const KEEP_ALIVE_MS = 60_000;
const MAX_BATCH = 5;

/** `/users/a/*` matches `/users/a/movements`; any other subscription matches its own channel only. */
function matches(subscribed, published) {
  return subscribed.endsWith('/*')
    ? published.startsWith(subscribed.slice(0, -1)) && published.length > subscribed.length - 1
    : subscribed === published;
}

const normalise = (channel) => `/${String(channel).replace(/^\//, '')}`;

function readBody(request) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    request.on('data', (chunk) => chunks.push(chunk));
    request.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    request.on('error', reject);
  });
}

/** The ticket travels in the `header-<base64url JSON>` subprotocol, as AppSync clients send it. */
function ticketFrom(request) {
  const offered = String(request.headers['sec-websocket-protocol'] ?? '')
    .split(',')
    .map((protocol) => protocol.trim());
  const header = offered.find((protocol) => protocol.startsWith('header-'));
  if (!offered.includes(SUBPROTOCOL) || header === undefined) {
    return undefined;
  }

  try {
    return JSON.parse(Buffer.from(header.slice('header-'.length), 'base64url').toString('utf8')).Authorization;
  } catch {
    return undefined;
  }
}

/**
 * @param {{ authorizer: (event: object) => Promise<{ isAuthorized: boolean }>, keepAliveMs?: number }} options
 */
export function createRealtimeServer({ authorizer, keepAliveMs = KEEP_ALIVE_MS }) {
  /** @type {Set<{ socket: import('ws').WebSocket, id: string, channel: string }>} */
  const subscriptions = new Set();

  const authorize = async (token, operation, channel = null) => {
    const normalised = channel === null ? null : normalise(channel);

    return (
      await authorizer({
        authorizationToken: token,
        requestContext: {
          operation,
          channel: normalised,
          channelNamespaceName: normalised?.split('/')[1] ?? null,
        },
      })
    ).isAuthorized;
  };

  const publish = async (request, response) => {
    const send = (status, body) => {
      response.writeHead(status, { 'content-type': 'application/json' }).end(JSON.stringify(body));
    };

    let body;
    try {
      body = JSON.parse(await readBody(request));
    } catch {
      return send(400, { errors: [{ errorType: 'BadRequestException', message: 'The body is not JSON.' }] });
    }

    const { channel, events } = body ?? {};
    const valid =
      typeof channel === 'string' &&
      Array.isArray(events) &&
      events.length >= 1 &&
      events.length <= MAX_BATCH &&
      events.every((event) => typeof event === 'string');
    if (!valid) {
      return send(400, { errors: [{ errorType: 'BadRequestException', message: 'Invalid channel or events.' }] });
    }

    const published = normalise(channel);
    for (const subscription of subscriptions) {
      if (matches(subscription.channel, published)) {
        for (const event of events) {
          subscription.socket.send(JSON.stringify({ type: 'data', id: subscription.id, event }));
        }
      }
    }

    return send(200, { successful: events.map((_, index) => ({ identifier: String(index), index })), failed: [] });
  };

  const server = createServer((request, response) => {
    const { pathname } = new URL(request.url ?? '/', 'http://localhost');

    if (request.method === 'GET' && pathname === '/health') {
      response.writeHead(200).end('ok');
    } else if (request.method === 'POST' && pathname === '/event') {
      publish(request, response).catch(() => response.writeHead(500).end());
    } else {
      response.writeHead(404).end();
    }
  });

  const sockets = new WebSocketServer({
    noServer: true,
    handleProtocols: (protocols) => (protocols.has(SUBPROTOCOL) ? SUBPROTOCOL : false),
  });

  sockets.on('connection', (socket) => {
    const send = (message) => socket.send(JSON.stringify(message));
    const keepAlive = setInterval(() => send({ type: 'ka' }), keepAliveMs);
    keepAlive.unref();

    socket.on('close', () => {
      clearInterval(keepAlive);
      for (const subscription of subscriptions) {
        if (subscription.socket === socket) {
          subscriptions.delete(subscription);
        }
      }
    });

    socket.on('message', async (data) => {
      let message;
      try {
        message = JSON.parse(data.toString());
      } catch {
        return;
      }

      if (message.type === 'connection_init') {
        send({ type: 'connection_ack', connectionTimeoutMs: 300_000 });
      } else if (message.type === 'subscribe') {
        const token = message.authorization?.Authorization;
        let allowed = false;
        try {
          allowed = typeof message.channel === 'string' && (await authorize(token, 'EVENT_SUBSCRIBE', message.channel));
        } catch {
          allowed = false;
        }

        if (allowed) {
          subscriptions.add({ socket, id: message.id, channel: normalise(message.channel) });
          send({ type: 'subscribe_success', id: message.id });
        } else {
          send({
            type: 'subscribe_error',
            id: message.id,
            errors: [{ errorType: 'UnauthorizedException', message: 'You are not authorized to subscribe.' }],
          });
        }
      } else if (message.type === 'unsubscribe') {
        for (const subscription of subscriptions) {
          if (subscription.socket === socket && subscription.id === message.id) {
            subscriptions.delete(subscription);
          }
        }
        send({ type: 'unsubscribe_success', id: message.id });
      }
    });
  });

  server.on('upgrade', async (request, socket, head) => {
    const { pathname } = new URL(request.url ?? '/', 'http://localhost');
    const token = ticketFrom(request);

    let allowed = false;
    try {
      allowed = pathname === '/event/realtime' && token !== undefined && (await authorize(token, 'EVENT_CONNECT'));
    } catch {
      allowed = false;
    }

    if (!allowed) {
      socket.end('HTTP/1.1 401 Unauthorized\r\nConnection: close\r\n\r\n');

      return;
    }

    sockets.handleUpgrade(request, socket, head, (webSocket) => sockets.emit('connection', webSocket, request));
  });

  server.on('close', () => sockets.close());

  return server;
}

if (import.meta.url === pathToFileURL(process.argv[1] ?? '').href) {
  const { createHandler } = await import(
    process.env.REALTIME_AUTHORIZER ?? '/app/infrastructure/lambda/realtime-authorizer/handler.mjs'
  );
  const publicKeyFile = process.env.JWT_PUBLIC_KEY_FILE ?? '/app/apps/api/config/jwt/public.pem';

  // Read on every call, so `make deps` generating the key pair after the container started is enough.
  const authorizer = createHandler({ getPublicKey: () => readFile(publicKeyFile, 'utf8') });
  const port = Number(process.env.PORT ?? 8080);

  createRealtimeServer({ authorizer }).listen(port, '0.0.0.0', () => {
    console.log(`Local AppSync Events listening on :${port}`);
  });
}
