# ADR-0005: Adopt PostgreSQL And Doctrine DBAL

- Status: Accepted
- Date: 2026-05-13

## Context
The API needs a relational database foundation for future persistence adapters. Existing architecture decisions require local services to run through Docker Compose and require framework or infrastructure details to stay outside the core package.

PostgreSQL is a mature relational database with strong transactional guarantees, broad operational support, and good compatibility with Doctrine DBAL. The latest stable PostgreSQL major release should be pinned explicitly in Docker Compose so local development is reproducible and does not drift through floating tags.

## Decision
Adopt PostgreSQL 18 for the local development database using the `postgres:18-alpine` container image.

Install Doctrine DBAL in the Symfony API application and configure its connection through DoctrineBundle using `DATABASE_URL`. The Symfony API owns this infrastructure configuration. The core package must remain independent from Doctrine, database drivers, and persistence infrastructure.

Adopt `doctrine/migrations` and `doctrine/doctrine-migrations-bundle` for versioned schema management. Migration files are generated via `php bin/console doctrine:migrations:generate` and must be edited to add raw SQL DDL before execution. The `up()` method applies the schema change; the `down()` method reverts it. No ORM mapping is introduced; migrations use the DBAL connection directly with raw SQL DDL.

## Consequences
Positive outcomes:

- Provides a reproducible local relational database service through Docker Compose.
- Keeps database connectivity in the API infrastructure boundary, consistent with the dependency rule.
- Gives future persistence adapters a standard DBAL connection without introducing ORM mapping decisions yet.

Tradeoffs:

- Local development now starts and maintains a PostgreSQL data volume.
- PHP runtime must include the PostgreSQL PDO extension.
- Future PostgreSQL major upgrades require explicit ADR or implementation review because the image tag is pinned.

Follow-ups:

- Migration files live under `apps/api/migrations/` with the `SocialBulletin\Api\Migrations` namespace.
- Keep repositories behind application ports before domain behaviour depends on persistence.
