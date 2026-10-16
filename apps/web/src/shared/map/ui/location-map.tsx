import 'leaflet/dist/leaflet.css';

import L from 'leaflet';
import { leafletLayer } from 'protomaps-leaflet';
import { useEffect, useMemo } from 'react';
import { MapContainer, Marker, useMap, useMapEvents } from 'react-leaflet';

import { type GeoPoint, wrapLongitude } from '@/shared/geo';

import type { LocationMapProps } from '../model/types.ts';
import { INITIAL_VIEW, TILES_URL } from '../model/view.ts';

const PINNED_ZOOM = 14;

// The Open Database License requires the OpenStreetMap credit wherever the data is shown.
const ATTRIBUTION =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="https://protomaps.com">Protomaps</a>';

/** ADR-0022: the one place Leaflet and protomaps-leaflet are used. */
export function LocationMap({ label, markerLabel, point, onPick }: LocationMapProps) {
  return (
    <section aria-label={label}>
      <MapContainer
        center={[INITIAL_VIEW.center.lat, INITIAL_VIEW.center.lon]}
        zoom={INITIAL_VIEW.zoom}
        className="h-96 w-full"
      >
        <Basemap />
        <PickOnClick onPick={onPick} />
        <FlyToPoint point={point} />
        {point !== null && <PinnedMarker point={point} label={markerLabel} />}
      </MapContainer>
    </section>
  );
}

function Basemap() {
  const map = useMap();

  useEffect(() => {
    // A GridLayer subclass at runtime; protomaps-leaflet's declarations describe it structurally.
    const layer = leafletLayer({
      url: TILES_URL,
      flavor: 'light',
      attribution: ATTRIBUTION,
    }) as unknown as L.GridLayer;
    layer.addTo(map);

    return () => {
      map.removeLayer(layer);
    };
  }, [map]);

  return null;
}

function PickOnClick({ onPick }: { onPick: (point: GeoPoint) => void }) {
  useMapEvents({
    click(event) {
      onPick({ lat: event.latlng.lat, lon: wrapLongitude(event.latlng.lng) });
    },
  });

  return null;
}

function FlyToPoint({ point }: { point: GeoPoint | null }) {
  const map = useMap();

  useEffect(() => {
    if (point !== null) {
      map.flyTo([point.lat, point.lon], PINNED_ZOOM);
    }
  }, [map, point]);

  return null;
}

function PinnedMarker({ point, label }: { point: GeoPoint; label: string }) {
  const icon = useMemo(() => {
    const marker = document.createElement('span');
    marker.setAttribute('role', 'img');
    marker.setAttribute('aria-label', label);
    marker.className =
      'block size-4 rounded-full border-2 border-white bg-primary shadow-md ring-2 ring-primary';

    return L.divIcon({ html: marker, className: '', iconSize: [16, 16], iconAnchor: [8, 8] });
  }, [label]);

  return (
    <Marker position={[point.lat, point.lon]} icon={icon} interactive={false} keyboard={false} />
  );
}
