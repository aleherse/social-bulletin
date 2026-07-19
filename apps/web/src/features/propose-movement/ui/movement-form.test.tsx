import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { fetchCategories } from '@/entities/movement/api/client.ts';
import { I18nProvider } from '@/shared/i18n';

import { MovementForm } from './movement-form.tsx';

vi.mock('@/entities/movement/api/client.ts', () => ({
  fetchCategories: vi.fn(),
  fetchMovements: vi.fn(),
  fetchMovement: vi.fn(),
  createMovement: vi.fn(),
  submitMovement: vi.fn(),
}));

function renderForm() {
  const onSubmit = vi.fn();
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

  render(
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <MovementForm pending={false} serverError={null} fieldErrors={{}} onSubmit={onSubmit} />
      </QueryClientProvider>
    </I18nProvider>,
  );

  return onSubmit;
}

beforeEach(() => {
  vi.mocked(fetchCategories).mockResolvedValue([{ id: 'cooperative' }]);
});

describe('MovementForm', () => {
  it('requires title, category, area, and location before submitting', async () => {
    const onSubmit = renderForm();

    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(await screen.findAllByText('This field is required.')).toHaveLength(4);
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('submits a local draft with its location and optional empty description', async () => {
    const onSubmit = renderForm();

    await userEvent.type(screen.getByLabelText('Title'), 'Community Gardens for Everyone');
    await userEvent.selectOptions(await screen.findByLabelText('Category'), 'cooperative');
    await userEvent.selectOptions(screen.getByLabelText('Area'), 'municipality');
    await userEvent.type(screen.getByLabelText('Location'), 'Sheffield');
    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(onSubmit).toHaveBeenCalledWith({
      title: 'Community Gardens for Everyone',
      description: '',
      category: 'cooperative',
      area: 'municipality',
      location: 'Sheffield',
    });
  });

  it('hides the location for international movements and submits it as null', async () => {
    const onSubmit = renderForm();

    await userEvent.type(screen.getByLabelText('Title'), 'Global Climate Strike');
    await userEvent.selectOptions(await screen.findByLabelText('Category'), 'cooperative');
    await userEvent.selectOptions(screen.getByLabelText('Area'), 'international');

    expect(screen.queryByLabelText('Location')).not.toBeInTheDocument();

    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(onSubmit).toHaveBeenCalledWith({
      title: 'Global Climate Strike',
      description: '',
      category: 'cooperative',
      area: 'international',
      location: null,
    });
  });
});
