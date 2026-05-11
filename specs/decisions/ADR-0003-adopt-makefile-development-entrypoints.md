# ADR-0003: Adopt Makefile Development Entrypoints

- Status: Accepted
- Date: 2026-05-11

## Context

The project has adopted a monorepo structure and Docker-based development. As runtimes, services, databases, test tools, frontend tooling, and other project capabilities are added, developers need a small and stable set of commands that hide low-level Docker Compose details without moving project execution back onto host machines.

Directly documenting many compose, package-manager, migration, and test commands would create drift and increase onboarding cost. A central command surface is needed so common development workflows stay discoverable, consistent, and easy to evolve as the project grows.

## Decision

Adopt a root-level `Makefile` as the canonical developer command entrypoint.

The `Makefile` should provide stable targets for common workflows while delegating actual execution to Docker Compose and project containers, in line with ADR-0002. Developers should not need host-level project runtimes beyond `make`, Docker, and Docker Compose or an equivalent container runtime.

The initial command surface should include:

- `make init` to prepare the local development environment, including starting required containers, database services, and other infrastructure, and ensuring default local environment variables are available from versioned templates.
- `make tests` to run the full automated test suite through all configured test tools.
- `make up` to start the development stack without reinitialising local defaults.
- `make down` to stop the development stack.
- `make logs` to inspect service logs.
- `make shell` or service-specific shell targets when interactive container access is useful.
- `make clean` for safe removal of generated local artefacts that can be recreated.
- `make help` to list supported targets and their purpose.

Targets should stay thin and intention-revealing. If a command becomes complex, the Makefile may delegate to versioned scripts, but the Makefile remains the primary public interface for local developer workflows.

## Consequences

Positive outcomes:

- Gives developers one discoverable command surface for setup, development, testing, and diagnostics.
- Keeps Docker Compose details centralised and easier to change without updating every workflow note.
- Supports incremental tool additions without changing the onboarding model.
- Makes CI and local workflows easier to align by reusing the same high-level targets where practical.
- Preserves the host-machine constraint from ADR-0002 by using Make targets as wrappers, not as host runtime execution points.

Tradeoffs:

- Make becomes part of the required local toolchain.
- Target names and semantics must be maintained carefully to avoid surprising developers.
- Cross-platform usage may require documented support for GNU Make-compatible environments.
- Very large workflows may need scripts behind the Makefile to avoid unreadable targets.

Follow-ups:

- Add the root `Makefile` when the first executable development workflow is introduced.
- Keep target names stable once published, or create a superseding ADR if the command model changes materially.
- Update `make help` whenever targets are added or removed.
- Ensure `make tests` expands to include each new testing tool as it is introduced.
- Ensure `make init` remains idempotent and safe to rerun.
- Propose this ADR to Airsync as team-scoped memory when Airsync memory tools are available.
