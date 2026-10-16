import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { lookUpPlaceName, PlaceLookupUnavailable } from './look-up-place-name.ts';

const HUELIN = { lat: 36.7033, lon: -4.4366 };

function jsonResponse(status: number, body: unknown): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

describe('lookUpPlaceName', () => {
  const fetchMock = vi.fn<typeof fetch>();

  beforeEach(() => {
    fetchMock.mockReset();
    vi.stubGlobal('fetch', fetchMock);
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
  });

  function lookUp(point = HUELIN, signal = new AbortController().signal) {
    return lookUpPlaceName(point, 'neighborhood', signal);
  }

  it('posts the point in the body only, with credentials', async () => {
    fetchMock.mockResolvedValue(jsonResponse(200, { name: 'Huelin' }));

    await lookUp();

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, init] = fetchMock.mock.calls[0] ?? [];
    expect(String(url)).toMatch(/\/api\/places\/lookup$/);
    expect(String(url)).not.toContain('36.7033');
    expect(String(url)).not.toContain('-4.4366');
    expect(init?.method).toBe('POST');
    expect(init?.credentials).toBe('include');
    expect(new Headers(init?.headers).get('Content-Type')).toBe('application/json');
    expect(JSON.parse(String(init?.body))).toEqual({
      lat: 36.7033,
      lon: -4.4366,
      area: 'neighborhood',
    });
  });

  it('sends a longitude wrapped into range', async () => {
    fetchMock.mockResolvedValue(jsonResponse(200, { name: 'Huelin' }));

    await lookUp({ lat: 36.7033, lon: 355.5634 });

    const body = JSON.parse(String(fetchMock.mock.calls[0]?.[1]?.body)) as { lon: number };
    expect(body.lon).toBeCloseTo(-4.4366, 10);
  });

  it('resolves the name of the place found', async () => {
    fetchMock.mockResolvedValue(jsonResponse(200, { name: 'Huelin' }));

    await expect(lookUp()).resolves.toBe('Huelin');
  });

  it('resolves null when no place is at that level', async () => {
    fetchMock.mockResolvedValue(jsonResponse(200, { name: null }));

    await expect(lookUp()).resolves.toBeNull();
  });

  it.each([
    ['a 503', () => Promise.resolve(jsonResponse(503, {}))],
    ['a 422', () => Promise.resolve(jsonResponse(422, { errors: [] }))],
    ['a network failure', () => Promise.reject(new TypeError('Failed to fetch'))],
    ['a body without a name', () => Promise.resolve(jsonResponse(200, {}))],
  ])('reports the lookup as unavailable on %s', async (_case, respond) => {
    fetchMock.mockImplementation(respond);

    await expect(lookUp()).rejects.toBeInstanceOf(PlaceLookupUnavailable);
  });

  it("lets the caller's abort through unchanged", async () => {
    fetchMock.mockImplementation(
      (_url, init) =>
        new Promise((_resolve, reject) => {
          init?.signal?.addEventListener('abort', () => {
            reject(init.signal?.reason as Error);
          });
        }),
    );
    const caller = new AbortController();

    const pending = lookUp(HUELIN, caller.signal);
    caller.abort();

    await expect(pending).rejects.toBe(caller.signal.reason);
    await expect(pending).rejects.toHaveProperty('name', 'AbortError');
  });

  it('gives up and aborts the request after 10 seconds', async () => {
    vi.useFakeTimers();
    let requestSignal: AbortSignal | undefined;
    fetchMock.mockImplementation((_url, init) => {
      requestSignal = init?.signal ?? undefined;
      return new Promise(() => undefined);
    });

    const pending = lookUp();
    const outcome = expect(pending).rejects.toBeInstanceOf(PlaceLookupUnavailable);

    await vi.advanceTimersByTimeAsync(9999);
    expect(requestSignal?.aborted).toBe(false);

    await vi.advanceTimersByTimeAsync(1);
    await outcome;
    expect(requestSignal?.aborted).toBe(true);
  });
});
