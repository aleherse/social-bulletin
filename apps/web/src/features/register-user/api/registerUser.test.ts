import { afterEach, describe, expect, it, vi } from 'vitest';
import { registerUser, ApiConflictError, ApiValidationError, ApiError } from './registerUser';

describe('registerUser', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should resolve with userId on 201 response', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ userId: 'uuid-v6' }), {
        status: 201,
        headers: { 'Content-Type': 'application/json' },
      }),
    );

    const result = await registerUser({
      email: 'user@example.com',
      password: 'Str0ng!Pass',
      termsAccepted: true,
    });

    expect(result.userId).toBe('uuid-v6');
  });

  it('should throw ApiConflictError on 409 response', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ error: 'email_already_registered' }), {
        status: 409,
        headers: { 'Content-Type': 'application/json' },
      }),
    );

    await expect(
      registerUser({ email: 'dup@example.com', password: 'Str0ng!Pass', termsAccepted: true }),
    ).rejects.toBeInstanceOf(ApiConflictError);
  });

  it('should throw ApiValidationError on 422 response', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify({ errors: [{ field: 'password', message: 'Too weak.' }] }),
        { status: 422, headers: { 'Content-Type': 'application/json' } },
      ),
    );

    await expect(
      registerUser({ email: 'user@example.com', password: '123', termsAccepted: true }),
    ).rejects.toBeInstanceOf(ApiValidationError);
  });

  it('should throw ApiError on unexpected status', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response('Internal Server Error', { status: 500 }),
    );

    await expect(
      registerUser({ email: 'user@example.com', password: 'Str0ng!Pass', termsAccepted: true }),
    ).rejects.toBeInstanceOf(ApiError);
  });
});
