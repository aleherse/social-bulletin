# Implementation Plan: Propose a Movement

**Branch**: `001-propose-movement` | **Date**: 2026-07-19 |
**Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/001-propose-movement/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command.
See `.specify/templates/plan-template.md` for the execution workflow.

## Summary

Signed-in users create movement drafts (title, optional markdown
description, category, area, location), edit them, and submit them as
proposals (`draft` → `proposed`).
Domain logic (Movement aggregate, status transitions, stage-dependent
validation) lives in the framework-free `packages/core`;
`apps/api` exposes JSON endpoints over the existing JWT cookie session
and persists movements via Doctrine DBAL into the `bulletin` schema;
`apps/web` adds an FSD slice with a proposal form, a "my movements"
list, and safe markdown rendering.
Moderation, publication, and slugs are explicitly out of scope
(deferred to later specifications).

## Technical Context

**Language/Version**: PHP 8.3 (Symfony 7.4 LTS in `apps/api`,
framework-free `packages/core`); TypeScript + React 19 in `apps/web`

**Primary Dependencies**: Backend: `doctrine/dbal`,
`doctrine/doctrine-migrations-bundle`, `symfony/uid` (UUID v7),
`webmozart/assert`, `lexik/jwt-authentication-bundle` (existing
cookie session).
Frontend: Vite, `@tanstack/react-query`, shadcn/ui components,
`react-i18next`, a markdown renderer that never emits raw HTML
(see research.md).

**Storage**: PostgreSQL, schema `bulletin` (ADR-0009 / ADR-0000);
new tables `bulletin.categories` and `bulletin.movements` via a raw-SQL
Doctrine migration

**Testing**: PHPSpec for `packages/core`; Behat + SymfonyExtension with
JMESPath assertions for `apps/api` (new `movements.feature`);
Vitest + Testing Library for `apps/web` (ADR-0015)

**Target Platform**: Dockerised Linux dev environment (nginx + PHP-FPM
+ Node + PostgreSQL), AWS serverless deployment (ADR-0014)

**Project Type**: Web application (monorepo: `apps/api`, `apps/web`,
`packages/core`)

**Performance Goals**: Standard interactive web expectations;
draft creation flow completable in under 3 minutes (SC-001);
no specific throughput targets for this feature

**Constraints**: Hexagonal dependency rule — `packages/core` must not
depend on Symfony, HTTP, or DBAL;
FSD import rules in `apps/web`;
migrations written as raw SQL against schema `bulletin`;
JWT arrives only via the `token` httpOnly cookie

**Scale/Scope**: Single aggregate (Movement), one reference table
(categories), 4 API endpoints, one new frontend page slice;
early-stage user base, no pagination concerns yet beyond a simple list

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1
design.*

`.specify/memory/constitution.md` is still the unfilled template, so no
ratified constitution gates exist.
The accepted ADRs in `docs/decisions/` and the engineering docs act as
the governing constraints; this plan is checked against them:

| Gate (source)                                        | Status | Notes                                                              |
|------------------------------------------------------|--------|--------------------------------------------------------------------|
| Core stays framework-free (ADR-0005, hexagonal.md)   | PASS   | Movement aggregate + ports in `packages/core`, no Symfony/DBAL     |
| Behaviour lives in the aggregate (hexagonal.md)      | PASS   | Status transition + submit validation are `Movement` methods       |
| One repository per aggregate (hexagonal.md)          | PASS   | Single `MovementRepository` port; categories read via a query port |
| Raw-SQL migrations in schema `bulletin` (ADR-0009)   | PASS   | One migration, `CREATE TABLE` statements, no ORM                   |
| DBAL only, no ORM entities (ADR-0009)                | PASS   | `DbalMovementRepository` adapter in `apps/api`                     |
| Cookie-JWT auth, stateless firewall (ADR-0011)       | PASS   | Endpoints reuse the existing `token` cookie authentication         |
| FSD layering, layers only as needed (ADR-0007)       | PASS   | New `entities/movement` + page slice; no new layers beyond need    |
| Testing toolchain (ADR-0015)                         | PASS   | PHPSpec unit specs, Behat API feature, Vitest component tests      |
| UUID v7 identifiers (ADR-0005)                       | PASS   | Reuses `IdentityGenerator` port + `UuidV7IdentityGenerator`        |

**Post-design re-check (after Phase 1)**: PASS — the data model,
contracts, and structure below introduce no deviation from the gates
above; Complexity Tracking stays empty.

## Project Structure

### Documentation (this feature)

```text
specs/001-propose-movement/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
│   └── movements-api.md
└── tasks.md             # Phase 2 output (/speckit-tasks command)
```

### Source Code (repository root)

```text
packages/core/
├── src/
│   └── Movement/                 # SocialBulletin\Core\Movement
│       ├── Movement.php          # Aggregate: fields + submit() rules
│       ├── MovementStatus.php    # Enum: draft | proposed | published
│       ├── Area.php              # Enum: international … neighborhood
│       ├── MovementRepository.php # Port (save, byId, byAuthor)
│       ├── Categories.php        # Port to read the managed list
│       ├── MovementService.php   # Application service (create/edit/submit)
│       └── MovementNotFound.php  # + validation exceptions as needed
└── spec/
    └── Movement/
        ├── MovementSpec.php
        └── MovementServiceSpec.php

apps/api/
├── src/
│   ├── Controller/MovementController.php
│   └── Repository/
│       ├── DbalMovementRepository.php
│       └── DbalCategories.php
├── migrations/VersionXXXXXXXXXXXXXX.php   # categories + movements
├── features/movements.feature             # Behat API scenarios
└── config/                                # service wiring if needed

apps/web/src/
├── entities/movement/          # types, api hooks, markdown view
├── features/propose-movement/  # draft form + submit action
├── pages/movements/            # "my movements" list + editor page
└── shared/                     # existing ui kit, api client, i18n

db/
└── schema.dbml                 # DBML schema doc (created/updated
                                # during implementation)
```

**Structure Decision**: Follow the existing monorepo split (ADR-0001,
ADR-0005): domain and application logic in `packages/core`, grouped
per aggregate — all Movement code lives in `src/Movement/` under the
`SocialBulletin\Core\Movement` namespace (the existing flat `User`
files predate this convention and are not touched by this feature),
HTTP + persistence adapters in `apps/api`,
and an FSD slice set in `apps/web` (`entities/movement` for the model
and API access, `features/propose-movement` for the form behaviour,
`pages/movements` for routing-level composition).

## Complexity Tracking

No constitution/ADR violations to justify — table intentionally empty.
