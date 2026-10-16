import 'leaflet/dist/leaflet.css';

import L from 'leaflet';
import { useEffect, useMemo } from 'react';
import { MapContainer, Marker, TileLayer, useMap, useMapEvents } from 'react-leaflet';

import { type GeoPoint, wrapLongitude } from '@/shared/geo';

import type { LocationMapProps } from '../model/types.ts';
import { INITIAL_VIEW, TILES_URL } from '../model/view.ts';

const PINNED_ZOOM = 14;
const MAX_ZOOM = 19;

// The Open Database License requires the OpenStreetMap credit wherever the data is shown.
const ATTRIBUTION =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

/** ADR-0022: the one place Leaflet is used. */
export function LocationMap({ label, markerLabel, point, onPick }: LocationMapProps) {
  return (
    <section aria-label={label}>
      <MapContainer
        center={[INITIAL_VIEW.center.lat, INITIAL_VIEW.center.lon]}
        zoom={INITIAL_VIEW.zoom}
        className="h-96 w-full"
      >
        <TileLayer url={TILES_URL} attribution={ATTRIBUTION} maxZoom={MAX_ZOOM} />
        <PickOnClick onPick={onPick} />
        <FlyToPoint point={point} />
        {point !== null && <PinnedMarker point={point} label={markerLabel} />}
      </MapContainer>
    </section>
  );
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
