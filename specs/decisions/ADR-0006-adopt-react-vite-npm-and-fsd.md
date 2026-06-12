# ADR-0006: Adopt React, Vite, npm, And Feature-Sliced Design

- Status: Accepted
- Date: 2026-05-14

## Context

The project needs a typed browser app with fast local feedback, containerised tooling, and clear frontend boundaries.

## Decision

Frontend app SHALL be installed by Vite via `npm create vite@latest apps/web -- --template react-ts` to use React and TypeScript.

Docker Node container `CMD` SHALL serve the Vite development environment.

Docker entrypoint SHAL install the dependencies if not already installed.

Vite SHALL serve `DEV_FRONT_URL` on port 3000.

Frontend code SHALL be structured using Feature-Sliced Design and its installed skill. Layers SHALL be added only as they are required.

These JavaScript packages SHALL be installed:
- `@tanstack/react-query` for server state and data fetching.

## Consequences

- React and TypeScript become the frontend foundation.
- Vite provides fast development and build workflows.
- npm dependencies run through the Node container.
- Feature-Sliced Design guides frontend structure.
- Layers are added only when real code needs them.
- Server state uses TanStack Query.
- Frontend builds, dependencies, and FSD boundaries need ongoing maintenance.
