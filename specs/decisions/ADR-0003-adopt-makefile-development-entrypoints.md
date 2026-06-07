# ADR-0003: Adopt Makefile Development Entrypoints

- Status: Accepted
- Date: 2026-05-11

## Context

The project uses containers for runtimes, services, databases, tests, and frontend tooling. Developers need one small command surface instead of memorising Docker Compose, package-manager, migration, and test commands.

## Decision

Adopt a root-level `Makefile` as the canonical developer command entrypoint.

The `Makefile` provides stable targets for common workflows and delegates execution to Docker Compose and containers. Developers need only `make`, Docker, and Docker Compose or equivalent container runtime.

The initial command surface should include:

- `make help` to list supported targets and their purpose (SHOULD discover targets at runtime).
- `make init` to prepare the local development environment, including starting required containers, database services, and other infrastructure, and ensuring default local environment variables are available from versioned templates.
- `make buid` to install all the package managers dependencies.
- `make up` to start the development stack without building containers.
- `make down` to stop the development stack.
- `make ps` to list running containers.
- `make logs` to inspect all service logs or a specific service.
- `make shell` or service-specific shell targets for interactive container access.
- `make tests` to run the full automated test suite through all configured test tools.
- `make clean` for safe removal of recreated local artefacts and dependencies.
- `make destroy` to delete all containers and artefacts.

Targets should stay thin and intention-revealing. If a command becomes complex, the Makefile may delegate to versioned scripts, but the Makefile remains the primary public interface for local developer workflows.

When adding a tool, service, or runtime, add a Make target if developers need to start, stop, test, build, shell into, or inspect it. Document new targets in `make help`.

## Consequences

- Gives developers one discoverable command surface for setup, development, testing, and diagnostics.
- Keeps Docker Compose details centralised and easier to change without updating every workflow note.
- Supports incremental tool additions without changing the onboarding model.
- Makes CI and local workflows easier to align by reusing the same high-level targets where practical.
- Preserves container-first execution by using Make targets as wrappers, not host runtime entrypoints.
- Make becomes part of the required local toolchain.
- Target names and semantics must be maintained carefully to avoid surprising developers.
- Cross-platform usage may require documented support for GNU Make-compatible environments.
- Very large workflows may need scripts behind the Makefile to avoid unreadable targets.
