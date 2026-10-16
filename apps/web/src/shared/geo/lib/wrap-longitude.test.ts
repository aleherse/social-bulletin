import { describe, expect, it } from 'vitest';

import { wrapLongitude } from './wrap-longitude.ts';

describe('wrapLongitude', () => {
  it('returns a longitude already in range unchanged', () => {
    const huelin = -4.4366;

    // eslint-disable-next-line sonarjs/no-floating-point-equality -- bit-for-bit is the point: wrapping an in-range value once drifted it by 1e-14
    expect(wrapLongitude(huelin)).toBe(huelin);
  });

  it('wraps a longitude east of the date line', () => {
    expect(wrapLongitude(190)).toBe(-170);
  });

  it('wraps a longitude west of the date line', () => {
    expect(wrapLongitude(-190)).toBe(170);
  });

  it('maps 180 to -180, keeping the range half-open', () => {
    expect(wrapLongitude(180)).toBe(-180);
  });

  it('wraps a longitude more than one turn away', () => {
    expect(wrapLongitude(535.5634)).toBeCloseTo(175.5634, 10);
  });
});
