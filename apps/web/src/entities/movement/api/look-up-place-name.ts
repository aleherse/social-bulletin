import { apiRequest } from '@/shared/api';
import { type GeoPoint, wrapLongitude } from '@/shared/geo';

import type { Area } from '../model/types.ts';

const LOOKUP_TIMEOUT_MS = 10_000;

/** The lookup failed, timed out, or answered with something other than a place name or `null`. */
export class PlaceLookupUnavailable extends Error {
  constructor(options?: ErrorOptions) {
    super('The place lookup is unavailable.', options);
    this.name = 'PlaceLookupUnavailable';
  }
}

/**
 * The name of the place at `point` at the rank `area` names, or `null` when there is none.
 * The point travels only in the POST body (FR-005); an abort through `signal` rejects with the caller's reason.
 */
export async function lookUpPlaceName(
  point: GeoPoint,
  area: Area,
  signal: AbortSignal,
): Promise<string | null> {
  const request = new AbortController();
  const forwardAbort = () => {
    request.abort(signal.reason);
  };
  signal.addEventListener('abort', forwardAbort, { once: true });

  let timer: ReturnType<typeof setTimeout> | undefined;
  const timeout = new Promise<never>((_resolve, reject) => {
    timer = setTimeout(() => {
      request.abort();
      reject(new PlaceLookupUnavailable());
    }, LOOKUP_TIMEOUT_MS);
  });

  try {
    return await Promise.race([postLookup(point, area, request.signal), timeout]);
  } catch (error) {
    if (signal.aborted || error instanceof PlaceLookupUnavailable) {
      throw error;
    }

    throw new PlaceLookupUnavailable({ cause: error });
  } finally {
    clearTimeout(timer);
    signal.removeEventListener('abort', forwardAbort);
  }
}

async function postLookup(
  point: GeoPoint,
  area: Area,
  signal: AbortSignal,
): Promise<string | null> {
  const response = await apiRequest('/api/places/lookup', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ lat: point.lat, lon: wrapLongitude(point.lon), area }),
    signal,
  });

  if (!response.ok) {
    throw new PlaceLookupUnavailable();
  }

  const body = (await response.json()) as { name?: unknown };

  if (typeof body.name === 'string' || body.name === null) {
    return body.name;
  }

  throw new PlaceLookupUnavailable();
}
