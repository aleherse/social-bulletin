import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import type { Movement } from '@/entities/movement';
import { submitMovement } from '@/entities/movement/api/client.ts';
import { ApiError } from '@/shared/api';
import { I18nProvider } from '@/shared/i18n';

import { SubmitMovementButton } from './submit-movement-button.tsx';

vi.mock('@/entities/movement/api/client.ts', () => ({
  fetchCategories: vi.fn(),
  fetchMovements: vi.fn(),
  fetchMovement: vi.fn(),
  createMovement: vi.fn(),
  submitMovement: vi.fn(),
  updateMovement: vi.fn(),
}));

const draft: Movement = {
  id: '0198f2f0-6d2c-7cf0-a2b8-222222222222',
  title: 'Save the Bees',
  description: '## Why\nBecause it matters.',
  category: 'cooperative',
  area: 'municipality',
  location: 'Sheffield',
  status: 'draft',
  createdAt: '2026-07-19T10:00:00+00:00',
  updatedAt: '2026-07-19T10:00:00+00:00',
};

function renderButton(movement: Movement) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

  render(
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <SubmitMovementButton movement={movement} />
      </QueryClientProvider>
    </I18nProvider>,
  );
}

describe('SubmitMovementButton', () => {
  it('submits a draft as a proposal', async () => {
    vi.mocked(submitMovement).mockResolvedValue({ ...draft, status: 'proposed' });

    renderButton(draft);

    await userEvent.click(screen.getByRole('button', { name: 'Submit proposal' }));

    expect(vi.mocked(submitMovement).mock.calls[0]?.[0]).toBe(draft.id);
  });

  it('shows the description error when the draft cannot be proposed yet', async () => {
    vi.mocked(submitMovement).mockRejectedValue(
      new ApiError('The movement is not valid.', 400, {
        description: 'A description is required to propose the movement.',
      }),
    );

    renderButton({ ...draft, description: '' });

    await userEvent.click(screen.getByRole('button', { name: 'Submit proposal' }));

    expect(
      await screen.findByText('A description is required to propose the movement.'),
    ).toBeInTheDocument();
  });

  it('renders nothing once the movement left draft', () => {
    renderButton({ ...draft, status: 'proposed' });

    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });
});
