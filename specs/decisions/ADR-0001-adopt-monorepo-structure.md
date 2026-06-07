# ADR-0001: Adopt Monorepo Structure

- Status: Accepted
- Date: 2026-05-10

## Context

The repository needs one layout for deployable apps, shared packages, specs, ADRs, tooling, and generated artefacts. Keeping these in one repo makes cross-cutting changes, shared standards, and architecture boundaries easier to maintain.

## Decision

Adopt a monorepo with these top-level areas:

- `apps/` for deployable applications
- `packages/` for shared libraries and reusable modules
- `specs/` as the canonical specification and decision record location
- `docker/` for container configuration when needed
- `deploy/` for deployment scripts

Each app or package must keep ownership boundaries explicit. Shared packages must not depend on deployable apps.

Add workspace-level commands only when implementation code exists and the runtime stack is known.

When adding an app, package, tool, generated output, or runtime files, update root `.gitignore` for build output, caches, local env files, editor state, and temporary data.

## Consequences

- Enables multiple apps and shared packages under one governed repo.
- Keeps specs, ADRs, and implementation decisions visible in one place.
- Supports consistent CI, linting, testing, and dependency policy.
- Reduces host-machine tooling drift by preferring official containerized executables.
- Requires clear package ownership to avoid accidental coupling.
- CI must be scoped carefully as project size grows.
- Docker availability becomes more important for consistent local and CI tooling workflows.
- New artefacts require `.gitignore` review.
