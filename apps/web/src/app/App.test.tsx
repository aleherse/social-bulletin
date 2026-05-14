import '@testing-library/jest-dom/vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { App } from './App';

describe('App startup health check', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should show no error when the API health check is ok', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ status: 'ok' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    );

    render(<App />);

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1));

    expect(fetchMock).toHaveBeenCalledWith('http://api.bulletin.local/health', {
      headers: { Accept: 'application/json' },
    });

    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  it('should show an error when the API health check is not ok', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ status: 'down' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    );

    render(<App />);

    expect(await screen.findByRole('alert')).toHaveTextContent('Service unavailable');
  });

  it('should show an error when the API health check cannot be reached', async () => {
    vi.spyOn(globalThis, 'fetch').mockRejectedValue(new Error('network unavailable'));

    render(<App />);

    expect(await screen.findByRole('alert')).toHaveTextContent('Service unavailable');
  });
});
