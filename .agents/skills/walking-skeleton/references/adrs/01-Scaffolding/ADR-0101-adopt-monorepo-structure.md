# ADR-0101: Adopt Monorepo Structure

- Status: Accepted
- Date: 2026-06-12

## Context

The repository needs one layout for deployable apps, shared packages, specs, ADRs, tooling, and generated artefacts. Keeping these in one repo makes cross-cutting changes, shared standards, and architecture boundaries easier to maintain.

## Decision

Adopt a monorepo with these top-level areas:

- `apps/` for deployable applications.
- `docker/` for container configuration when needed.
- `infrastructure/` for deployment scripts.
- `packages/` for shared libraries and reusable modules.
- `scripts/` for development/CI/CD scripts.
- `specs/` as the canonical specification and decision record location.

Each app or package SHALL keep ownership boundaries explicit. Shared packages SHALL NOT depend on deployable apps.

Root `.gitignore` SHALL be updated when adding an app, package, tool, generated output, build output, runtime files, caches, local env files, editor state, and temporary data.

## Consequences

- Provides one predictable repository layout for applications, shared packages, specifications, decisions, tooling, and deployment assets.
- Makes cross-boundary changes easier because related code and documentation can move in one commit.
- Keeps architectural decisions and living specifications close to the code they govern.
- Makes app and package ownership boundaries explicit from the start.
- Allows shared tooling, quality gates, and developer commands to be centralised at the repository root.
- Requires teams to maintain clear dependency direction so shared packages do not depend on deployable applications.
- Increases the importance of root-level ignore rules, generated output hygiene, and build artefact separation.
- Repository-wide checks may become slower as applications and packages are added, so affected-scope workflows may be needed later.
- Changes to shared packages can affect multiple deployable applications and therefore require broader impact review.
