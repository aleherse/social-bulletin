import { Navigate } from 'react-router';
import type { RouteObject } from 'react-router';

import { homeRoutes } from '@/pages/home';
import { movementsRoutes } from '@/pages/movements';
import { NotFoundPage } from '@/pages/not-found';
import { DEFAULT_LOCALE, i18n, isSupportedLocale } from '@/shared/i18n';

import { RequireSession } from './guards/require-session.tsx';
import { AppLoading } from './ui/app-loading.tsx';
import { LocaleLayout } from './ui/locale-layout.tsx';
import { RouteError } from './ui/route-error.tsx';

function detectedLocale() {
  return isSupportedLocale(i18n.resolvedLanguage) ? i18n.resolvedLanguage : DEFAULT_LOCALE;
}

/**
 * ADR-0018: the whole URL surface, composed from the fragments each page slice
 * owns. Importable without `RouterProvider` so tests can mount it in memory.
 */
export const appRoutes: RouteObject[] = [
  {
    path: '/',
    hydrateFallbackElement: <AppLoading />,
    children: [
      // Every application URL carries its locale, so `/` resolves one first.
      { index: true, element: <Navigate to={`/${detectedLocale()}`} replace /> },
      {
        path: ':lang',
        element: <LocaleLayout />,
        errorElement: <RouteError />,
        children: [
          homeRoutes,
          { element: <RequireSession />, children: [movementsRoutes] },
          { path: '*', element: <NotFoundPage /> },
        ],
      },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
];
