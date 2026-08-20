import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router';

import { appRoutes } from '@/app/routes.tsx';
import { I18nProvider } from '@/shared/i18n';

/**
 * ADR-0018: renders a real path against the real route table, so a path no
 * route matches fails the test that uses it.
 *
 * Lives outside the FSD layers because it composes the `app` layer; putting it
 * in `shared` would invert the import direction.
 */
export function renderRoute(path: string) {
  const router = createMemoryRouter(appRoutes, { initialEntries: [path] });
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

  return render(
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <RouterProvider router={router} />
      </QueryClientProvider>
    </I18nProvider>,
  );
}
