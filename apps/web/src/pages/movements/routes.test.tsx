import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { Movement } from '@/entities/movement';
import {
  createMovement,
  fetchCategories,
  fetchMovement,
  fetchMovements,
  updateMovement,
} from '@/entities/movement/api/client.ts';
import { fetchCurrentUser } from '@/shared/api';
import { renderRoute } from '@/test/render-route.tsx';

vi.mock('@/entities/movement/api/client.ts', () => ({
  fetchCategories: vi.fn(),
  fetchMovements: vi.fn(),
  fetchMovement: vi.fn(),
  createMovement: vi.fn(),
  submitMovement: vi.fn(),
  updateMovement: vi.fn(),
}));

vi.mock('@/shared/api', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/shared/api')>()),
  fetchCurrentUser: vi.fn(),
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

beforeEach(() => {
  vi.mocked(fetchCurrentUser).mockResolvedValue({ email: 'author@example.com' });
  vi.mocked(fetchCategories).mockResolvedValue([{ id: 'cooperative' }]);
  vi.mocked(fetchMovements).mockResolvedValue([]);
});

describe('movements routes', () => {
  it('lists the movements of the signed-in user with their status', async () => {
    vi.mocked(fetchMovements).mockResolvedValue([draft]);

    renderRoute('/en/movements');

    expect(await screen.findByText('Save the Bees')).toBeInTheDocument();
    expect(screen.getByText('Draft')).toBeInTheDocument();
    expect(screen.getByText(/Municipality · Sheffield/)).toBeInTheDocument();
  });

  it('shows an empty state when no movements exist yet', async () => {
    renderRoute('/en/movements');

    expect(await screen.findByText('You have not proposed any movements yet.')).toBeInTheDocument();
  });

  it('sends a guest to sign in instead of rendering the list', async () => {
    vi.mocked(fetchCurrentUser).mockResolvedValue(null);

    renderRoute('/en/movements');

    expect(await screen.findByLabelText('Email')).toBeInTheDocument();
    expect(screen.queryByRole('heading', { name: 'My movements' })).not.toBeInTheDocument();
  });

  it('creates a draft from the new-movement route', async () => {
    vi.mocked(createMovement).mockResolvedValue(draft);

    renderRoute('/en/movements/new');

    await userEvent.type(await screen.findByLabelText('Title'), 'Save the Bees');
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

  it('shows a movement on its own detail route', async () => {
    vi.mocked(fetchMovement).mockResolvedValue(draft);

    renderRoute(`/en/movements/${draft.id}`);

    expect(await screen.findByText('Save the Bees')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Submit proposal' })).toBeInTheDocument();
    expect(vi.mocked(fetchMovement).mock.calls[0]?.[0]).toBe(draft.id);
  });

  it('edits a draft from the edit route', async () => {
    vi.mocked(fetchMovement).mockResolvedValue(draft);
    vi.mocked(updateMovement).mockResolvedValue({ ...draft, title: 'Save All the Bees' });

    renderRoute(`/en/movements/${draft.id}/edit`);

    const title = await screen.findByLabelText('Title');
    expect(title).toHaveValue('Save the Bees');

    await userEvent.clear(title);
    await userEvent.type(title, 'Save All the Bees');
    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(vi.mocked(updateMovement).mock.calls[0]?.[0]).toBe(draft.id);
    expect(vi.mocked(updateMovement).mock.calls[0]?.[1]).toEqual({
      title: 'Save All the Bees',
      description: '',
      category: 'cooperative',
      area: 'municipality',
      location: 'Sheffield',
    });
  });

  it('refuses to edit a movement that already left draft', async () => {
    vi.mocked(fetchMovement).mockResolvedValue({ ...draft, status: 'proposed' });

    renderRoute(`/en/movements/${draft.id}/edit`);

    expect(await screen.findByText('This movement was not found.')).toBeInTheDocument();
    expect(screen.queryByLabelText('Title')).not.toBeInTheDocument();
  });

  it('renders the not-found page for an unknown path', async () => {
    renderRoute('/en/nope');

    expect(await screen.findByText('Page not found')).toBeInTheDocument();
  });

  it('renders the not-found page for an unsupported locale', async () => {
    renderRoute('/xx/movements');

    expect(await screen.findByText('Page not found')).toBeInTheDocument();
  });
});
