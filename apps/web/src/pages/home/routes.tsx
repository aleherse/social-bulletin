import type { RouteObject } from '@/shared/routing';

/** ADR-0018: the page slice owns its paths; `app/routes.tsx` composes them. */
export const homeRoutes: RouteObject = {
  index: true,
  lazy: async () => ({ Component: (await import('./ui/home-page.tsx')).HomePage }),
};
