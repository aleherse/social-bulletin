# ADR-0001: Adopt Monorepo Structure

- Status: Accepted
- Date: 2026-05-10

## Context

The repository currently contains framework, agent, and specification governance files, but no application package structure yet. Before adding implementation code, the project needs a clear repository layout that can support multiple deployable applications, shared libraries, canonical specs, ADRs, and consistent standards.

A monorepo would make cross-cutting changes easier to coordinate, keep specs and implementation close together, and allow shared standards for testing, linting, architecture boundaries, and documentation.

## Decision

Adopt a monorepo structure for this project.

The repository should organise code into top-level workspace areas such as:

- `apps/` for deployable applications
- `packages/` for shared libraries and reusable modules
- `specs/` as the canonical specification and decision record location

Each application or package must keep ownership boundaries explicit. Code inside `packages/` SHOULD not depend on code inside `apps/`, the opposite is truth.

Workspace-level commands should be introduced only when implementation code exists and should match the chosen language/runtime stack.

Every time a new repository artifact is added, including an application, package, tooling configuration, generated output, or runtime-specific file set, the root `.gitignore` must be reviewed and updated with sensible ignore rules for that artifact. Ignore rules should cover generated files, build outputs, caches, local environment files, editor state, and tool-specific temporary data that should not be versioned.

## Consequences

Positive outcomes:

- Enables multiple apps and shared packages under one governed repo.
- Keeps specs, ADRs, and implementation decisions visible in one place.
- Supports consistent CI, linting, testing, and dependency policy.
- Reduces duplicated setup across future applications.

Tradeoffs:

- Requires clear package ownership to avoid accidental coupling.
- CI must be scoped carefully as project size grows.
- Workspace command conventions should be deferred until first real application/runtime is known.
- Each new artifact introduces a small maintenance step to keep repository hygiene explicit in the root `.gitignore`.

Follow-ups:

- Define initial workspace layout when first app/package is added.
- Add workspace-level lint/test/build commands once runtime stack is selected.
- Review and extend the root `.gitignore` whenever a new app, package, tool, or generated artifact is introduced.
- Update `specs/features/` when repo structure creates observable developer workflow behaviour.