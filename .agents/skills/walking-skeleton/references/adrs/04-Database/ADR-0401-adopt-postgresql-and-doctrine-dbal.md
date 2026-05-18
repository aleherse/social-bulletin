# ADR-0401: Adopt PostgreSQL And Doctrine DBAL

- Status: Accepted
- Date: 2026-06-12

## Context

The application needs durable relational storage with versioned schema changes and containerised local development.

## Decision

A container with the latest stable release of PostgreSQL SHALL be added to Docker compose.

These Symfony packages SHALL be installed:
- `doctrine/dbal` to connect to the database, configured via an environment variable.
- `doctrine/doctrine-migrations-bundle` for versioned schema management.

Migration files SHALL be generated via `php bin/console doctrine:migrations:generate` and not directly, migrations SHALL use the DBAL connection directly via raw SQL.

Doctrine DBAL and migrations SHALL use the schema `DATABASE_SCHEMA`.

## Consequences

- PostgreSQL becomes the primary data store.
- Schema changes are tracked through Doctrine migrations.
- Database access uses DBAL, not an ORM model layer.
- Local development needs the PostgreSQL container running.
- Migrations must be reviewed because they use raw SQL.
- Schema naming must stay consistent through `DATABASE_SCHEMA`.
