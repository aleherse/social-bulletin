# ADR-0002: Adopt Docker-Based Development

- Status: Accepted
- Date: 2026-05-11

## Context

The project is expected to include multiple runtimes and tools over time, including PHP, Playwright, nginx, React, and other technologies that may be added later. Installing these directly on host machines would make local setup harder to reproduce, increase onboarding friction, and create drift between developer environments and CI.

The project needs a consistent development model where dependencies, runtimes, services, and tooling are isolated from the host machine and can be started in a predictable way.

## Decision

Adopt Docker-based development for this project.

Nothing required to build, run, test, lint, or operate project code should be installed directly on the host machine, except for Docker and Docker Compose or an equivalent container runtime capable of running the project compose stack.

All project technologies must be introduced through containers. As capabilities are added, the required services should be added to a Docker Compose file, including but not limited to:

- PHP runtime and tooling
- Playwright browser testing environment
- a single nginx web server or reverse proxy shared by all local applications
- React development/build environment
- Any database, queue, cache, worker, CLI, or other runtime introduced later

Project commands should be documented and executed through Docker Compose so developers do not need host-level language runtimes, package managers, browsers, or service daemons. Container images and compose service definitions should be kept close to the application or workspace they support.

The local development stack must use one shared nginx container as the public HTTP(S) entrypoint for all applications. Each app is routed by hostname and nginx server block, for example `api.bulletin.local` for the API and `app.bulletin.local` for the web app. New applications should add an nginx server block or included configuration to the shared nginx service rather than introducing another nginx container, unless a future ADR records a specific isolation requirement.

Container processes must run as the host user's UID and GID so that files created inside a container are owned by the host user. This must be achieved by passing the host user identity into containers at runtime (e.g. via `user: "${UID}:${GID}"` in the compose service or equivalent), not by mapping files as root and relying on post-hoc permission fixes. Images must be built to support non-root execution where this is required.

## Consequences

Positive outcomes:

- Makes local development reproducible across machines.
- Reduces onboarding requirements to Docker plus repository checkout.
- Keeps host machines clean from project-specific runtimes and tools.
- Aligns local development, CI, and future deployment assumptions around containerized services.
- Makes technology additions explicit through versioned compose changes.
- Keeps local HTTP(S) ingress simple by exposing one shared nginx entrypoint for all apps exposing port 80.

Tradeoffs:

- Docker becomes a required dependency for development.
- Compose configuration must be maintained as the project grows.
- Shared nginx routing must be kept organised as more applications and hostnames are added.
- File permissions, bind mounts, networking, and container performance need active care.
- Debugging may require container-aware tooling and documented commands.

Follow-ups:

- Add the initial Docker Compose file when the first runtime or service is introduced.
- Configure the initial nginx service as a shared reverse proxy/static server for all local applications.
- Document common development commands using Docker Compose.
- Ensure CI uses the same containerized assumptions where practical.
- Update future ADRs or specs when a new technology requires additional container conventions.
- Establish a convention for passing `UID`/`GID` into compose services (e.g. via `.env` or shell export) and document it so all contributors apply it consistently.
