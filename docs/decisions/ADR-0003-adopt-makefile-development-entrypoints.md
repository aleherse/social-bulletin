# ADR-0003: Adopt Makefile Development Entrypoints

- Status: Accepted
- Date: 2026-10-03

## Context

Developers need one stable, discoverable command entrypoint for local workflows across apps, packages, and services.

## Decision

Adopt a root-level `Makefile` as the canonical developer command entrypoint.

The `Makefile` delegates execution to Docker Compose and containers,
so developers need only `make`, Docker, and Docker Compose or an equivalent container runtime.
Language runtimes, package managers and database tools SHALL NOT be run on the host.

Every target SHALL carry a `## ` description on its rule line,
which `make help` discovers at runtime and displays.
`make` with no target SHALL print `make help`.

Targets SHOULD be grouped into sections, each introduced by a `## ---` banner:

- Setup and daily use:
  - `make init` SHALL prepare or update everything from scratch, idempotently,
    by running the other setup targets in order.
    Each step SHOULD also be its own target,
    e.g. local environment files from versioned templates, images, dependencies, builds, the database and git hooks.
  - `make up` SHALL start the stack without building, wait until it is healthy, and print its addresses;
    `make down` SHALL stop it.
  - Targets to inspect and enter the stack, such as `ps`, `logs`, `shell`, and framework consoles,
    taking the service or command as a variable (`service=...`, `cmd=...`).
- Focussed checks:
  - One target per linter and per test suite.
  - Aggregate targets, such as `make lint` and `make tests`, to run them all.
- The full gate, when the project has one:
  - `make ci` to run every check the merge gate requires, with the same verdict locally and in CI.
- Housekeeping:
  - `make clean` for safe removal of recreated local artefacts and dependencies.
  - `make destroy` to delete all containers, volumes and artefacts.

Focussed checks SHOULD accept paths from the repository root to narrow their scope,
e.g. `FILES="..."` for linters and `PATHS="..."` for test suites.
Each check keeps the files it understands, strips the prefix its container works from,
and reports that it skipped when none are left.
Checks that only make sense over the whole project SHALL ignore the narrowing and say so in their description.

Targets that run others in sequence SHALL do so through one sub-make per target,
so their order holds under `make -j` too.

Targets should stay thin and intention-revealing.
If a command becomes complex, the Makefile may delegate to versioned scripts,
but the Makefile remains the primary public interface for local developer workflows.

When adding a tool, service, or runtime, add a Make target if developers need to start, stop, test, build, shell into, or inspect it.
A new linter or suite SHALL also join the aggregate targets and, when there is one, the gate.

## Consequences

- Common workflows use one command surface.
- `make help` keeps commands discoverable.
- Docker Compose details stay hidden behind Make targets.
- Git hooks and agents can run a narrow check on changed files with the same targets the gate runs.
- New developer-facing tools need matching targets.
- The Makefile must stay thin and maintained.
