# Walking Skeleton tasks:

## Structure

- [x] [T01] [ADR-0001] Create monorepo directories `apps/`, `packages/`, `docker/`, `infrastructure/`, and `scripts/` with `.gitkeep` placeholders where empty
- [x] [T02] [ADR-0001] [ADR-0002] [ADR-0011] Create root `.gitignore` covering `vendor/`, `node_modules/`, `dist/`, `var/`, `.env.local`, `docker-compose.override.yml`, `docker/certs/`, `apps/api/config/jwt/`, coverage output, `.idea/`, and editor/temp files

## Setup

- [x] [T03] [ADR-0002] [ADR-0012] Create `docker/php/Dockerfile` from the official `php:fpm` image with Composer, `gosu`, `pdo_pgsql`, `intl`, and Xdebug (`xdebug.mode=debug,coverage,profile`, `start_with_request=trigger`, client host/port and output dir from `XDEBUG_*` env vars), plus `docker/php/entrypoint.sh` that installs Composer dependencies when missing and runs `php-fpm` as `CMD` via `gosu`
- [x] [T04] [ADR-0002] [ADR-0004] [ADR-0007] Create `docker/node/entrypoint.sh` for the official `node` image that installs npm dependencies when missing and builds the frontend into `apps/web/dist` when the build output is missing; container `CMD` runs the Vite dev server
- [x] [T05] [ADR-0002] [ADR-0004] [ADR-0011] Create `docker/nginx/Dockerfile` from the official `nginx` image with `mkcert` installed, and an entrypoint that runs `mkcert -install` once, issues `docker/certs/cert.pem` / `docker/certs/cert-key.pem` for `social.aleherse.com`, `localhost`, and `127.0.0.1` when missing, copies `rootCA.pem` into `docker/certs/`, and fixes file ownership
- [x] [T06] [ADR-0004] [ADR-0011] Create versioned nginx config in `docker/nginx/` that serves `dev.api.social.aleherse.com` over HTTPS with the mkcert certificate, redirects HTTP to HTTPS, forwards PHP requests to the PHP-FPM container via FastCGI using the Symfony front controller `apps/api/public/index.php`, and serves the compiled frontend for `dev.app.social.aleherse.com` from `apps/web/dist` with SPA `try_files` fallback
- [x] [T07] [ADR-0002] [ADR-0009] Create `docker-compose.yml` with services `php` (custom image, mounts repo), `node` (mounts repo and `docker/certs/`), `nginx` (custom image, depends on `php`), and `postgres` (latest official image, healthcheck, named volume), plus a versioned `docker-compose.override.yml.dist` template for personalised ports and env vars
- [x] [T08] [ADR-0003] Create root `Makefile` with targets `help` (runtime target discovery with descriptions), `init`, `build`, `up`, `down`, `ps`, `logs`, `shell`, `clean`, and `destroy`, all delegating to Docker Compose

## Modules

- [x] [T09] [ADR-0005] Install the latest Symfony LTS skeleton into `apps/api` via `docker compose run --rm php composer create-project symfony/skeleton apps/api`
- [x] [T10] [ADR-0005] Create framework-free package `packages/core` with its own `composer.json` (namespace `SocialBulletin\Core`, no Symfony/HTTP dependencies) and register it in `apps/api/composer.json` as a path repository
- [x] [T11] [ADR-0007] Install the Vite React TypeScript app into `apps/web` via `docker compose run --rm node npm create vite@latest apps/web -- --template react-ts`
- [x] [T12] [ADR-0007] [ADR-0011] Configure Vite to serve `dev.app.social.aleherse.com` on port 3000 over HTTPS using `docker/certs/cert.pem` and `docker/certs/cert-key.pem`, and expose port 3000 from the `node` service
- [x] [T13] [ADR-0005] Add `make console` target running `php bin/console` inside the API container

## Dependencies

