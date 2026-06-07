# ADR-0006: Adopt React, Vite, npm, And Feature-Sliced Design

- Status: Accepted
- Date: 2026-05-14

## Context

The project needs a browser-facing web app that compiles quickly, is served as static assets through nginx, and can grow without screen-first or framework-first coupling. Frontend code needs clear boundaries between app bootstrap, shared adapters, reusable UI, and product behaviour.

## Decision

Adopt npm as the package manager for the web application. npm commands must run through the Docker Compose Node service and root Makefile targets, not through a required host-level Node.js installation. The Node tooling image should install the latest stable npm during image build so local and CI package-manager behaviour does not depend on the npm version bundled with the base Node image.

Adopt Vite as the frontend build tool and Vitest-compatible project foundation because it provides fast TypeScript and React builds with a small configuration surface.

Create the web application with npm using the latest stable Vite scaffolder and the React TypeScript template. The command form is:

```sh
npm create vite@latest apps/web -- --template react-ts
```

Vite's development server is enabled with `server.host: true` so the container can accept connections from the host. The `dev-web` Makefile target starts the Vite dev server with hot-module replacement on port 3000 by publishing the container port to the host. The `DEV_ALLOWED_HOST` environment variable controls Vite's `server.allowedHosts` so the dev server accepts requests arriving under a custom local hostname, defaulting to `FRONTEND_URL`, without hardcoding it in the configuration file.

The compiled React application must be served in local development by nginx at `FRONTEND_URL`, mapped to `127.0.0.1` in `/etc/hosts`. nginx serves the Vite build output as static files. The Vite dev server remains available through `make dev-web` for hot-module replacement.

Structure the web source using Feature-Sliced Design. The initial application should create only layers that contain real code, starting with `app` for bootstrap/startup orchestration and `shared` for reusable API/config code. Empty layers such as `entities`, `features`, `widgets`, or `pages` should not be created until they have concrete behaviour.

Install and use `@tanstack/react-query` for server state and data fetching.

## Consequences

- Provides a standard browser UI foundation scaffolded from the latest stable Vite React TypeScript template under the monorepo application boundary.
- Keeps frontend dependency installation, tests, and builds reproducible through Docker Compose.
- Gives future frontend work a clear source organisation model without adding premature empty structure.
- Keeps the web application as an adapter boundary, separate from API and core package code.
- The dev server with hot-module replacement allows rapid frontend iteration without rebuilding static assets.
- nginx serving the compiled build matches the production topology and catches asset-path or routing issues early.
- The repository now has a second package-management ecosystem and lockfile to maintain.
- `make init` and full test workflows now include frontend dependency and build work, increasing setup time.
- The Node tooling image must be rebuilt to pick up newer npm releases.
- Vite environment variables are build-time inputs, so changes to frontend runtime configuration require rebuilding compiled assets.
- The dev server bypasses nginx and serves assets directly; production behaviour must still be verified against the compiled build.
- Feature-Sliced Design requires discipline to avoid over-creating layers or placing product behaviour in shared code.
