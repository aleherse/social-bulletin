# ADR Catalog

Bundled architecture decisions for greenfield project planning. Categories are ordered by dependency — lower numbers should generally appear before higher numbers in `specs/decisions/`.

## 01-Scaffolding

| ID | Title | Summary | Owner |
|----|-------|---------|-------|
| ADR-0101 | Adopt Monorepo Structure | `apps/`, `packages/`, `specs/`, `docker/`, `infrastructure/`, `scripts/` layout | alejandrohernandez-aircury |
| ADR-0102 | Adopt Docker-Based Development | All build/run/test/lint through containers; host needs only Docker | alejandrohernandez-aircury |
| ADR-0103 | Adopt Makefile Development Entrypoints | Root `Makefile` as canonical dev command surface (`help`, `init`, `build`, …) | alejandrohernandez-aircury |
| ADR-0104 | Adopt nginx to Serve PHP And Compiled Frontend | nginx terminates `DEV_API_URL` / `DEV_FRONT_URL`; FastCGI to PHP-FPM; static SPA assets | alejandrohernandez-aircury |

## 02-Backend

| ID | Title | Summary | Owner |
|----|-------|---------|-------|
| ADR-0201 | Adopt Symfony API And Core Package | Symfony skeleton at `apps/api`; framework-free `packages/core`; OpenAPI + Monolog | alejandrohernandez-aircury |
| ADR-0202 | Adopt symfony/translation for Backend i18n | YAML catalogues under `apps/api/translations/` with ICU format | alejandrohernandez-aircury |

## 03-Frontend

| ID | Title | Summary | Owner |
|----|-------|---------|-------|
| ADR-0301 | Adopt React, Vite, npm, And Feature-Sliced Design | Vite React-TS at `apps/web`; FSD layers; TanStack Query | alejandrohernandez-aircury |
| ADR-0302 | Adopt react-i18next for Frontend i18n | Browser-side internationalisation aligned with backend domains | alejandrohernandez-aircury |

## 04-Database

| ID | Title | Summary | Owner |
|----|-------|---------|-------|
| ADR-0401 | Adopt PostgreSQL And Doctrine DBAL | PostgreSQL container; DBAL + migrations; schema `DATABASE_SCHEMA` | alejandrohernandez-aircury |

## 05-Design

| ID | Title | Summary | Owner |
|----|-------|---------|-------|
| ADR-0501 | Adopt shadcn/ui as the Frontend Design System | shadcn/ui + Tailwind; components in shared FSD UI area | alejandrohernandez-aircury |

## 06-Authentication

| ID | Title | Summary | Owner |
|----|-------|---------|-------|
| ADR-0601 | Adopt lexik/jwt-authentication-bundle and httpOnly Cookie JWT Delivery | JWT in httpOnly cookie; mkcert TLS for local dev; RSA keys via Lexik | alejandrohernandez-aircury |

## 07-Quality

| ID | Title | Summary | Owner |
|----|-------|---------|-------|
| ADR-0701 | Adopt Linting and Static Analysis Toolchain | PHPStan, PHPCS, ESLint, Prettier, deptrac, knip, etc. | alejandrohernandez-aircury |
| ADR-0702 | Development quality gates | Pre-commit / CI gates enforcing the lint toolchain | alejandrohernandez-aircury |

## 08-Infrastructure

| ID | Title | Summary | Owner |
|----|-------|---------|-------|
| ADR-0801 | AWS serverless deployment | CDK; S3 + CloudFront frontend; Bref Lambda API; Aurora Serverless; `live` / `preview` | alejandrohernandez-aircury |

## 09-Testing

| ID | Title | Summary | Owner |
|----|-------|---------|-------|
| ADR-0901 | Adopt Testing Toolchain | PHPSpec (core), PHPUnit (API), Playwright (browser), test DB fixtures | alejandrohernandez-aircury |

## Known implicit dependencies

These ADRs assume capabilities from other bundled ADRs even though they do not cite IDs explicitly:

| ADR | Assumes (recommended companions)       |
|-----|----------------------------------------|
| ADR-0103 | ADR-0102 (Docker)                      |
| ADR-0104 | ADR-0102, ADR-0201, ADR-0301           |
| ADR-0202 | ADR-0201                               |
| ADR-0302 | ADR-0301                               |
| ADR-0401 | ADR-0102, ADR-0201                     |
| ADR-0501 | ADR-0301                               |
| ADR-0601 | ADR-0102, ADR-0104, ADR-0201, ADR-0301 |
| ADR-0702 | ADR-0701                               |
| ADR-0801 | ADR-0101, ADR-0201, ADR-0301, ADR-0401 |
| ADR-0901 | ADR-0102, ADR-0201, ADR-0301, ADR-0401 |

## Documentation constants

Placeholders used across ADRs (full list and meanings in `constants-template.md`):

| Constant | Used in |
|----------|---------|
| `PROJECT_NAME`, `PROJECT_SLUG`, `PROJECT_NAMESPACE` | Template only (project identity) |
| `DATABASE_SCHEMA` | ADR-0401 |
| `DEV_API_URL`, `DEV_FRONT_URL` | ADR-0104, ADR-0201, ADR-0301 |
| `DEV_TLS_HOSTNAME` | ADR-0601 |
| `LIVE_API_URL`, `LIVE_FRONT_URL` | ADR-0801 |
