# ADR-0006: Adopt React, Vite, npm, And Feature-Sliced Design

- Status: Accepted
- Date: 2026-05-14

## Context

The project now needs a browser-facing web application in the existing monorepo. Existing decisions require deployable applications to live under `apps/`, local tooling to run through Docker Compose, and common developer workflows to remain behind root Makefile targets.

The frontend needs a small application skeleton that can compile quickly, be served as static assets through nginx, and grow without collapsing into screen-first or framework-first coupling. Future UI behaviour should have clear boundaries so browser adapters, shared UI code, and product capabilities can evolve independently.

## Decision

Adopt React for the web application's UI layer.

Adopt Vite as the frontend build tool and Vitest-compatible project foundation because it provides fast TypeScript and React builds with a small configuration surface. Use `public` folder for static assets copied as-is into the build output (including `index.html`). Vite's development server is enabled with `server.host: true` so the container can accept connections from the host. The `dev-web` Makefile target starts the Vite dev server with hot-module replacement on port 3000 by publishing the container port to the host. The `DEV_ALLOWED_HOST` environment variable controls Vite's `server.allowedHosts` so the dev server accepts requests arriving under a custom local hostname (default `FRONTEND_URL`) without hardcoding it in the configuration file.

Adopt npm as the package manager for the web application. npm commands must run through the Docker Compose Node service and root Makefile targets, not through a required host-level Node.js installation. The Node tooling image should install the latest stable npm during image build so local and CI package-manager behaviour does not depend on the npm version bundled with the base Node image.

The compiled React application must be served in local development via an nginx container at `FRONTEND_URL`. Developers must add the `FRONTEND_URL` hostname to their `/etc/hosts` file pointing to `127.0.0.1`; this step must be documented in the project README or onboarding guide. The nginx container serves the Vite build output as static files; the Vite dev server remains available separately via the `dev-web` target for hot-module replacement during active frontend development.

Structure the web source using Feature-Sliced Design. The initial application should create only layers that contain real code, starting with `app` for bootstrap/startup orchestration and `shared` for reusable API/config code. Empty layers such as `entities`, `features`, `widgets`, or `pages` should not be created until they have concrete behaviour.

Install and use these libraries:
 - `@tanstack/react-query` for asynchronous state management, server-state utilities and data fetching.

## Consequences

Positive outcomes:

- Provides a standard browser UI foundation under the monorepo application boundary.
- Keeps frontend dependency installation, tests, and builds reproducible through Docker Compose.
- Gives future frontend work a clear source organisation model without adding premature empty structure.
- Keeps the web application as an adapter boundary, separate from API and core package code.
- The dev server with hot-module replacement allows rapid frontend iteration without rebuilding static assets.
- nginx serving the compiled build matches the production topology and catches asset-path or routing issues early.

Tradeoffs:

- The repository now has a second package-management ecosystem and lockfile to maintain.
- `make init` and full test workflows now include frontend dependency and build work, increasing setup time.
- The Node tooling image must be rebuilt to pick up newer npm releases.
- Vite environment variables are build-time inputs, so changes to frontend runtime configuration require rebuilding compiled assets.
- The dev server bypasses nginx and serves assets directly; production behaviour must still be verified against the compiled build.
- Feature-Sliced Design requires discipline to avoid over-creating layers or placing product behaviour in shared code.
- Self-signed certificate requires a one-time `/etc/hosts` entry and optional local trust-store setup on each developer machine.
- Two nginx containers (API and web) must be kept in sync with their respective upstream services as the compose stack evolves.

Follow-ups:

- Add frontend linting rules when the first non-trivial UI code is introduced.
- Add page, feature, entity, or widget layers only when concrete behaviour needs them.
- Keep frontend behaviour covered by Vitest and Testing Library at the smallest useful boundary.
- Document the `FRONTEND_URL` `/etc/hosts` entry and optional trust-store setup alongside the API equivalent in the project README or onboarding guide.
