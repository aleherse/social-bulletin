# ADR-0002: Adopt Docker-Based Development

- Status: Accepted
- Date: 2026-05-11

## Context

The project will use multiple runtimes and services: PHP, React, nginx, PostgreSQL, browser testing, and future tools. Host installs would increase setup drift between developers and CI. Development needs reproducible containers with predictable startup.

## Decision

Adopt Docker-based development. Only Docker, Docker Compose, or an equivalent container runtime is required on the host for project build, run, test, lint, and operations.

All project technologies must be introduced through containers. As capabilities are added, the required services should be added to a Docker Compose file, including but not limited to:

- PHP runtime and tooling
- Playwright browser testing environment
- a single nginx web server or reverse proxy shared by all local applications
- React development/build environment
- Any database, queue, cache, worker, CLI, or other runtime introduced later

Project commands must run through Docker Compose so developers do not need host language runtimes, package managers, browsers, or daemons.

Prefer official Docker images for executable tooling instead of host installs when practical. Host tools are acceptable when no suitable image exists, Docker is unavailable, or ergonomics require it.

The local stack uses one shared nginx container as public HTTP(S) entrypoint. Apps route by hostname and nginx server block, including `API_URL` for the API and `FRONTEND_URL` for the web app. New apps add nginx config to the shared service unless isolation is explicitly needed.

Container processes must run as the host user's UID and GID so that files created inside a container are owned by the host user. This must be achieved by passing the host user identity into containers at runtime (e.g. via `user: "${UID}:${GID}"` in the compose service or equivalent), not by mapping files as root and relying on post-hoc permission fixes. Images must be built to support non-root execution where this is required.

A git ignored compose override file should exist so developers could personalise ports and environment variables.

## Consequences

- Makes local development reproducible across machines.
- Reduces onboarding requirements to Docker plus repository checkout.
- Keeps host machines clean from project-specific runtimes and tools.
- Aligns local development, CI, and future deployment assumptions around containerized services.
- Makes technology additions explicit through versioned compose changes.
- Keeps local HTTP(S) ingress simple with one shared nginx entrypoint.
- Docker becomes a required dependency for development.
- Compose configuration must be maintained as the project grows.
- Shared nginx routing must be kept organised as more applications and hostnames are added.
- File permissions, bind mounts, networking, and container performance need active care.
- Debugging needs container-aware tooling and commands.
