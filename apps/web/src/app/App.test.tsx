import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { App } from './App';
import { AppProviders } from './providers';
import { fetchCurrentUser, registerOrAuthenticate } from '@/shared/api';

vi.mock('@/shared/api', () => ({
  fetchCurrentUser: vi.fn(),
  registerOrAuthenticate: vi.fn(),
}));

function renderApp() {
  return render(
    <AppProviders>
      <App />
    </AppProviders>,
  );
}

describe('App', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should render the registration form when unauthenticated', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue(null);

    renderApp();

    expect(await screen.findByRole('textbox', { name: /email address/i })).toBeInTheDocument();
  });

  it('should render hello view after successful registration', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue(null);
    vi.mocked(registerOrAuthenticate).mockResolvedValue({ email: 'new-user@example.com' });

    renderApp();

    await userEvent.type(
      await screen.findByRole('textbox', { name: /email address/i }),
      'new-user@example.com',
    );
    await userEvent.click(screen.getByRole('button', { name: /continue/i }));

    expect(await screen.findByText('Hello, new-user@example.com')).toBeInTheDocument();
  });

  it('should render hello view when authenticated on page load', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue({ email: 'known-user@example.com' });

    renderApp();

    expect(await screen.findByText('Hello, known-user@example.com')).toBeInTheDocument();
  });
});
