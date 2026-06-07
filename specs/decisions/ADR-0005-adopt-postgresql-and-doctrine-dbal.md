# ADR-0005: Adopt PostgreSQL And Doctrine DBAL

- Status: Accepted
- Date: 2026-05-13

## Context
The API needs a relational database foundation for persistence adapters. Database connectivity belongs in API infrastructure; domain and application core must remain database-framework-free.

PostgreSQL is a mature relational database with strong transactional guarantees, broad operational support, and good compatibility with Doctrine DBAL. The latest stable PostgreSQL major release should be pinned explicitly in Docker Compose so local development is reproducible and does not drift through floating tags.

## Decision
Adopt the latest stable release of PostgreSQL for the local development database using the `postgres:18-alpine` container image.

Install Doctrine DBAL in the Symfony API application and configure its connection through DoctrineBundle using `DATABASE_URL`.

Use PostgreSQL schema `DATABASE_SCHEMA` as the default application schema. Doctrine DBAL connections must set the default schema to `DATABASE_SCHEMA`, and migrations must create and target this schema rather than relying on PostgreSQL's `public` schema.

Adopt `doctrine/migrations` and `doctrine/doctrine-migrations-bundle` for versioned schema management. Migration files are generated via `php bin/console doctrine:migrations:generate`. The `up()` method applies the schema change; the `down()` method reverts it. No ORM mapping is introduced; migrations use the DBAL connection directly with raw SQL DDL.

## Consequences
- Provides a reproducible local relational database service through Docker Compose.
- Keeps database connectivity in the API infrastructure boundary, consistent with the dependency rule.
- Gives future persistence adapters a standard DBAL connection without introducing ORM mapping decisions yet.
- Separates application-owned database objects from PostgreSQL's default `public` schema.
- Local development now starts and maintains a PostgreSQL data volume.
- PHP runtime must include the PostgreSQL PDO extension.
- Future PostgreSQL major upgrades require explicit ADR or implementation review because the image tag is pinned.
- Schema lifecycle commands must ensure the `DATABASE_SCHEMA` schema exists before applying object-level migrations.
- Migration files live under `apps/api/migrations/` with the `PROJECT_NAMESPACE\Api\Migrations` namespace.
- Add a `db` Makefile target that creates the database and runs all migrations. This target owns the schema lifecycle and must be re-run whenever the schema changes.
- Configure local and application database connections so unqualified database objects resolve under the `DATABASE_SCHEMA` schema by default.
