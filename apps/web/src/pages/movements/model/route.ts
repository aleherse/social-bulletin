import { useSyncExternalStore } from 'react';

export type MovementsRoute =
  | { view: 'list' }
  | { view: 'new' }
  | { view: 'detail'; id: string }
  | { view: 'edit'; id: string };

function subscribe(onChange: () => void) {
  window.addEventListener('hashchange', onChange);

  return () => {
    window.removeEventListener('hashchange', onChange);
  };
}

export function useMovementsRoute(): MovementsRoute {
  const hash = useSyncExternalStore(subscribe, () => window.location.hash);
  const segments = hash.replace(/^#\/?/, '').split('/').filter(Boolean);

  if (segments[1] === 'new') {
    return { view: 'new' };
  }

  if (segments[1] !== undefined) {
    if (segments[2] === 'edit') {
      return { view: 'edit', id: segments[1] };
    }

    return { view: 'detail', id: segments[1] };
  }

  return { view: 'list' };
}

export function goToMovements() {
  window.location.hash = '#/movements';
}
