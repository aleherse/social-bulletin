import { useCallback, useEffect, useRef, useState } from 'react';

import { lookUpPlaceName } from '@/entities/movement';
import type { Area } from '@/entities/movement';
import type { GeoPoint } from '@/shared/geo';

export type PlaceLookupStatus = 'idle' | 'pending' | 'not-found' | 'unavailable';

/**
 * One lookup per pin: a new pin or unmounting abandons the one in flight,
 * whose answer is then ignored even if it still arrives.
 */
export function usePlaceLookup({ onFound }: { onFound: (name: string) => void }) {
  const [status, setStatus] = useState<PlaceLookupStatus>('idle');
  const inFlight = useRef<AbortController | null>(null);
  const onFoundRef = useRef(onFound);

  useEffect(() => {
    onFoundRef.current = onFound;
  }, [onFound]);

  useEffect(
    () => () => {
      inFlight.current?.abort();
    },
    [],
  );

  const lookUp = useCallback(async (point: GeoPoint, area: Area) => {
    inFlight.current?.abort();
    const controller = new AbortController();
    inFlight.current = controller;
    setStatus('pending');

    try {
      const name = await lookUpPlaceName(point, area, controller.signal);

      if (controller.signal.aborted) {
        return;
      }

      if (name === null) {
        setStatus('not-found');
        return;
      }

      setStatus('idle');
      onFoundRef.current(name);
    } catch {
      if (!controller.signal.aborted) {
        setStatus('unavailable');
      }
    }
  }, []);

  return { status, lookUp };
}
