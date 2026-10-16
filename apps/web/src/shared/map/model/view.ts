/**
 * Spans latitudes 31.8 to 55.7 in the 384 px map, keeping both spec points
 * (Huelin, central Sheffield) clear of Leaflet's controls; `e2e/support/map.ts`
 * turns a point into a click from these values.
 */
export const INITIAL_VIEW = { center: { lat: 45, lon: -4 }, zoom: 4 } as const;

/** Same origin as the app (FR-011): nginx locally, the map-data bucket behind CloudFront in AWS. */
export const TILES_URL = '/tiles/spain-uk.pmtiles';