- [x] [T14] [ADR-0005] Install API Composer packages `nelmio/api-doc-bundle` (JSON OpenAPI only), `symfony/monolog-bundle` (structured JSON logs to `stderr` in all environments), `nelmio/cors-bundle` (allow origin `https://dev.app.social.aleherse.com`), `symfony/uid`, and `webmozart/assert`
- [x] [T15] [ADR-0006] Install `symfony/translation` in `apps/api` and `symfony/translation-contracts` in `packages/core`; add a `kernel.request` listener that resolves the request locale from `Accept-Language` with fallback `en`, and create YAML ICU catalogues under `apps/api/translations/` for the `validators` and `errors` domains
- [x] [T16] [ADR-0009] Install `doctrine/dbal` and `doctrine/doctrine-migrations-bundle` in `apps/api`, configured via `DATABASE_URL` env var against the `postgres` service and using PostgreSQL schema `bulletin`
- [x] [T17] [ADR-0011] Install `lexik/jwt-authentication-bundle` in `apps/api`; configure a `stateless: true` firewall, disable the `Authorization` header extractor, read the JWT only from an `httpOnly` cookie named `token` (`Secure`, `SameSite=Strict`, `Path=/`), store RSA keys under `apps/api/config/jwt/`, and extend `make init` to run `lexik:jwt:generate-keypair` only when key files are missing
- [x] [T18] [ADR-0007] Install `@tanstack/react-query` in `apps/web` and compose the `QueryClientProvider` into the app provider stack
- [x] [T19] [ADR-0008] Install `i18next`, `react-i18next`, and `i18next-browser-languagedetector` in `apps/web`; create `src/shared/i18n/` with locale JSON under `locales/en/`, an `I18nProvider`, and a public API re-exporting `useTranslation`
- [x] [T20] [ADR-0010] Initialise Tailwind CSS and shadcn/ui in `apps/web` via the official CLI (npm package runner), placing generated primitives in the FSD shared UI area, and add the `Button`, `Input`, `Card`, and `Label` components

## Linting

- [x] [T21] [ADR-0012] Install and configure `deptrac/deptrac` with layer rules keeping `packages/core` free of Symfony/HTTP dependencies and enforcing app-to-package dependency direction
- [x] [T22] [ADR-0012] Install and configure `phpstan/phpstan` for `apps/api/src` and `packages/core/src` at a strict but incremental level
- [x] [T23] [ADR-0012] Install and configure `symplify/easy-coding-standard` with Symfony/PHP defaults for `apps/api` and `packages/core`
- [x] [T24] [ADR-0012] Configure ESLint for `apps/web` with TypeScript, React, accessibility, and import rules, extending `eslint-config-prettier` last
- [x] [T25] [ADR-0012] Add Prettier config for `apps/web` with the ADR-0012 options (`semi: true`, `singleQuote: true`, `trailingComma: "all"`, `printWidth: 100`, `tabWidth: 2`, `arrowParens: "always"`, `endOfLine: "lf"`, …) and a `.prettierignore` for `dist`, `build`, `coverage`, `node_modules`, `.vite`
- [x] [T26] [ADR-0012] Install and configure `knip` for `apps/web`, tuned to the FSD structure to avoid false positives
- [x] [T27] [ADR-0012] Create root `.editorconfig` aligned with Easy Coding Standard and Prettier settings
- [x] [T28] [ADR-0012] Add Make targets: an aggregate `lint` plus `php-deptrac`, `php-stan`, `php-ecs`, `web-tsc`, `web-eslint`, `web-knip`, and `web-prettier`, using hook-safe `docker compose run --rm` invocations
- [x] [T29] [ADR-0013] Install Lefthook via `make init`; configure `pre-commit` (format, lint, type, coding-standard checks), `commit-msg` (Conventional Commits, optional leading task ID), and `pre-push` (codebase scanners and unit tests only)

## Testing

- [x] [T30] [ADR-0015] Install PHPSpec in `packages/core` with a working spec bootstrap and add `make php-unit`
- [x] [T31] [ADR-0015] Install Behat with `friends-of-behat/symfony-extension` and `mtdowling/jmespath.php` in `apps/api`; create `behat.yml.dist` with the `~@fixtures` gherkin tag filter and a test-environment Symfony context
- [x] [T32] [ADR-0015] Install DSLR (`pip install DSLR`) in the PHP container image; add `make db` running the full sequence: create database, run migrations, load the `@fixtures` tagged `fixtures.feature` dataset, create the `fixtures` snapshot
- [x] [T33] [ADR-0015] Add a Behat hook restoring the DSLR `fixtures` snapshot before each scenario, and add `make api-tests`
- [x] [T34] [ADR-0015] Install Vitest and Testing Library in `apps/web` with a working config and add `make web-unit`
- [x] [T35] [ADR-0015] Install Playwright in `apps/web` configured to run against `https://dev.app.social.aleherse.com` with the mkcert root CA trusted, restoring the DSLR snapshot before each scenario; add `make web-e2e` and `make web-e2e-ui`
- [x] [T36] [ADR-0003] [ADR-0015] Add `make tests` running the full suite: `php-unit`, `api-tests`, `web-unit`, and `web-e2e`

## Skeleton

