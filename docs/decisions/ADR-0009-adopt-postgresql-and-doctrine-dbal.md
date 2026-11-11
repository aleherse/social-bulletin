# ADR-0009: Adopt PostgreSQL And Doctrine DBAL

- Status: Accepted
- Date: 2026-06-12

## Context

The application needs durable relational storage with versioned schema changes and containerised local development.

## Decision

A PostgreSQL 17 container with the PostGIS extension installed SHALL be added to Docker compose,
built from `docker/postgres/Dockerfile` (`postgres:17` from the ECR mirror plus `postgresql-17-postgis-3`)
so its major version matches Aurora PostgreSQL 17 ([ADR-0016](ADR-0016-aws-serverless-deployment.md)).

The `postgis` extension SHALL be enabled by a migration (`CREATE EXTENSION IF NOT EXISTS postgis`),
not by the container, so the same migration enables it on Aurora
([ADR-0024](ADR-0024-use-osm-tiles-and-self-hosted-place-lookup.md)).

These Symfony packages SHALL be installed:
- `doctrine/dbal` to connect to the database, configured via an environment variable.
- `doctrine/doctrine-migrations-bundle` for versioned schema management.

Migration files SHALL be generated via `php bin/console doctrine:migrations:generate` and not directly, migrations SHALL use the DBAL connection directly via raw SQL.

Doctrine DBAL and migrations SHALL use the schema `DATABASE_SCHEMA`.

## Consequences

- PostgreSQL becomes the primary data store.
- Schema changes are tracked through Doctrine migrations.
- Database access uses DBAL, not an ORM model layer.
- Local development needs the PostgreSQL container running, built locally with PostGIS rather than pulled as-is.
- Migrations must be reviewed because they use raw SQL.
- Schema naming must stay consistent through `DATABASE_SCHEMA`.
