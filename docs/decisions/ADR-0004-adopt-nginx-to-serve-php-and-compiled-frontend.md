# ADR-0004: Adopt nginx to Serve PHP And Compiled Frontend

- Status: Accepted
- Date: 2026-06-14

## Context

The API runs as PHP-FPM and does not speak HTTP directly. The frontend is built to static assets that need a web server and SPA routing.

## Decision

An nginx container SHALL be added to Docker Compose using an official nginx image.

Versioned nginx configuration SHALL live in the repository and be mounted into the container.

nginx SHALL terminate HTTP for `DEV_API_URL` and `DEV_FRONT_URL`.

For `DEV_API_URL`, nginx SHALL forward PHP requests to the PHP-FPM container via FastCGI using the Symfony public front controller.

For `DEV_FRONT_URL`, nginx SHALL serve the compiled Vite frontend from the build output directory under `apps/web`.

The frontend location SHALL use `try_files` so client-side routes fall back to the SPA entry document.

The nginx container SHALL depend on the PHP-FPM and frontend build output being available before serving traffic.

A `make build` or equivalent target SHALL produce the frontend assets nginx serves.

During active frontend development, the Vite dev server MAY run separately for hot module replacement; nginx serves the compiled frontend for integrated local testing.

## Consequences

- nginx becomes the local HTTP entry point for API and frontend hostnames.
- PHP requests reach Symfony through FastCGI instead of a standalone web server in the PHP container.
- The compiled frontend is served as static files with SPA fallback routing.
- nginx configuration, volume mounts, and hostname routing need ongoing maintenance.
- Integrated local testing requires a frontend build before nginx can serve the UI.
- Hot reload during frontend work may still use the Vite dev server outside nginx.
