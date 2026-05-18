# ADR-0002: Adopt Docker-Based Development

- Status: Accepted
- Date: 2026-06-12

## Context

The project uses several runtimes and services. Host installs would create setup drift between developers and CI. Development needs one reproducible way to build, run, test, and debug the system.

## Decision

Adopt Docker-based development. Only Docker, Docker Compose, or an equivalent container runtime is required on the host for project build, run, test, lint, and operations.

All project technologies SHALL be introduced through containers. As capabilities are added, the required services should be added to a Docker Compose file.

Container SHALL have an `entrypoint.sh` that installs related dependencies and `CMD` to the relevant service.

Project commands SHALL run through Docker Compose, via `docker compose exec` so developers do not need host language runtimes, package managers, browsers, or daemons.

When apps are required inside a container those SHALL be installed from official Docker images.

`gosu` SHALL be used to avoid issues with files ownership.

A git ignored compose override file SHALL exist so developers could personalise ports and environment variables.

## Consequences

- Local setup is reproducible and closer to CI.
- Developers need only Docker and the repository.
- Project runtimes stay off the host machine.
- New tools and services must be added through Compose.
- Docker becomes required for normal development.
- Compose files, permissions, networking, and performance need ongoing maintenance.
- Debugging must use container-aware commands and tooling.