- [x] [T37] [ADR-0009] Generate a Doctrine migration (`doctrine:migrations:generate`, raw SQL over DBAL) creating schema `bulletin` and table `bulletin.users` with UUID v7 primary key, case-insensitively unique `email`, and `created_at` timestamp
- [x] [T38] [ADR-0005] [ADR-0006] Implement in `packages/core` a user service with find-or-create-by-email and current-user lookup, using a DBAL-backed repository port, `Uuid::v7()` identifiers, `webmozart/assert` guards, and `TranslatorInterface` (contracts only) for validation messages
- [x] [T39] [ADR-0005] [ADR-0011] Implement API endpoints in `apps/api`: `POST /api/session` (validate email, delegate to core find-or-create, set the `token` httpOnly JWT cookie), `GET /api/me` (return `{ "email": string }` or 401), and `POST /api/logout` (clear the `token` cookie); invalid email returns 4xx with a translated error and no user created
- [x] [T40] [ADR-0007] [ADR-0008] [ADR-0010] Implement the `apps/web` homepage in FSD layers: session API client using TanStack Query with `credentials: 'include'` calling `GET /api/me`, an email-only registration form and authenticated hello view (email + logout button) built from shadcn/ui `Card`, `Input`, `Button`, and `Label`, with visible validation/error states and copy via the `shared/i18n` public API
- [x] [T41] [ADR-0015] Write Behat scenarios covering: session creation sets `token` cookie and creates a user; existing email reuses the user; `GET /api/me` with and without a valid cookie; logout then 401 on `/api/me`; invalid email returns 4xx without creating a user — using JMESPath `Then` assertions and application-code `Given` steps
- [x] [T42] [ADR-0015] Write one Playwright journey: open the homepage and see the registration form, register a new email and see the hello view, reload and still see the hello view, log out and see the registration form again
- [x] [T43] [ADR-0015] Add Vitest coverage for email validation logic and conditional rendering of registration form versus hello view

## Scripts

- [x] [T44] [ADR-0013] Add `.github/PULL_REQUEST_TEMPLATE.md` with the ADR-0013 template (issue link, description, CI check checkboxes for PHPSpec, Behat, Vitest, Playwright, rollout notes)
- [x] [T45] [ADR-0013] Add GitHub Actions workflow `.github/workflows/pull-request.yml` on `pull_request` types `[opened, edited, synchronize, reopened]`, with optional jobs for PHPSpec, Behat, Vitest, and Playwright each gated by the matching PR-body checkbox (`contains(github.event.pull_request.body, '- [x] PHPSpec')`, etc.)
- [x] [T46] [ADR-0014] Bootstrap an AWS CDK TypeScript app under `infrastructure/` with `live` and `preview` environment definitions and environment-scoped AWS Systems Manager Parameter Store paths (`/social-bulletin/<env>/...`) for configuration and secrets
- [x] [T47] [ADR-0014] Add a CDK frontend stack: private S3 bucket behind CloudFront at `app.social.aleherse.com`, with Route53 alias and ACM certificate managed by CDK
- [x] [T48] [ADR-0014] Add a CDK API stack: Bref PHP-FPM Lambda behind CloudFront at `api.social.aleherse.com`, a Bref console Lambda for post-deploy migrations, Route53 alias, and ACM certificate
- [x] [T49] [ADR-0014] Add a CDK database stack: Aurora Serverless v2 PostgreSQL cluster with one writer, no reader, and cost-optimised capacity settings, with connection secrets published to Parameter Store
- [x] [T50] [ADR-0014] Add GitHub Actions workflow `.github/workflows/deploy.yml` deploying via CDK on merge into the `live` branch (build frontend assets, package API for Bref, `cdk deploy`, then run migrations through the console Lambda)

## Documentation

- [x] [T51] Add root `README.md` covering prerequisites (Docker, Docker Compose, make), `/etc/hosts` entries for `dev.api.social.aleherse.com` and `dev.app.social.aleherse.com`, trusting the mkcert root CA from `docker/certs/rootCA.pem`, and the bootstrap/run/test/lint workflow (`make init`, `make build`, `make up`, `make db`, `make tests`, `make lint`)
- [x] [T52] Add root `AGENTS.md` describing the monorepo layout, `specs/decisions/` ADR conventions, Makefile-first command surface, and quality gates for agent-driven work

## Cleanup

- [x] [T53] [ADR-0001] Untrack `.idea/` from git via `git rm -r --cached .idea/` so the new `.gitignore` rule takes effect
- [x] [T54] [ADR-0002] Remove `apps/api/compose.yaml` and `apps/api/compose.override.yaml` left by the Symfony Flex docker recipe; the root `docker-compose.yml` is the only compose entrypoint
