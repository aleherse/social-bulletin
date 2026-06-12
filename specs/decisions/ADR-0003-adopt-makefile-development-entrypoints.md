# ADR-0003: Adopt Makefile Development Entrypoints

- Status: Accepted
- Date: 2026-05-11

## Context

Developers need one stable, discoverable command entrypoint for local workflows across apps, packages, and services.

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

When adding a tool, service, or runtime, add a Make target if developers need to start, stop, test, build, shell into, or inspect it. 

All existing and future entries SHALL be documented with `make help` by reading the existing rules and displaying a short description.

## Consequences

- Common workflows use one command surface.
- `make help` keeps commands discoverable.
- Docker Compose details stay hidden behind Make targets.
- New developer-facing tools need matching targets.
- The Makefile must stay thin and maintained.
