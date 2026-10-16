import { afterEach, describe, expect, it, vi } from 'vitest';

import { currentPosition, PositionDenied, PositionUnavailable } from './current-position.ts';

type Success = Parameters<Geolocation['getCurrentPosition']>[0];
type Failure = NonNullable<Parameters<Geolocation['getCurrentPosition']>[1]>;

function stubGeolocation(
  answer: (success: Success, failure: Failure) => void,
): ReturnType<typeof vi.fn<Geolocation['getCurrentPosition']>> {
  const getCurrentPosition = vi.fn<Geolocation['getCurrentPosition']>((success, failure) => {
    answer(success, failure as Failure);
  });
  vi.stubGlobal('navigator', { geolocation: { getCurrentPosition } });

  return getCurrentPosition;
}

function positionError(code: number): GeolocationPositionError {
  return { code, message: '', PERMISSION_DENIED: 1, POSITION_UNAVAILABLE: 2, TIMEOUT: 3 };
}

afterEach(() => {
  vi.unstubAllGlobals();
});

describe('currentPosition', () => {
  it("resolves the browser's position as a point", async () => {
    stubGeolocation((success) => {
      success({ coords: { latitude: 36.7033, longitude: -4.4366 } } as GeolocationPosition);
    });

    await expect(currentPosition()).resolves.toEqual({ lat: 36.7033, lon: -4.4366 });
  });

  it('rejects with PositionDenied when permission is refused', async () => {
    stubGeolocation((_success, failure) => {
      failure(positionError(1));
    });

    await expect(currentPosition()).rejects.toBeInstanceOf(PositionDenied);
  });

  it.each([
    ['unavailable', 2],
    ['timed out', 3],
  ])('rejects with PositionUnavailable when the position is %s', async (_case, code) => {
    stubGeolocation((_success, failure) => {
      failure(positionError(code));
    });

    await expect(currentPosition()).rejects.toBeInstanceOf(PositionUnavailable);
  });

  it('rejects with PositionUnavailable when the browser has no geolocation', async () => {
    vi.stubGlobal('navigator', {});

    await expect(currentPosition()).rejects.toBeInstanceOf(PositionUnavailable);
  });

  it('asks only when called, giving up after 10 seconds', async () => {
    const getCurrentPosition = stubGeolocation((success) => {
      success({ coords: { latitude: 0, longitude: 0 } } as GeolocationPosition);
    });

    expect(getCurrentPosition).not.toHaveBeenCalled();

    await currentPosition();

    expect(getCurrentPosition).toHaveBeenCalledWith(
      expect.any(Function),
      expect.any(Function),
      expect.objectContaining({ timeout: 10_000 }),
    );
  });
});
