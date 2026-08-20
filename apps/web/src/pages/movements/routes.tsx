import type { RouteObject } from '@/shared/routing';

import { MovementsLayout } from './ui/movements-layout.tsx';

/** ADR-0018: the page slice owns its paths; `app/routes.tsx` composes them. */
export const movementsRoutes: RouteObject = {
  path: 'movements',
  element: <MovementsLayout />,
  children: [
    {
      index: true,
      lazy: async () => ({ Component: (await import('./ui/movement-list.tsx')).MovementList }),
    },
    {
      path: 'new',
      lazy: async () => ({ Component: (await import('./ui/new-movement.tsx')).NewMovement }),
    },
    {
      path: ':id',
      lazy: async () => ({ Component: (await import('./ui/movement-detail.tsx')).MovementDetail }),
    },
    {
      path: ':id/edit',
      lazy: async () => ({ Component: (await import('./ui/edit-movement.tsx')).EditMovement }),
    },
  ],
};
