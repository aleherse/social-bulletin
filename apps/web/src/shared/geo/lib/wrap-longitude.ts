/** Brings a longitude from a map scrolled past the date line back into [-180, 180). */
export function wrapLongitude(lon: number): number {
  if (lon >= -180 && lon < 180) {
    return lon;
  }

  return ((((lon + 180) % 360) + 360) % 360) - 180;
}
