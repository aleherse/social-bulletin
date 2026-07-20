# ADR-0005: Adopt Symfony API And Core Package

- Status: Accepted
- Date: 2026-06-12

## Context

The project needs an HTTP API and a framework-free place for domain and application logic.

## Decision

A minimal Symfony skeleton SHALL be installed under `apps/api` via `composer create-project symfony/skeleton apps/api` using the latest LTS version.

and a separate framework-free PHP core package under `packages/core`.

The Symfony application owns HTTP controllers, framework configuration, public runtime files, and API-level tests. The core package owns future domain and application code and must not depend on Symfony, HTTP, containers, or application-specific infrastructure.

These Symfony packages SHALL be installed:
- `nelmio/api-doc-bundle` to expose the OpenAPI specification as a JSON document only.
- `symfony/monolog-bundle` to emit structured JSON log events to `stderr` in all environments.
- `nelmio/cors-bundle` to send Cross-Origin Resource Sharing headers.
- `symfony/uid` to generate unique identifiers based on `Uuid::v7()`.
- `webmozart/assert` to validate method input/output with nice error messages.

CORS SHALL be configured to accept connections from `DEV_FRONT_URL`.

Docker PHP container `CMD` SHALL execute `php-fpm`.

Docker PHP entrypoint SHALL install the dependencies if not already installed.

A `make console` target SHALL be added to execute Symfony console commands inside the API container.

## Consequences

- Symfony owns HTTP, configuration, routing, and API tests.
- `packages/core` stays independent from Symfony and transport concerns.
- API documentation, CORS, logging, IDs, and assertions have standard packages.
- API commands run through Docker and `make console`.
- Developers must keep framework code out of the core package.
- Symfony configuration and package upgrades need ongoing maintenance.
