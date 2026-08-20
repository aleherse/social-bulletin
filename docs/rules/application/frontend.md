# Application / Frontend

## application-frontend-0001: External frontend libraries behind a shared adapter

**WHEN** code in `apps/web/src` outside the `app` layer needs an API from a third-party UI or
routing library (`react-router`, `react-i18next`, …)

**THEN** import it from the `shared/<concern>` slice that re-exports it, never from the library
itself. When the slice does not yet expose what you need, add the re-export to its public API —
including type-only exports — rather than reaching past it. The `app` layer is exempt: it owns
composition and may import the library directly.

**Example:**

| Wrong                                                                          | Right                                                 |
|----------------------------------------------------------------------------------|---------------------------------------------------------|
| `import { Link } from 'react-router'` in `pages/movements/ui/movement-row.tsx`  | `import { Link } from '@/shared/routing'`              |
| `import { useTranslation } from 'react-i18next'` in a page slice                | `import { useTranslation } from '@/shared/i18n'`       |
| `import type { RouteObject } from 'react-router'` in `pages/home/routes.tsx`    | `import type { RouteObject } from '@/shared/routing'`  |

## application-frontend-0002: Page slices own their routes, the app composes them

**WHEN** adding or changing a URL in `apps/web`

**THEN** declare the path in the page slice's own `routes.tsx`, as a `RouteObject` fragment exported
from that slice's public API, and splice it into `app/routes.tsx`. A page component must never
inspect the location to decide which view to render, and `createBrowserRouter` is called in
`app/router.tsx` and nowhere else.

**Example:**

| Wrong                                                                                       | Right                                                                         |
|-----------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------|
| `MovementsPage` switching on a `list \| new \| detail \| edit` union from `model/route.ts`   | one component per path, listed in `pages/movements/routes.tsx`                 |
| movement paths written directly in `app/routes.tsx`                                          | `movementsRoutes` exported by `pages/movements`, composed in `app/routes.tsx`   |
| `useSyncExternalStore` over `window.location.hash` in a slice                                | `useParams` from `@/shared/routing`                                            |

## application-frontend-0003: Route guards act on settled state, never on pending

**WHEN** a route guard decides access from an async query (session, permissions)

**THEN** treat a pending query as undecided: render a loading element while it is in flight, and
redirect only once it settles to "no access". Record the attempted path so the visitor returns to it
after signing in. A guard that redirects while the query is pending bounces an already signed-in
user on every slow first load.

**Example:**

| Wrong                                          | Right                                                  |
|--------------------------------------------------|----------------------------------------------------------|
| `if (!currentUser.data) return <Navigate … />`  | branch on `isPending` first, then on the settled `null`  |
| redirecting to the bare home path                | `?next=%2Fmovements`, consumed by `RegistrationForm`     |
