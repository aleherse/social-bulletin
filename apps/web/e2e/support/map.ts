import type { Page } from '@playwright/test';

import { INITIAL_VIEW } from '../../src/shared/map/model/view.ts';

interface Point {
  lat: number;
  lon: number;
}

const TILE_SIZE = 256;
const EDGE_MARGIN = 32;

function project({ lat, lon }: Point, zoom: number) {
  const worldSize = TILE_SIZE * 2 ** zoom;
  const latitude = (lat * Math.PI) / 180;

  return {
    x: (worldSize * (lon + 180)) / 360,
    y: (worldSize * (1 - Math.log(Math.tan(latitude) + 1 / Math.cos(latitude)) / Math.PI)) / 2,
  };
}

/**
 * Clicks the map at `point`, assuming it still shows INITIAL_VIEW.
 * `region.click` scrolls the map into view and fails if a map control would take the click.
 */
export async function clickMapAt(page: Page, point: Point) {
  const region = page.getByRole('region', { name: 'Location map' });
  const box = await region.boundingBox();

  if (box === null) {
    throw new Error('The location map is not rendered.');
  }

  const target = project(point, INITIAL_VIEW.zoom);
  const center = project(INITIAL_VIEW.center, INITIAL_VIEW.zoom);
  const x = box.width / 2 + target.x - center.x;
  const y = box.height / 2 + target.y - center.y;

  if (
    x < EDGE_MARGIN ||
    y < EDGE_MARGIN ||
    x > box.width - EDGE_MARGIN ||
    y > box.height - EDGE_MARGIN
  ) {
    throw new Error(
      `(${String(point.lat)}, ${String(point.lon)}) is within ${String(EDGE_MARGIN)} px of the map's edge at its initial view.`,
    );
  }

  await region.click({ position: { x, y } });
}
