import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { lookUpPlaceName, PlaceLookupUnavailable } from '@/entities/movement';
import type { Movement } from '@/entities/movement';
import { fetchCategories } from '@/entities/movement/api/client.ts';
import { I18nProvider } from '@/shared/i18n';

import { locationMapStub } from './location-map.stub.ts';
import { MovementForm } from './movement-form.tsx';

vi.mock('@/entities/movement/api/client.ts', () => ({
  fetchCategories: vi.fn(),
  fetchMovements: vi.fn(),
  fetchMovement: vi.fn(),
  createMovement: vi.fn(),
  submitMovement: vi.fn(),
  updateMovement: vi.fn(),
}));
vi.mock('@/shared/map', () => import('./location-map.stub.ts'));
vi.mock('@/entities/movement/api/look-up-place-name.ts', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/entities/movement/api/look-up-place-name.ts')>()),
  lookUpPlaceName: vi.fn(),
}));

const HUELIN = { lat: 36.7033, lon: -4.4366 };

function renderForm(initial?: Movement) {
  const onSubmit = vi.fn();
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

  const { unmount } = render(
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <MovementForm
          initial={initial}
          pending={false}
          serverError={null}
          fieldErrors={{}}
          onSubmit={onSubmit}
        />
      </QueryClientProvider>
    </I18nProvider>,
  );

  return { onSubmit, unmount };
}

function deferred<T>() {
  let resolve!: (value: T) => void;
  const promise = new Promise<T>((res) => {
    resolve = res;
  });

  return { promise, resolve };
}

async function fillDraft(area: string) {
  await userEvent.type(screen.getByLabelText('Title'), 'Huelin Community Kitchen');
  await userEvent.selectOptions(await screen.findByLabelText('Category'), 'cooperative');
  await userEvent.selectOptions(screen.getByLabelText('Area'), area);
}

async function pin(point: { lat: number; lon: number }) {
  await act(async () => {
    locationMapStub.pick(point);
    await Promise.resolve();
  });
}

async function settle(action: () => void) {
  await act(async () => {
    action();
    await Promise.resolve();
  });
}

function lookupSignal(call = 0) {
  return vi.mocked(lookUpPlaceName).mock.calls[call]?.[2];
}

beforeEach(() => {
  vi.mocked(fetchCategories).mockResolvedValue([{ id: 'cooperative' }]);
  vi.mocked(lookUpPlaceName).mockReset();
  locationMapStub.reset();
});

