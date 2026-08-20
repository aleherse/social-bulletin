# ADR-0018: Adopt React Router for Frontend Routing

- Status: Accepted
- Date: 2026-08-17

## Context

Per [ADR-0007](ADR-0007-adopt-react-vite-npm-and-fsd.md), `apps/web` is a
React and Vite application structured with Feature-Sliced Design, with no
routing library.
Routing is hand-rolled twice over `window.location.hash`:
`app/router.tsx` picks the page, and `pages/movements/model/route.ts`
parses the hash a second time into a `list | new | detail | edit` union
that `MovementsPage` switches on internally.

Hash routing leaves the application without shareable or indexable URLs,
without a not-found route, without any way to guard a route, and without
a boundary to code-split on.

Real paths are already deployable.
Both the development and production frontends fall back to
`/index.html`, per
[ADR-0004](ADR-0004-adopt-nginx-to-serve-php-and-compiled-frontend.md)
and [ADR-0014](ADR-0014-aws-serverless-deployment.md), so serving history
URLs needs no infrastructure change.

Per [ADR-0008](ADR-0008-adopt-react-i18next-for-frontend-i18n.md), the
active language is detected from the browser and is absent from the URL,
so a translated page cannot be linked to in a specific language.

## Decision

### Router and URL scheme

`apps/web` SHALL depend on `react-router` at `^8.3.0`.

Routing SHALL use the data router API:
`createBrowserRouter` for the route table and `RouterProvider` to render
it.
Hash-based routing SHALL NOT be used.
`AppProviders` SHALL continue to wrap `RouterProvider`, so the i18n and
TanStack Query providers stay outside the router.

Every application URL SHALL carry the active locale as its first path
segment, in the form `/:lang/…`, for example `/en/movements/:id/edit`.

`shared/i18n` SHALL export the supported locale codes as a typed constant
and SHALL be the only source of that list.

The root path `/` SHALL redirect to the detected locale's home path.
Detection SHALL come from the existing
`i18next-browser-languagedetector` instance, falling back to the
configured `fallbackLng`.

A `:lang` segment that is not a supported locale SHALL render the
not-found route rather than redirecting, so a wrong locale is reported
rather than silently rewritten.

The `:lang` segment SHALL be authoritative for the rendered language.
The i18next detector SHALL be configured to read the path first, and the
application SHALL call `changeLanguage` when the segment changes, so the
URL and the rendered language cannot diverge.

### Route ownership under Feature-Sliced Design

Each page slice SHALL own its own paths and export them as a route object
fragment from its public API, for example `pages/movements/routes.tsx`
re-exported by `pages/movements/index.ts`.

Route fragments SHALL declare paths relative to their parent and SHALL
NOT contain the `:lang` segment.

`app/router.tsx` SHALL compose the fragments into a single
`createBrowserRouter` call under one `:lang` parent route, and SHALL be
the only module that calls `createBrowserRouter`.

The exported route table SHALL be importable on its own, without
`RouterProvider`, so tests can mount it in a memory router.

`apps/web/src/pages/movements/model/route.ts` SHALL be deleted.
`MovementsPage` SHALL NOT switch on a parsed view union; each view SHALL
be its own route component reached by its own path.

Route parameters SHALL be read with the router's `useParams`, never by
parsing `window.location`.

### Navigation and links

Slices SHALL NOT import `Link`, `NavLink`, or `useNavigate` from
`react-router` directly.
`shared/routing` SHALL wrap them and SHALL be the only import site for
`react-router` outside `app/`, mirroring the adapter boundary ADR-0008
established for `react-i18next`.

The `shared/routing` wrappers SHALL prefix the active `:lang` segment
automatically, so callers write locale-free paths such as
`/movements/:id` and never interpolate the language themselves.

Anchor elements pointing at in-application `#/…` hrefs SHALL be replaced
by these wrappers, so navigation does not reload the document.

### Not-found and error handling

The route table SHALL include a catch-all `*` route rendering a
not-found page.

The `:lang` parent route SHALL declare an `errorElement` rendering a
route error page, so an error thrown while rendering a route does not
blank the application.

Both pages SHALL take their text from `shared/i18n`.

### Route guarding

Routes requiring a signed-in user SHALL be wrapped by a guard component
in the `app` layer that reads the current session through the
`useCurrentUser` query hook.

The guard SHALL treat the session query's pending state as undecided and
SHALL render neither the route nor a redirect until it settles, so a
signed-in user is never bounced on a slow first load.

On a settled `null` session the guard SHALL redirect to the locale home
path, recording the attempted path so sign-in can return to it.

