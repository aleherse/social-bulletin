import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { fetchCurrentUser } from '@/shared/api';
import { I18nProvider } from '@/shared/i18n';

import { HomePage } from './home-page.tsx';

vi.mock('@/shared/api', () => ({
  fetchCurrentUser: vi.fn(),
  createSession: vi.fn(),
  deleteSession: vi.fn(),
  SessionError: class SessionError extends Error {},
}));

function renderHomePage() {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

  render(
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <HomePage />
      </QueryClientProvider>
    </I18nProvider>,
  );
}

describe('HomePage', () => {
  it('shows the registration form when the visitor is unauthenticated', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue(null);

    renderHomePage();

    expect(await screen.findByLabelText('Email')).toBeInTheDocument();
    expect(screen.queryByText(/Hello,/)).not.toBeInTheDocument();
  });

  it('shows the hello view when the visitor is authenticated', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue({ email: 'user@example.com' });

    renderHomePage();

    expect(await screen.findByText('Hello, user@example.com!')).toBeInTheDocument();
    expect(screen.queryByLabelText('Email')).not.toBeInTheDocument();
  });
});
