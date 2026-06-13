import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { AppProviders } from '@/app/providers';
import { HomePage } from './HomePage';

function renderHomePage() {
  return render(
    <AppProviders>
      <HomePage />
    </AppProviders>,
  );
}

function jsonResponse(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

describe('HomePage', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('should render the registration form when current user is anonymous', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(
      jsonResponse({ message: 'Authentication is required.' }, 401),
    );

    renderHomePage();

    expect(await screen.findByRole('heading', { name: 'Join SocialBulletin' })).toBeVisible();
    expect(screen.getByLabelText('Email address')).toBeVisible();
  });

  it('should greet the authenticated user after registration succeeds', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(jsonResponse({ message: 'Authentication is required.' }, 401))
      .mockResolvedValueOnce(jsonResponse({ user: { email: 'person@example.com' } }));

    renderHomePage();

    await userEvent.type(await screen.findByLabelText('Email address'), 'person@example.com');
    await userEvent.click(screen.getByRole('button', { name: 'Continue' }));

    expect(await screen.findByRole('heading', { name: 'Hello, person@example.com' })).toBeVisible();
  });

  it('should return to the registration form after logout', async () => {
    vi.mocked(fetch)
      .mockResolvedValueOnce(jsonResponse({ user: { email: 'person@example.com' } }))
      .mockResolvedValueOnce(new Response(null, { status: 204 }));

    renderHomePage();

    expect(await screen.findByRole('heading', { name: 'Hello, person@example.com' })).toBeVisible();
    await userEvent.click(screen.getByRole('button', { name: 'Log out' }));

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Join SocialBulletin' })).toBeVisible();
    });
  });
});
