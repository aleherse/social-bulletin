import { describe, it, expect, vi, beforeEach } from 'vitest';
import { apiFetch } from './client';

describe('apiFetch', () => {
  beforeEach(() => {
    vi.resetAllMocks();
  });

  it('returns parsed JSON on success', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ email: 'test@example.com' }),
    } as Response);

    const result = await apiFetch<{ email: string }>('/api/register', {
      method: 'POST',
      body: JSON.stringify({ email: 'test@example.com' }),
    });

    expect(result).toEqual({ email: 'test@example.com' });
    expect(global.fetch).toHaveBeenCalledWith(
      expect.stringContaining('/api/register'),
      expect.objectContaining({ credentials: 'include', method: 'POST' }),
    );
  });

  it('throws on non-ok response', async () => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 401,
    } as Response);

    await expect(apiFetch('/api/me')).rejects.toThrow('API error 401');
  });
});
