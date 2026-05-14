# ADR-0006: Adopt React, Vite, npm, And Feature-Sliced Design

- Status: Accepted
- Date: 2026-05-14

## Context

The project now needs a browser-facing web application in the existing monorepo. Existing decisions require deployable applications to live under `apps/`, local tooling to run through Docker Compose, and common developer workflows to remain behind root Makefile targets.

The frontend needs a small application skeleton that can compile quickly, be served as static assets through nginx, and grow without collapsing into screen-first or framework-first coupling. Future UI behaviour should have clear boundaries so browser adapters, shared UI code, and product capabilities can evolve independently.

## Decision

Adopt React for the web application's UI layer.

Adopt Vite as the frontend build tool and Vitest-compatible project foundation because it provides fast TypeScript and React builds with a small configuration surface.

Adopt npm as the package manager for the web application. npm commands must run through the Docker Compose Node service and root Makefile targets, not through a required host-level Node.js installation. The Node tooling image should install the latest stable npm during image build so local and CI package-manager behaviour does not depend on the npm version bundled with the base Node image.

Structure the web source using Feature-Sliced Design. The initial application should create only layers that contain real code, starting with `app` for bootstrap/startup orchestration and `shared` for reusable API/config code. Empty layers such as `entities`, `features`, `widgets`, or `pages` should not be created until they have concrete behaviour.

## Consequences

Positive outcomes:

- Provides a standard browser UI foundation under the monorepo application boundary.
- Keeps frontend dependency installation, tests, and builds reproducible through Docker Compose.
- Gives future frontend work a clear source organisation model without adding premature empty structure.
- Keeps the web application as an adapter boundary, separate from API and core package code.

Tradeoffs:

- The repository now has a second package-management ecosystem and lockfile to maintain.
- `make init` and full test workflows now include frontend dependency and build work, increasing setup time.
- The Node tooling image must be rebuilt to pick up newer npm releases.
- Vite environment variables are build-time inputs, so changes to frontend runtime configuration require rebuilding compiled assets.
- Feature-Sliced Design requires discipline to avoid over-creating layers or placing product behaviour in shared code.

Follow-ups:

- Add frontend linting rules when the first non-trivial UI code is introduced.
- Add page, feature, entity, or widget layers only when concrete behaviour needs them.
- Keep frontend behaviour covered by Vitest and Testing Library at the smallest useful boundary.
- Propose this ADR to Airsync as team-scoped memory when Airsync memory tools are available.