`useCurrentUser`, `useCreateSession`, and `useLogout` currently live in
`apps/web/src/pages/home/api/session.ts`.
They SHALL move to an `entities/session` slice and be reached through its
public API, because the `app` layer cannot import from `pages` under
Feature-Sliced Design.
The query key they share SHALL move with them, so the guard and the
sign-in form continue to read one cache entry.

All `/:lang/movements…` routes SHALL be guarded.

### Code splitting

Route fragments SHALL load their components through the route object's
`lazy` property, so each page becomes its own build chunk.

The not-found page, the route error page, and the guard SHALL NOT be
lazy, because they must be able to render when a chunk fails to load.

`RouterProvider` SHALL be given a `hydrateFallbackElement` so a pending
chunk shows the application's loading state rather than nothing.

### Testing

A shared test helper SHALL render a given path against the real route
table using `createMemoryRouter`, wrapped in the same providers the
application uses.

Component tests SHALL navigate by passing a path to that helper and SHALL
NOT assign to `window.location`.
A path that no route matches SHALL therefore fail the test that uses it.

Playwright specs SHALL navigate to real paths.

## Non-Goals

Route loaders and actions SHALL NOT be used for data fetching.
Server state remains with TanStack Query per ADR-0007, read through the
existing entity hooks, so there is exactly one way to fetch.
This ADR does not change how any request is made.

React Router framework mode is rejected.
Its file-based route convention and its own Vite plugin would take over
both the build configured by ADR-0007 and the `pages` layer's structure,
in exchange for capabilities this application does not need.

Search parameter state management, scroll restoration tuning, and
route-based prefetching are out of scope.

## Implementation Plan

### Affected paths

| Path                                                | Change                                                                   |
|-----------------------------------------------------|--------------------------------------------------------------------------|
| `apps/web/package.json`                             | add `react-router@^8.3.0`                                                |
| `apps/web/src/app/router.tsx`                       | replace hash logic with `createBrowserRouter`; export the route table    |
| `apps/web/src/app/providers/index.tsx`              | unchanged; `RouterProvider` renders inside it                            |
| `apps/web/src/app/guards/require-session.tsx`       | new guard component                                                      |
| `apps/web/src/shared/routing/`                      | new slice: `Link`, `NavLink`, `useNavigate` wrappers with locale prefix  |
| `apps/web/src/shared/i18n/i18n.ts`                  | add supported locale constant; put `path` first in detector order        |
| `apps/web/src/entities/session/`                    | new slice; receives the hooks from `pages/home/api/session.ts`           |
| `apps/web/src/pages/home/api/session.ts`            | deleted; imports repointed at `@/entities/session`                       |
| `apps/web/src/pages/movements/routes.tsx`           | new route fragment for list, new, detail, edit                           |
| `apps/web/src/pages/movements/model/route.ts`       | deleted                                                                  |
| `apps/web/src/pages/movements/ui/movements-page.tsx`| split per view; drop the view switch and the inline sign-in prompt       |
| `apps/web/src/pages/home/routes.tsx`                | new route fragment for the home path                                     |
| `apps/web/src/pages/not-found/`                     | new page slice for `*`                                                   |
| `apps/web/src/shared/lib/test/render-route.tsx`     | new `renderRoute(path)` helper over `createMemoryRouter`                 |
| `apps/web/e2e/movements.spec.ts`                    | `/#/movements` becomes `/en/movements`                                   |
| `apps/web/src/shared/i18n/locales/en/common.json`   | keys for not-found, route error, and loading states                      |

### Steps

1. Install `react-router@^8.3.0` through the Node container per
   ADR-0007.
2. Add the supported locale constant to `shared/i18n` and reorder the
   detector so the path segment wins over the browser.
3. Create `shared/routing` with the locale-prefixing wrappers and its
   public API.
4. Move the session hooks into `entities/session` and repoint
   `pages/home` at the new public API.
5. Write the per-page route fragments, splitting `MovementsPage` into one
   component per view.
6. Compose the fragments in `app/router.tsx` under the `:lang` route,
   with the redirect at `/`, the catch-all, the `errorElement`, and the
   guard on the movements subtree.
7. Replace every `#/…` anchor with a `shared/routing` wrapper.
8. Delete `pages/movements/model/route.ts`.
9. Add `renderRoute` and migrate the component tests onto it.
10. Update the Playwright specs to real paths.

### Patterns to follow

Route fragments are plain objects, not JSX, so `app/router.tsx` can
compose them without rendering.

Import direction stays Feature-Sliced: `app` imports from `pages`, never
the reverse, and no slice imports another slice's internals.

Translated strings continue to come from `shared/i18n`, including the new
not-found and error pages.

