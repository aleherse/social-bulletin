# ADR-0004: Adopt Symfony API And Core Package

- Status: Accepted
- Date: 2026-05-13

## Context

The repository has adopted a monorepo structure, Docker-based development, and Makefile entrypoints, but it does not yet contain an executable API application. The project needs a minimal PHP API runtime while preserving the architecture rule that framework and infrastructure code must not leak into the domain or application core.

Symfony 7.4 is the current long-term support release and requires PHP 8.2 or higher. A Symfony application can provide the HTTP and framework adapter boundary, while a separate Composer package can hold future domain and application code without depending on Symfony.

## Decision

Adopt a minimal Symfony 7.4 API application under `apps/api` and a separate framework-free core package under `packages/core`.

The Symfony application owns HTTP controllers, framework configuration, public runtime files, and API-level tests. The core package owns future domain and application code and must not depend on Symfony, HTTP, Doctrine, containers, or application-specific infrastructure.

Local development, dependency installation, and test execution must run through Docker Compose and root Makefile targets, consistent with ADR-0002 and ADR-0003.

## Consequences

Positive outcomes:

- Provides an executable API foundation without requiring host PHP or Composer.
- Keeps framework glue isolated from future business rules.
- Makes API and core test boundaries explicit from the first runtime commit.
- Aligns implementation with existing monorepo, Docker, and Makefile decisions.

Tradeoffs:

- Adds Composer path repository wiring between the app and core package.
- Adds container setup before any product domain behaviour exists.
- Requires maintaining Symfony configuration and Docker runtime files as the API grows.

Follow-ups:

- Keep controllers limited to transport adaptation and application use-case invocation as domain behaviour is added.
- Update canonical feature specs when this working change is completed.
- Propose this ADR to Airsync as team-scoped memory when Airsync memory tools are available.
