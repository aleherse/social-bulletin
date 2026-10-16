import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { lookUpPlaceName, PlaceLookupUnavailable } from '@/entities/movement';
import type { Area } from '@/entities/movement';
import { currentPosition, PositionDenied, PositionUnavailable } from '@/shared/geo';
import { I18nProvider } from '@/shared/i18n';

import { locationMapStub } from './location-map.stub.ts';
import { LocationPicker } from './location-picker.tsx';

vi.mock('@/shared/map', () => import('./location-map.stub.ts'));
vi.mock('@/entities/movement/api/look-up-place-name.ts', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/entities/movement/api/look-up-place-name.ts')>()),
  lookUpPlaceName: vi.fn(),
}));
vi.mock('@/shared/geo/api/current-position.ts', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/shared/geo/api/current-position.ts')>()),
  currentPosition: vi.fn(),
}));

const HUELIN = { lat: 36.7033, lon: -4.4366 };
const SHEFFIELD = { lat: 53.3811, lon: -1.4701 };

function deferred<T>() {
  let resolve!: (value: T) => void;
  let reject!: (reason: unknown) => void;
  const promise = new Promise<T>((res, rej) => {
    resolve = res;
    reject = rej;
  });

  return { promise, resolve, reject };
}

function renderPicker(area: Area = 'neighborhood') {
  const onPin = vi.fn();
  const onPlaceFound = vi.fn();
  const view = (current: Area) => (
    <I18nProvider>
      <LocationPicker area={current} onPin={onPin} onPlaceFound={onPlaceFound} />
      <p>Rest of the form</p>
    </I18nProvider>
  );
  const { rerender } = render(view(area));

  return {
    onPin,
    onPlaceFound,
    setArea: (next: Area) => {
      rerender(view(next));
    },
  };
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

beforeEach(() => {
  locationMapStub.reset();
  vi.mocked(lookUpPlaceName).mockReset();
  vi.mocked(currentPosition).mockReset();
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('LocationPicker', () => {
  it('pins the point, looks up its name at the area level, and reports it', async () => {
    const lookup = deferred<string | null>();
    vi.mocked(lookUpPlaceName).mockReturnValue(lookup.promise);
    const { onPlaceFound } = renderPicker('neighborhood');

    await pin(HUELIN);

    expect(locationMapStub.props?.point).toEqual(HUELIN);
    expect(lookUpPlaceName).toHaveBeenCalledWith(HUELIN, 'neighborhood', expect.any(AbortSignal));
    expect(screen.getByRole('status')).toHaveTextContent('Looking up the place…');

    await settle(() => {
      lookup.resolve('Huelin');
    });

    expect(onPlaceFound).toHaveBeenCalledWith('Huelin');
    expect(screen.getByRole('status')).toBeEmptyDOMElement();
  });

  it('keeps the marker and explains when no place is at that level', async () => {
    vi.mocked(lookUpPlaceName).mockResolvedValue(null);
    const { onPlaceFound } = renderPicker();

    await pin(HUELIN);

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'No place name was found at that level. Type it in instead.',
    );
    expect(onPlaceFound).not.toHaveBeenCalled();
    expect(locationMapStub.props?.point).toEqual(HUELIN);
  });

  it('explains when the lookup is unavailable', async () => {
    vi.mocked(lookUpPlaceName).mockRejectedValue(new PlaceLookupUnavailable());
    const { onPlaceFound } = renderPicker();

    await pin(HUELIN);

    expect(await screen.findByRole('alert')).toHaveTextContent(
      "We couldn't look up that place. Type it in instead.",
    );
    expect(onPlaceFound).not.toHaveBeenCalled();
  });

  it('aborts a superseded lookup and ignores its late answer', async () => {
    const first = deferred<string | null>();
    const second = deferred<string | null>();
    vi.mocked(lookUpPlaceName)
      .mockReturnValueOnce(first.promise)
      .mockReturnValueOnce(second.promise);
    const { onPlaceFound } = renderPicker('municipality');

    await pin(HUELIN);
    const firstSignal = vi.mocked(lookUpPlaceName).mock.calls[0]?.[2];
    await pin(SHEFFIELD);

    expect(firstSignal?.aborted).toBe(true);

    await settle(() => {
      first.resolve('Málaga');
    });
    await settle(() => {
      second.resolve('Sheffield');
    });

    expect(onPlaceFound).toHaveBeenCalledTimes(1);
    expect(onPlaceFound).toHaveBeenCalledWith('Sheffield');
  });

  it('clears a previous message on the next pin', async () => {
    vi.mocked(lookUpPlaceName).mockResolvedValueOnce(null);
    renderPicker();

    await pin(HUELIN);
    expect(await screen.findByRole('alert')).toBeInTheDocument();

    vi.mocked(lookUpPlaceName).mockReturnValueOnce(deferred<string | null>().promise);
    await pin(SHEFFIELD);

    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  it('reports every pin before its lookup resolves', async () => {
    vi.mocked(lookUpPlaceName).mockReturnValue(deferred<string | null>().promise);
    const { onPin } = renderPicker();

    await pin(HUELIN);
    await pin(SHEFFIELD);

    expect(onPin).toHaveBeenCalledTimes(2);
  });

  it('looks up at the area chosen when the pin was placed', async () => {
    const lookup = deferred<string | null>();
    vi.mocked(lookUpPlaceName).mockReturnValue(lookup.promise);
    const { onPlaceFound, setArea } = renderPicker('municipality');

    await pin(HUELIN);
    setArea('province');
    await settle(() => {
      lookup.resolve('Málaga');
    });

    expect(lookUpPlaceName).toHaveBeenCalledTimes(1);
    expect(lookUpPlaceName).toHaveBeenCalledWith(HUELIN, 'municipality', expect.any(AbortSignal));
    expect(onPlaceFound).toHaveBeenCalledWith('Málaga');
  });

  it('reports the new name when the author pins again after a lookup', async () => {
    vi.mocked(lookUpPlaceName).mockResolvedValueOnce('Málaga').mockResolvedValueOnce('Sheffield');
    const { onPlaceFound } = renderPicker('municipality');

    await pin(HUELIN);
    await pin(SHEFFIELD);

    expect(onPlaceFound).toHaveBeenNthCalledWith(1, 'Málaga');
    expect(onPlaceFound).toHaveBeenNthCalledWith(2, 'Sheffield');
  });

  it('leaves the rest of the form working when the map fails to render', () => {
    vi.spyOn(console, 'error').mockImplementation(() => undefined);
    locationMapStub.failsToRender = true;

    renderPicker();

    expect(screen.queryByRole('region', { name: 'Location map' })).not.toBeInTheDocument();
    expect(screen.getByText('Rest of the form')).toBeInTheDocument();
  });

  describe('use my location', () => {
    const useMyLocation = () => screen.getByRole('button', { name: 'Use my location' });

    it('pins the position and names it as a click would', async () => {
      vi.mocked(currentPosition).mockResolvedValue(HUELIN);
      vi.mocked(lookUpPlaceName).mockResolvedValue('Málaga');
      const { onPin, onPlaceFound } = renderPicker('municipality');

      await userEvent.click(useMyLocation());

      expect(locationMapStub.props?.point).toEqual(HUELIN);
      expect(onPin).toHaveBeenCalledTimes(1);
      expect(lookUpPlaceName).toHaveBeenCalledWith(HUELIN, 'municipality', expect.any(AbortSignal));
      expect(onPlaceFound).toHaveBeenCalledWith('Málaga');
    });

    it('explains a refused permission and changes nothing else', async () => {
      vi.mocked(lookUpPlaceName).mockReturnValue(deferred<string | null>().promise);
      vi.mocked(currentPosition).mockRejectedValue(new PositionDenied());
      const { onPin, onPlaceFound } = renderPicker();
      await pin(SHEFFIELD);
      vi.mocked(lookUpPlaceName).mockClear();
      onPin.mockClear();

      await userEvent.click(useMyLocation());

      expect(await screen.findByRole('alert')).toHaveTextContent(
        "We couldn't use your location. You can still type it.",
      );
      expect(lookUpPlaceName).not.toHaveBeenCalled();
      expect(onPin).not.toHaveBeenCalled();
      expect(onPlaceFound).not.toHaveBeenCalled();
      expect(locationMapStub.props?.point).toEqual(SHEFFIELD);
    });

    it('explains an unavailable position', async () => {
      vi.mocked(currentPosition).mockRejectedValue(new PositionUnavailable());
      renderPicker();

      await userEvent.click(useMyLocation());

      expect(await screen.findByRole('alert')).toHaveTextContent(
        "Your location isn't available right now. You can still type it.",
      );
    });

    it('is disabled while the position is requested', async () => {
      const position = deferred<{ lat: number; lon: number }>();
      vi.mocked(currentPosition).mockReturnValue(position.promise);
      renderPicker();

      await userEvent.click(useMyLocation());
      expect(useMyLocation()).toBeDisabled();

      await settle(() => {
        position.reject(new PositionUnavailable());
      });
      expect(useMyLocation()).toBeEnabled();
    });

    it('never asks for the position until chosen', () => {
      renderPicker();

      expect(currentPosition).not.toHaveBeenCalled();
    });

    it("clears a previous lookup's message", async () => {
      vi.mocked(lookUpPlaceName).mockResolvedValue(null);
      vi.mocked(currentPosition).mockReturnValue(deferred<{ lat: number; lon: number }>().promise);
      renderPicker();
      await pin(HUELIN);
      expect(await screen.findByRole('alert')).toBeInTheDocument();

      await userEvent.click(useMyLocation());

      expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('still works when the map fails to render', async () => {
      vi.spyOn(console, 'error').mockImplementation(() => undefined);
      locationMapStub.failsToRender = true;
      vi.mocked(currentPosition).mockResolvedValue(HUELIN);
      vi.mocked(lookUpPlaceName).mockResolvedValue('Málaga');
      const { onPlaceFound } = renderPicker('municipality');

      await userEvent.click(useMyLocation());

      expect(onPlaceFound).toHaveBeenCalledWith('Málaga');
    });
  });
});
