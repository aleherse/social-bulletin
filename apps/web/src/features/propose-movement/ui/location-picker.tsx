import { useState } from 'react';

import type { Area } from '@/entities/movement';
import type { GeoPoint } from '@/shared/geo';
import { useTranslation } from '@/shared/i18n';
import { LocationMap } from '@/shared/map';
import { ErrorBoundary } from '@/shared/ui';

import { type PlaceLookupStatus, usePlaceLookup } from '../model/use-place-lookup.ts';

const LOOKUP_MESSAGES: Partial<Record<PlaceLookupStatus, string>> = {
  'not-found': 'movements.form.map.placeNotFound',
  unavailable: 'movements.form.map.lookupFailed',
};

interface LocationPickerProps {
  area: Area;
  onPin: () => void;
  onPlaceFound: (name: string) => void;
}

/** The pinned point lives here only, while the form is open; nothing but the found name leaves (FR-005). */
export function LocationPicker({ area, onPin, onPlaceFound }: LocationPickerProps) {
  const { t } = useTranslation();
  const [point, setPoint] = useState<GeoPoint | null>(null);
  const lookup = usePlaceLookup({ onFound: onPlaceFound });

  const pick = (picked: GeoPoint) => {
    setPoint(picked);
    onPin();
    void lookup.lookUp(picked, area);
  };

  const messageKey = LOOKUP_MESSAGES[lookup.status];

  return (
    <div className="flex flex-col gap-2">
      <ErrorBoundary fallback={null}>
        <LocationMap
          label={t('movements.form.map.label')}
          markerLabel={t('movements.form.map.marker')}
          point={point}
          onPick={pick}
        />
      </ErrorBoundary>
      <p role="status" className="text-sm text-muted-foreground">
        {lookup.status === 'pending' ? t('movements.form.map.lookingUp') : null}
      </p>
      {messageKey !== undefined && (
        <p role="alert" className="text-sm text-destructive">
          {t(messageKey)}
        </p>
      )}
    </div>
  );
}
