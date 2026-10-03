# ADR-0002: Adopt Docker-Based Development

- Status: Accepted
- Date: 2026-10-03

## Context

The project uses several runtimes and services.
Host installs would create setup drift between developers and CI.
Development needs one reproducible way to build, run, test, and debug the system.

## Decision

Adopt Docker-based development.
Only Docker with Docker Compose and `make` are required on the host
to build, run, test, lint, and operate the project.

### Services and images

Every project technology SHALL be introduced as a Docker Compose service:

Docker Official Images SHALL be pulled from `public.ecr.aws/docker/library`, not Docker Hub,
whose anonymous pull limit fails parallel builds.

Each image's `CMD` SHALL start its service.
An image MAY have an `entrypoint.sh` for start-up work that depends on runtime state,
such as issuing or trusting the development certificates.
Entrypoints SHALL NOT install dependencies

### Running commands

The repository SHALL be bind-mounted at `/app`,
and project commands SHALL run through the Makefile,
never through host language runtimes, package managers, browsers, or daemons.
One-off commands use `docker compose run --rm`,
with `--no-deps` when they need no other service;
commands against the running stack (`make console`, `make shell`) use `docker compose exec`.

### File ownership

Containers that write the bind-mounted tree SHALL run as the host user that owns it,
fixed when the image is built rather than when the container starts:
the Dockerfile takes `UID` and `GID` build arguments,
creates (or renames an existing user to) `app` with them in its last layers,
and switches to it with `USER`.
Compose passes `HOST_UID` and `HOST_GID`, which the Makefile exports from whoever runs `make`,
so `docker compose run` and `docker compose exec` both act as that user.
Under a rootless engine the caller exports `0:0`

### Caches

Package manager caches SHALL live in named volumes owned by the image's user
(`composer-cache` at `/var/cache/composer`, `npm-cache` at `/var/cache/npm`),
so `docker compose run --rm` containers reuse downloads instead of starting cold.

### Host ports

Host ports SHALL be published only from `docker-compose.override.yml`,
which `make env` copies from the versioned `docker-compose.yml.dist` and git ignores,
Every port the template publishes SHALL come from an environment variable
with the conventional port as its default (e.g. `${POSTGRES_PORT:-5432}:5432`),
so developers personalise ports in `.env` without editing Compose files.
A versioned `.env.dist` SHALL list those variables with their defaults, and `.env` is git ignored.

### Startup and other Compose files

Services that others depend on SHALL declare a health check,
and dependants wait on `condition: service_healthy`,
so `make up` (`docker compose up --wait`) returns only once the stack serves requests.

## Consequences

- Local setup is reproducible and closer to CI.
- Developers need only Docker, `make` and the repository.
- Project runtimes stay off the host machine.
- New tools and services must be added through Compose.
- Docker becomes required for normal development.
- Files written from containers belong to the developer, on rootful and rootless engines alike.
- Images are built for one host identity; a different `HOST_UID` or `HOST_GID` needs `make images`.
- `make env` never overwrites `docker-compose.override.yml`,
  so changes to the template reach existing checkouts by hand.
- Compose files, permissions, networking, and performance need ongoing maintenance.
- Debugging must use container-aware commands and tooling.
