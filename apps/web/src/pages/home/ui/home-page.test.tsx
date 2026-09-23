import { screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { fetchCurrentUser } from '@/shared/api';
import { renderRoute } from '@/test/render-route.tsx';

vi.mock('@/shared/api', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/shared/api')>()),
  fetchCurrentUser: vi.fn(),
  createSession: vi.fn(),
  deleteSession: vi.fn(),
}));

describe('HomePage', () => {
  it('shows the registration form when the visitor is unauthenticated', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue(null);

    renderRoute('/en');

    expect(await screen.findByLabelText('Email')).toBeInTheDocument();
    expect(screen.queryByText(/Hello,/)).not.toBeInTheDocument();
  });

  it('shows the hello view when the visitor is authenticated', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue({ email: 'user@example.com' });

    renderRoute('/en');

    expect(await screen.findByText('Hello, user@example.com!')).toBeInTheDocument();
    expect(screen.queryByLabelText('Email')).not.toBeInTheDocument();
  });

  it('sends a visitor without a locale to the detected one', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue(null);

    renderRoute('/');

    expect(await screen.findByLabelText('Email')).toBeInTheDocument();
  });
});
