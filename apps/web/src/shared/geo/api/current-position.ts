import type { GeoPoint } from '../model/types.ts';

const POSITION_TIMEOUT_MS = 10_000;
const PERMISSION_DENIED = 1;

/** The author refused to share their position. */
export class PositionDenied extends Error {
  constructor() {
    super('Permission to use the position was refused.');
    this.name = 'PositionDenied';
  }
}

/** The browser could not tell the position, in time or at all. */
export class PositionUnavailable extends Error {
  constructor() {
    super('The position is unavailable.');
    this.name = 'PositionUnavailable';
  }
}

/** Asks the browser for the author's position; only ever called on their explicit request (FR-002). */
export function currentPosition(): Promise<GeoPoint> {
  return new Promise((resolve, reject) => {
    if (!('geolocation' in navigator)) {
      reject(new PositionUnavailable());
      return;
    }

    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        resolve({ lat: coords.latitude, lon: coords.longitude });
      },
      (error) => {
        reject(error.code === PERMISSION_DENIED ? new PositionDenied() : new PositionUnavailable());
      },
      { timeout: POSITION_TIMEOUT_MS },
    );
  });
}