describe('MovementForm', () => {
  it('requires title, category, area, and location before submitting', async () => {
    const { onSubmit } = renderForm();

    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(await screen.findAllByText('This field is required.')).toHaveLength(4);
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('submits a local draft with its location and optional empty description', async () => {
    const { onSubmit } = renderForm();

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
    const { onSubmit } = renderForm();

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

  it('shows the map only once a non-international area is chosen', async () => {
    renderForm();

    expect(screen.queryByRole('region', { name: 'Location map' })).not.toBeInTheDocument();

    await userEvent.selectOptions(screen.getByLabelText('Area'), 'municipality');
    expect(screen.getByRole('region', { name: 'Location map' })).toBeInTheDocument();

    await userEvent.selectOptions(screen.getByLabelText('Area'), 'international');
    expect(screen.queryByRole('region', { name: 'Location map' })).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Location')).not.toBeInTheDocument();
  });

  it('fills the location from a pin and submits only its name', async () => {
    vi.mocked(lookUpPlaceName).mockResolvedValue('Huelin');
    const { onSubmit } = renderForm();

    await fillDraft('neighborhood');
    await pin(HUELIN);

    expect(screen.getByLabelText('Location')).toHaveValue('Huelin');

    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(onSubmit).toHaveBeenCalledWith({
      title: 'Huelin Community Kitchen',
      description: '',
      category: 'cooperative',
      area: 'neighborhood',
      location: 'Huelin',
    });
  });

  it('keeps what the author types while a lookup is pending, and the marker too', async () => {
    const lookup = deferred<string | null>();
    vi.mocked(lookUpPlaceName).mockReturnValue(lookup.promise);
    renderForm();

    await fillDraft('neighborhood');
    await pin(HUELIN);
    await userEvent.type(screen.getByLabelText('Location'), 'Huelin, Málaga');
    await settle(() => {
      lookup.resolve('Huelin');
    });

    expect(screen.getByLabelText('Location')).toHaveValue('Huelin, Málaga');
    expect(locationMapStub.props?.point).toEqual(HUELIN);
  });

  it('saves the typed name before the lookup answers, and abandons the lookup on leaving', async () => {
    vi.mocked(lookUpPlaceName).mockReturnValue(deferred<string | null>().promise);
    const { onSubmit, unmount } = renderForm();

    await fillDraft('neighborhood');
    await pin(HUELIN);
    await userEvent.type(screen.getByLabelText('Location'), 'Huelin, Málaga');
    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ location: 'Huelin, Málaga' }));

    unmount();
    expect(lookupSignal()?.aborted).toBe(true);
  });

  it('does not look up again or rename the location when the area changes', async () => {
    vi.mocked(lookUpPlaceName).mockResolvedValue('Málaga');
    renderForm();

    await fillDraft('municipality');
    await pin(HUELIN);
    expect(screen.getByLabelText('Location')).toHaveValue('Málaga');

    await userEvent.selectOptions(screen.getByLabelText('Area'), 'province');

    expect(screen.getByLabelText('Location')).toHaveValue('Málaga');
    expect(lookUpPlaceName).toHaveBeenCalledTimes(1);
  });

  it('opens a saved draft with its location and no marker, without looking it up', () => {
    renderForm({
      id: '0198f2f0-6d2c-7cf0-a2b8-222222222222',
      title: 'Community Gardens for Everyone',
      description: '',
      category: 'cooperative',
      area: 'municipality',
      location: 'Sheffield',
      status: 'draft',
      createdAt: '2026-10-16T10:00:00+00:00',
      updatedAt: '2026-10-16T10:00:00+00:00',
    });

    expect(screen.getByLabelText('Location')).toHaveValue('Sheffield');
    expect(locationMapStub.props?.point).toBeNull();
    expect(lookUpPlaceName).not.toHaveBeenCalled();
  });

  it('saves the name the author typed over a filled one', async () => {
    vi.mocked(lookUpPlaceName).mockResolvedValue('Huelin');
    const { onSubmit } = renderForm();

    await fillDraft('neighborhood');
    await pin(HUELIN);
    await userEvent.clear(screen.getByLabelText('Location'));
    await userEvent.type(screen.getByLabelText('Location'), 'Barrio de Huelin');
    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({ location: 'Barrio de Huelin' }),
    );
  });

  it('drops the pin and its pending lookup when the area becomes international', async () => {
    const lookup = deferred<string | null>();
    vi.mocked(lookUpPlaceName).mockReturnValue(lookup.promise);
    renderForm();

    await fillDraft('municipality');
    await pin(HUELIN);
    await userEvent.selectOptions(screen.getByLabelText('Area'), 'international');
    await settle(() => {
      lookup.resolve('Málaga');
    });
    await userEvent.selectOptions(screen.getByLabelText('Area'), 'municipality');

    expect(screen.getByRole('region', { name: 'Location map' })).toBeInTheDocument();
    expect(locationMapStub.props?.point).toBeNull();
    expect(lookupSignal()?.aborted).toBe(true);
    expect(screen.getByLabelText('Location')).toHaveValue('');
  });

  it.each([
    ['no place is found', () => Promise.resolve(null)],
    ['the lookup is unavailable', () => Promise.reject(new PlaceLookupUnavailable())],
  ])('still saves a typed location when %s', async (_case, answer) => {
    vi.mocked(lookUpPlaceName).mockImplementation(answer);
    const { onSubmit } = renderForm();

    await fillDraft('municipality');
    await pin(HUELIN);
    await screen.findByRole('alert');
    await userEvent.type(screen.getByLabelText('Location'), 'Sheffield');
    await userEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ location: 'Sheffield' }));
  });
});
