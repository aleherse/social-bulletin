import { useState } from 'react';

import type { Area } from '@/entities/movement';
import { currentPosition, type GeoPoint, PositionDenied } from '@/shared/geo';
import { useTranslation } from '@/shared/i18n';
import { LocationMap } from '@/shared/map';
import { Button, ErrorBoundary } from '@/shared/ui';

import { type PlaceLookupStatus, usePlaceLookup } from '../model/use-place-lookup.ts';

type PositionStatus = 'idle' | 'pending' | 'denied' | 'unavailable';

const POSITION_MESSAGES: Partial<Record<PositionStatus, string>> = {
  denied: 'movements.form.map.positionDenied',
  unavailable: 'movements.form.map.positionUnavailable',
};

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
  const [position, setPosition] = useState<PositionStatus>('idle');
  const lookup = usePlaceLookup({ onFound: onPlaceFound });

  const pick = (picked: GeoPoint) => {
    setPosition('idle');
    setPoint(picked);
    onPin();
    void lookup.lookUp(picked, area);
  };

  const pickCurrentPosition = async () => {
    setPosition('pending');
    lookup.dismiss();

    try {
      pick(await currentPosition());
    } catch (error) {
      setPosition(error instanceof PositionDenied ? 'denied' : 'unavailable');
    }
  };

  const messageKey = POSITION_MESSAGES[position] ?? LOOKUP_MESSAGES[lookup.status];

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
      <Button
        type="button"
        variant="outline"
        className="self-start"
        disabled={position === 'pending'}
        onClick={() => void pickCurrentPosition()}
      >
        {t('movements.form.map.useMyLocation')}
      </Button>
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
