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

  it('shows the hero as the main heading above the registration form', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue(null);

    renderHomePage();

    const heading = screen.getByRole('heading', {
      level: 1,
      name: 'Welcome to Social Bulletin',
    });
    const description = screen.getByText(
      'Your place to stay up to date with the latest social movements events',
    );
    const email = await screen.findByLabelText('Email');

    expect(heading).toBeVisible();
    expect(description).toBeVisible();
    expect(heading.compareDocumentPosition(email) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
    expect(screen.getAllByText('Welcome to Social Bulletin')).toHaveLength(1);
    expect(screen.getByText('Register or sign in')).toBeInTheDocument();
  });

  it('shows the hero while the current user is loading', () => {
    vi.mocked(fetchCurrentUser).mockReturnValue(new Promise(() => undefined));

    renderHomePage();

    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
      'Welcome to Social Bulletin',
    );
    expect(screen.getByText('Loading…')).toBeInTheDocument();
  });

  it('keeps the hero above the greeting when the visitor is authenticated', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue({ email: 'user@example.com' });

    renderHomePage();

    const heading = screen.getByRole('heading', { level: 1 });
    const greeting = await screen.findByText('Hello, user@example.com!');

    expect(heading).toHaveTextContent('Welcome to Social Bulletin');
    expect(
      heading.compareDocumentPosition(greeting) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
  });
});
