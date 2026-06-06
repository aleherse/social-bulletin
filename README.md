# SocialBulletin

SocialBulletin is a Docker-first monorepo containing a Symfony API, framework-free PHP core package, React web app, PostgreSQL database, and shared nginx ingress.

## Walking Skeleton Authentication

The current registration flow is an email-only walking skeleton for development and pre-production integration work.

It lets any browser submit an email address, creates the user if needed, and issues a JWT in a secure `httpOnly` cookie. This proves browser-to-frontend-to-API-to-database-to-cookie integration only.

Do not treat this as production-grade authentication. Before protecting real user data, replace or harden it with a stronger flow covered by a new ADR, such as passwords, magic links, OAuth, email verification, logout, token refresh, account recovery, or revocation.

## Local HTTPS

Secure JWT cookies require HTTPS in local development.

Run `make certs` to generate local certificates with mkcert inside Docker. Certificates are written to `docker/nginx/certs/` and mounted into nginx and the Vite dev server.

Add these hosts to your machine's hosts file:

```text
127.0.0.1 api.bulletin.local app.bulletin.local
```

Linux certificate trust:

```sh
docker compose run --rm mkcert mkcert -install
```

Windows certificate trust:

Run the same command from a shell with access to Docker Desktop. If the browser still does not trust the certificate, import the generated mkcert root certificate into the Windows Trusted Root Certification Authorities store.

## API Localisation

The API negotiates locale from the `Accept-Language` request header. It resolves the browser preference against supported locales and falls back to `en` when no supported locale is found.

Backend translation catalogues use ICU YAML files under `apps/api/translations/` with bounded-context domains:

- `validators+intl-icu.<locale>.yaml`
- `notifications+intl-icu.<locale>.yaml`
- `errors+intl-icu.<locale>.yaml`

## Testing Setup

Run `make db` before the first test run and whenever migrations or fixture data change. This creates the database, runs migrations, loads fixture scenarios, and creates the DSLR `fixtures` snapshot.

`make tests` runs backend API tests, frontend unit/component tests, and Playwright E2E tests through Docker. Test execution expects the DSLR snapshot to already exist; it does not recreate the snapshot.

## Quality Gates

Run `make checks` for the deterministic local quality gate. It runs PHP dependency analysis, PHP static analysis, PHP coding-standard checks, translation linting, TypeScript type checking, ESLint, Knip, and Prettier checks.

Hook-safe targets are available for Git hooks:

- `make hook-pre-commit` runs fast formatting, linting, type, and coding-standard checks.
- `make hook-pre-push` runs medium-cost scanner and unit-test checks.

## AWS Deployment Migrations

AWS database migrations are an explicit deployment step. They must be run through `deploy/aws/scripts/run-migrations.sh <environment>` or an equivalent one-off Lambda deployment step. Migrations must not run during normal application requests.

## AWS Environments

AWS deployment uses CDK under `deploy/aws` with environment-scoped stacks for `preview`, `live`, and future named preview environments.

Resource names include the environment, for example `socialbulletin-preview-backend` and `socialbulletin-live-database`.

Configuration and secrets use SSM Parameter Store paths scoped by environment:

- `/socialbulletin/preview/...`
- `/socialbulletin/live/...`
- `/socialbulletin/preview-x/...`

CloudFront sends `x-socialbulletin-origin-secret` to the backend Lambda Function URL. The API rejects requests when `ORIGIN_SECRET` is configured and the header does not match.

Deployments use GitHub OIDC roles named `socialbulletin-<environment>-deployment`. `live` deployments must use GitHub Environment protection before the workflow is allowed to run.
