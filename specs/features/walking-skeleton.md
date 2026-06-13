# Walking Skeleton

- Status: Implemented
- Date: 2026-06-13

## Summary

The walking skeleton establishes the full project structure, toolchain, and a working end-to-end registration flow that proves frontend, API, database, and cookie-based JWT authentication work together.

## What Is Implemented

### Infrastructure

- Monorepo with `apps/`, `docker/`, `infrastructure/`, `packages/`, `scripts/`, `specs/`
- Docker-based development: PHP 8.3, Node LTS, PostgreSQL 16 containers
- Root `Makefile` with discoverable targets for init, build, test, lint, deploy

### Backend (`apps/api`)

- Symfony 7.4 LTS API
- Doctrine DBAL + migrations (schema: `bulletin`)
- JWT authentication via `lexik/jwt-authentication-bundle` (httpOnly cookie, SameSite=Strict)
- `POST /api/register` — find-or-create user by email, issue JWT cookie
- `GET /api/me` — return `{id, email}` for authenticated user
- `POST /api/logout` — clear JWT cookie

### Core Package (`packages/core`)

- Framework-free `User` and `Email` value objects
- PHPSpec unit specs (7 passing examples)

### Frontend (`apps/web`)

- Vite + React + TypeScript
- Feature-Sliced Design structure
- shadcn/ui design system (Tailwind CSS v4)
- `RegistrationPage` — email form, calls `POST /api/register`
- `HelloPage` — greets user, calls `POST /api/logout`
- `App` — checks `GET /api/me` on mount, routes between views
- TanStack Query for data fetching
- react-i18next for internationalisation

### Testing

- PHPSpec: `packages/core` unit tests
- Behat: `apps/api` feature specs (registration, login, me, logout)
- Vitest: `apps/web` unit tests (3 passing)
- Playwright: `apps/web/e2e` end-to-end scenarios

### Quality Gates

- Lefthook: pre-commit (format, lint, type-check), commit-msg (Conventional Commits), pre-push (unit tests)
- GitHub Actions CI (opt-in per suite via PR checkboxes)
- GitHub Actions deploy (push to `live` → CDK deploy)

### AWS Deployment

- CDK TypeScript stack: S3 + CloudFront (frontend), Bref Lambda + CloudFront (API), Aurora Serverless v2, Route53, ACM, SSM
- Two environments: `live` and `preview`
