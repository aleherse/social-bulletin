// AppSync Events Lambda authorizer (ADR-0025): verifies the short-lived ticket issued by
// `POST /api/realtime-tickets` and lets its user connect and subscribe to their own channels.
// A browser never publishes: the API publishes with IAM.
import { createVerify } from 'node:crypto';

const AUDIENCE = 'realtime';
const DENIED = { isAuthorized: false };

function decodeSegment(segment) {
  return JSON.parse(Buffer.from(segment, 'base64url').toString('utf8'));
}

/**
 * Returns the claims of a valid RS256 ticket, or throws.
 *
 * Only RS256 is accepted, so `none` and HMAC tokens signed with the public key are refused.
 */
function verifyTicket(token, publicKey, now) {
  const segments = typeof token === 'string' ? token.split('.') : [];
  if (segments.length !== 3) {
    throw new Error('not a JWT');
  }

  const [header, payload, signature] = segments;
  if (decodeSegment(header).alg !== 'RS256') {
    throw new Error('unsupported algorithm');
  }

  const valid = createVerify('RSA-SHA256')
    .update(`${header}.${payload}`)
    .verify(publicKey, Buffer.from(signature, 'base64url'));
  if (!valid) {
    throw new Error('bad signature');
  }

  const claims = decodeSegment(payload);
  const audience = Array.isArray(claims.aud) ? claims.aud : [claims.aud];
  if (!audience.includes(AUDIENCE)) {
    throw new Error('wrong audience');
  }
  if (typeof claims.sub !== 'string' || claims.sub === '') {
    throw new Error('no subject');
  }
  if (typeof claims.exp !== 'number' || claims.exp <= now) {
    throw new Error('expired');
  }

  return claims;
}

/**
 * @param {{ getPublicKey: () => Promise<string>, now?: () => number }} dependencies
 */
export function createHandler({ getPublicKey, now = () => Math.floor(Date.now() / 1000) }) {
  return async (event) => {
    try {
      const claims = verifyTicket(event.authorizationToken, await getPublicKey(), now());
      const { operation, channel } = event.requestContext;

      const isAuthorized =
        operation === 'EVENT_CONNECT' ||
        (operation === 'EVENT_SUBSCRIBE' &&
          typeof channel === 'string' &&
          `/${channel.replace(/^\//, '')}`.startsWith(`/users/${claims.sub}/`));

      // The ticket expires in a minute; a cached decision must not outlive it.
      return { isAuthorized, ttlOverride: 0 };
    } catch (error) {
      console.error('realtime authorizer denied the request:', error instanceof Error ? error.message : error);

      return DENIED;
    }
  };
}

let publicKey;

// The aws-sdk v3 clients are part of the Lambda Node.js runtime, so they are imported only here.
async function loadPublicKeyFromParameterStore() {
  const { GetParameterCommand, SSMClient } = await import('@aws-sdk/client-ssm');
  const { Parameter } = await new SSMClient({}).send(
    new GetParameterCommand({ Name: process.env.JWT_PUBLIC_KEY_PARAMETER }),
  );

  return Parameter.Value;
}

export const handler = createHandler({
  getPublicKey: () => {
    publicKey ??= loadPublicKeyFromParameterStore().catch((error) => {
      publicKey = undefined;
      throw error;
    });

    return publicKey;
  },
});
