import type { GeoPoint } from '@/shared/geo';

export interface LocationMapProps {
  label: string;
  markerLabel: string;
  point: GeoPoint | null;
  onPick: (point: GeoPoint) => void;
}
