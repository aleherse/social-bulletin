import { afterEach, describe, expect, it, vi } from 'vitest';

import { ApiError, apiResource, apiResources, jsonApiRequest, readResource } from './http.ts';

interface Movement {
  id: string;
  title: string;
}

function respondWith(body: unknown, status = 200): void {
  vi.stubGlobal(
    'fetch',
    vi.fn(() =>
      Promise.resolve(
        new Response(JSON.stringify(body), {
          status,
          headers: { 'Content-Type': 'application/vnd.api+json' },
        }),
      ),
    ),
  );
}

describe('JSON:API client', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('flattens a resource into its id and attributes', async () => {
    respondWith({ data: { type: 'movements', id: 'm-1', attributes: { title: 'Save the Bees' } } });

    await expect(apiResource<Movement>('/api/movements/m-1')).resolves.toEqual({
      id: 'm-1',
      title: 'Save the Bees',
    });
  });

  it('flattens every resource of a list, keeping their order', async () => {
    respondWith({
      data: [
        { type: 'movements', id: 'm-1', attributes: { title: 'First' } },
        { type: 'movements', id: 'm-2', attributes: { title: 'Second' } },
      ],
    });

    await expect(apiResources<Movement>('/api/movements')).resolves.toEqual([
      { id: 'm-1', title: 'First' },
      { id: 'm-2', title: 'Second' },
    ]);
  });

  it('returns an empty list for an empty collection', async () => {
    respondWith({ data: [] });

    await expect(apiResources<Movement>('/api/movements')).resolves.toEqual([]);
  });

  it('rejects with the error document instead of reading data', async () => {
    respondWith(
      {
        errors: [{ status: '404', title: 'Movement not found' }],
      },
      404,
    );

    await expect(apiResource<Movement>('/api/movements/nope')).rejects.toMatchObject({
      status: 404,
      message: 'Movement not found',
    });
    await expect(apiResource<Movement>('/api/movements/nope')).rejects.toBeInstanceOf(ApiError);
  });

  it('reads a resource out of a response it already holds', async () => {
    const response = new Response(
      JSON.stringify({
        data: { type: 'users', id: 'u-1', attributes: { email: 'a@example.com' } },
      }),
    );

    await expect(readResource<{ id: string; email: string }>(response)).resolves.toEqual({
      id: 'u-1',
      email: 'a@example.com',
    });
  });

  it('sends attributes as a JSON:API resource object', () => {
    const init = jsonApiRequest('PATCH', 'movements', { title: 'Save the Bees' });

    expect(init.method).toBe('PATCH');
    expect(init.headers).toEqual({ 'Content-Type': 'application/vnd.api+json' });
    expect(JSON.parse(init.body as string)).toEqual({
      data: { type: 'movements', attributes: { title: 'Save the Bees' } },
    });
  });
});