### Patterns to avoid

Do not import `Link`, `NavLink`, or `useNavigate` from `react-router`
inside a slice; import them from `shared/routing`.

Do not interpolate the language into a path at a call site; the
`shared/routing` wrappers add it.

Do not read `window.location` to work out the current route or its
parameters; use `useParams` and the router's location hook.

Do not add a `loader` or an `action` to a route fragment; fetching stays
in the TanStack Query entity hooks.

Do not call `createBrowserRouter` anywhere but `app/router.tsx`.

Do not put a page's paths in `app/router.tsx`; the page slice owns them
and exports a fragment.

Do not wrap a test component in its own `MemoryRouter` with an ad hoc
route; use `renderRoute` so the real route table is exercised.

Do not redirect on a pending session; only a settled `null` session
redirects.

## Verification

- [ ] `react-router` appears in `apps/web/package.json` at `^8.3.0`.
- [ ] `grep -rn "#/" apps/web/src apps/web/e2e` returns no
      in-application route href.
- [ ] `grep -rln "from 'react-router'" apps/web/src` lists only files
      under `src/app/` and `src/shared/routing/`.
- [ ] `apps/web/src/pages/movements/model/route.ts` no longer exists.
- [ ] `grep -rn "window.location" apps/web/src` returns no route parsing.
- [ ] Visiting `/` in a browser lands on `/en`.
- [ ] Visiting `/en/movements/:id/edit` directly, with a full page load,
      renders the edit view — proving the nginx and CloudFront fallbacks
      work with history URLs.
- [ ] Visiting `/xx/movements` renders the not-found page.
- [ ] Visiting `/en/nope` renders the not-found page.
- [ ] Visiting `/en/movements` while signed out redirects to `/en`, and
      signing in returns to `/en/movements`.
- [ ] The network panel shows a separate chunk fetched on first
      navigation to the movements subtree.
- [ ] `make tests` passes, with component tests calling `renderRoute`
      rather than setting `window.location.hash`.
- [ ] `make lint` passes.

## Consequences

- URLs become real paths, so movements can be linked, bookmarked, and
  shared.
- Every application URL carries a locale segment, and every link is built
  through `shared/routing` rather than written literally.
- The route table in `app/router.tsx` and the fragments in each page
  slice become the single description of the application's URL surface.
- The two hand-rolled hash parsers are gone, along with the duplicated
  `useSyncExternalStore` subscriptions.
- `MovementsPage` splits into one component per view, so no page
  component switches on a parsed view union.
- Guest handling for movements changes behaviour: the inline
  "Sign in to propose a movement." prompt in `MovementsPage` is replaced
  by a redirect to the locale home path, and the Playwright test covering
  that flow is rewritten against the redirect.
- Session hooks move out of `pages/home` into `entities/session`, which
  is where the `app` layer can legally reach them.
- Pages load as separate chunks, so the initial bundle no longer carries
  every page.
- `react-router` becomes a routing dependency to keep current, and the
  `shared/routing` adapter must be extended whenever a new router API is
  needed by a slice.
- Locale-prefixed URLs are adopted while `shared/i18n` ships a single
  locale, so the segment carries a cost before it carries a benefit; it
  is in place for the second locale rather than added afterwards.

## Revisit Triggers

Reconsider the locale segment if `shared/i18n` still ships one locale by
the time the application has a public, linkable URL surface.
The segment was adopted ahead of a second locale so that adding one is
not a rewrite of every path; if a second locale is abandoned rather than
delayed, the cost no longer buys anything and the segment should be
dropped in a superseding ADR.

Reconsider the ban on loaders and actions if route transitions become
slow enough to measure, since prefetching into the TanStack Query cache
from a loader is the next step available without abandoning ADR-0007.

Reconsider `shared/routing` as an adapter if it grows past thin wrappers
into routing logic of its own, which would mean the boundary is carrying
weight it was not meant to.

## More Information

- Extends [ADR-0007](ADR-0007-adopt-react-vite-npm-and-fsd.md), which
  established React, Vite, and Feature-Sliced Design, and which keeps
  server state in TanStack Query.
- Depends on the SPA fallback established by
  [ADR-0004](ADR-0004-adopt-nginx-to-serve-php-and-compiled-frontend.md)
  and implemented for production in
  [ADR-0014](ADR-0014-aws-serverless-deployment.md).
- Builds the locale segment on
  [ADR-0008](ADR-0008-adopt-react-i18next-for-frontend-i18n.md), and
  reuses its adapter boundary pattern for `shared/routing`.
- Guards read the session established by
  [ADR-0011](ADR-0011-adopt-lexik-jwt-authentication-bundle.md).
