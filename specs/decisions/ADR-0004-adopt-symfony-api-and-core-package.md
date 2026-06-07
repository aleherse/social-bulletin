# ADR-0004: Adopt Symfony API And Core Package

- Status: Accepted
- Date: 2026-05-13

## Context

The project needs an executable PHP API and a framework-free place for domain and application code. Framework, HTTP, and infrastructure concerns must stay outside core business code.

Symfony 7.4 is the current long-term support release and requires PHP 8.2 or higher. A Symfony application can provide the HTTP and framework adapter boundary, while a separate Composer package can hold future domain and application code without depending on Symfony.

API contracts must be machine-readable from controllers without a separate documentation workflow.

## Decision

Adopt a minimal Symfony application under `apps/api` and a separate framework-free core package under `packages/core`.

Create the Symfony application with Composer using the Symfony Skeleton package pinned to the latest stable Symfony release branch:

```sh
composer create-project symfony/skeleton:"7.4.x-dev" apps/api
```

The Symfony application owns HTTP controllers, framework configuration, public runtime files, and API-level tests. The core package owns future domain and application code and must not depend on Symfony, HTTP, containers, or application-specific infrastructure.

Install `nelmio/api-doc-bundle` (v5) and `zircote/swagger-php` (v6) in the API application. Use the default NelmioApiDocBundle configuration to expose the OpenAPI specification as a JSON document only. All controllers MUST document their routes using `OpenApi\Attributes` PHP 8 attributes. The Swagger UI is not enabled because the application has no Twig dependency.

Install `symfony/monolog-bundle` (v3) in the API application. Configure it to emit structured JSON log events to `stderr` in all environments. In development, log at `debug` level. In production and test, use a `fingers_crossed` handler that buffers until an error-level event occurs. Event and Doctrine channels are excluded from the main handler in development to reduce noise.

Install and use these libraries:
- `nelmio/cors-bundle` to send Cross-Origin Resource Sharing headers.
- `symfony/uid` to generate unique identifiers based on `Uuid::v7()`.
- `webmozart/assert` to validate method input/output with nice error messages.

Local development, dependency installation, and test execution must run through Docker Compose and root Makefile targets.

Composer must be installed in the API PHP container by copying the binary from the official `composer` Docker image in a multi-stage Docker build.

The API must be served by nginx as reverse proxy in front of PHP-FPM. In local development it must be reachable at `API_URL`, mapped to `127.0.0.1` in `/etc/hosts`. nginx runs in Docker, not on host.

A `make console` target must be added to the root Makefile to execute Symfony console commands (`bin/console`) inside the API container. Developers must not need to know the container name or Docker Compose syntax to run Symfony commands.

## Consequences

- Provides an executable API foundation created from the Symfony Skeleton distribution for the selected stable Symfony branch without requiring host PHP or Composer.
- Uses the official Composer image as the source of the Composer binary, reducing custom installer logic in the PHP Dockerfile.
- Keeps framework glue isolated from future business rules.
- Makes API and core test boundaries explicit from the first runtime commit.
- API contracts are machine-readable and discoverable via `/doc.json` from the first endpoint.
- OpenAPI attributes co-locate documentation with the route, reducing drift between code and spec.
- Structured JSON logs on `stderr` integrate cleanly with Docker log aggregation and local `make logs`.
- nginx reverse proxy matches a production-like topology and avoids exposing PHP-FPM directly.
- Adds Composer path repository wiring between the app and core package.
- Adds container setup before any product domain behaviour exists.
- Requires maintaining Symfony configuration and Docker runtime files as the API grows.
- The PHP Dockerfile must track the chosen official Composer image tag when Composer upgrades are needed.
- Developers must annotate new endpoints with `OpenApi\Attributes`; undocumented routes will silently appear without descriptions.
- Monolog configuration must be maintained per environment; missing or misconfigured handlers will suppress log output silently.
- Local HTTPS requires a one-time `/etc/hosts` entry and trusted certificate setup per developer machine.
- nginx configuration must be kept in sync with PHP-FPM container naming as the compose stack evolves.
