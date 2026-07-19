import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { Movement } from '@/entities/movement';
import { createMovement, fetchCategories, fetchMovements } from '@/entities/movement/api/client.ts';
import { ApiError } from '@/shared/api';
import { I18nProvider } from '@/shared/i18n';

import { MovementsPage } from './movements-page.tsx';

vi.mock('@/entities/movement/api/client.ts', () => ({
  fetchCategories: vi.fn(),
  fetchMovements: vi.fn(),
  fetchMovement: vi.fn(),
  createMovement: vi.fn(),
  submitMovement: vi.fn(),
}));

const draft: Movement = {
  id: '0198f2f0-6d2c-7cf0-a2b8-222222222222',
  title: 'Save the Bees',
  description: '',
  category: 'cooperative',
  area: 'municipality',
  location: 'Sheffield',
  status: 'draft',
  createdAt: '2026-07-19T10:00:00+00:00',
  updatedAt: '2026-07-19T10:00:00+00:00',
};

function renderPage(hash: string) {
  window.location.hash = hash;
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

  render(
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <MovementsPage />
      </QueryClientProvider>
    </I18nProvider>,
  );
}

beforeEach(() => {
  vi.mocked(fetchCategories).mockResolvedValue([{ id: 'cooperative' }]);
  vi.mocked(fetchMovements).mockResolvedValue([]);
});

describe('MovementsPage', () => {
  it('lists the movements of the signed-in user with their status', async () => {
    vi.mocked(fetchMovements).mockResolvedValue([draft]);

    renderPage('#/movements');

    expect(await screen.findByText('Save the Bees')).toBeInTheDocument();
    expect(screen.getByText('Draft')).toBeInTheDocument();
    expect(screen.getByText(/Municipality · Sheffield/)).toBeInTheDocument();
  });

  it('shows an empty state when no movements exist yet', async () => {
    renderPage('#/movements');

    expect(await screen.findByText('You have not proposed any movements yet.')).toBeInTheDocument();
  });

  it('asks guests to sign in', async () => {
    vi.mocked(fetchMovements).mockRejectedValue(new ApiError('Unauthorized', 401));

    renderPage('#/movements');

    expect(await screen.findByText('Sign in to propose a movement.')).toBeInTheDocument();
  });

  it('creates a draft from the new-movement route', async () => {
    vi.mocked(createMovement).mockResolvedValue(draft);

    renderPage('#/movements/new');

    await userEvent.type(screen.getByLabelText('Title'), 'Save the Bees');
    await userEvent.selectOptions(await screen.findByLabelText('Category'), 'cooperative');
    await userEvent.selectOptions(screen.getByLabelText('Area'), 'municipality');
    await userEvent.type(screen.getByLabelText('Location'), 'Sheffield');
    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(vi.mocked(createMovement).mock.calls[0]?.[0]).toEqual({
      title: 'Save the Bees',
      description: '',
      category: 'cooperative',
      area: 'municipality',
      location: 'Sheffield',
    });
  });
});
