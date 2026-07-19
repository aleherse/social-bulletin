import { useSyncExternalStore } from 'react';

import { HomePage } from '@/pages/home';
import { MovementsPage } from '@/pages/movements';

function subscribe(onChange: () => void) {
  window.addEventListener('hashchange', onChange);

  return () => {
    window.removeEventListener('hashchange', onChange);
  };
}

/** Minimal hash router: `#/movements…` renders the movements page, anything else the home page. */
export function AppRouter() {
  const hash = useSyncExternalStore(subscribe, () => window.location.hash);

  return hash.startsWith('#/movements') ? <MovementsPage /> : <HomePage />;
}
