import { createElement } from 'react';

import type { GeoPoint } from '@/shared/geo';
import type { LocationMapProps } from '@/shared/map';

/** Stands in for the Leaflet adapter, which jsdom cannot drive; tests call `onPick` themselves. */
export const locationMapStub = {
  props: null as LocationMapProps | null,
  failsToRender: false,
  reset() {
    this.props = null;
    this.failsToRender = false;
  },
  pick(point: GeoPoint) {
    if (this.props === null) {
      throw new Error('The location map is not rendered.');
    }

    this.props.onPick(point);
  },
  render(props: LocationMapProps) {
    if (this.failsToRender) {
      throw new Error('The map failed to render.');
    }

    this.props = props;

    return createElement('section', { 'aria-label': props.label });
  },
};

export function LocationMap(props: LocationMapProps) {
  return locationMapStub.render(props);
}
