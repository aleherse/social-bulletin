# Walking Skeleton Tasks

## Phase 1 — Structure

- [x] [T01] [ADR-0001] Create top-level monorepo directories: apps/, docker/, infrastructure/, packages/, scripts/
- [x] [T02] [ADR-0001] Create root .gitignore covering build outputs, caches, local env files, JWT keys (apps/api/config/jwt/), certs (docker/php/certs/), editor state, node_modules, vendor, dist, .vite

## Phase 2 — Containers

- [x] [T03] [P] [ADR-0002] Create docker/php/Dockerfile: php:8.3-cli-bookworm base, Composer, Symfony CLI, gosu, pdo_pgsql/zip extensions, Xdebug via PECL, python3-pip, app user with UID/GID build args
- [x] [T04] [P] [ADR-0002] Create docker/php/xdebug.ini: xdebug.mode=debug,coverage,profile; start_with_request=trigger; client_host/port/output_dir from env vars
- [x] [T05] [P] [ADR-0002] Create docker/php/entrypoint.sh: install Symfony CA cert if absent (export PEM to docker/php/certs/); generate Vite domain cert signed by CA; generate JWT keypair if absent; composer install if vendor absent; gosu app exec CMD
- [x] [T06] [P] [ADR-0002] Create docker/node/Dockerfile: node:lts-bookworm base with npm; app user with UID/GID build args
- [x] [T07] [P] [ADR-0002] Create docker/node/entrypoint.sh: npm install if node_modules absent; exec node CMD
- [x] [T08] [ADR-0002] Create docker-compose.yml: php (apps/api, port 8000), node (apps/web, port 3000), postgres:16 (port 5432); named volumes for api_vendor, web_node_modules, postgres_data; bind-mount project root to /var/www/app in php and node
- [x] [T09] [ADR-0002] Create docker-compose.override.yml.dist with example host port mappings and env var overrides (UID, GID, XDEBUG_REMOTE_HOST, POSTGRES_PASSWORD); add docker-compose.override.yml to .gitignore

## Phase 3 — Makefile

- [x] [T10] [ADR-0003] Create root Makefile with runtime-discovered help and targets: init (copy override + build + up + keypair + lefthook install), build, up, down, ps, logs, shell, tests, clean, destroy, console (ADR-0004)

## Phase 4 — PHP/Symfony Setup

- [x] [T11-build] [ADR-0002] Build Docker containers (php, node)
- [x] [T11] [ADR-0004] Bootstrap Symfony API: run `docker compose run --rm php symfony new /var/www/app/apps/api --version=lts --no-git` from project root; configure apps/api/.env.local.dist
- [x] [T12] [ADR-0004] Create packages/core: composer.json (name: socialbulletin/core, PSR-4 namespace SocialBulletin\Core), src/, tests/ directories; add as path repository in apps/api/composer.json
- [x] [T13] [P] [ADR-0004] Install Symfony bundles in apps/api: nelmio/api-doc-bundle, symfony/monolog-bundle, nelmio/cors-bundle, symfony/uid, webmozart/assert
- [x] [T14] [P] [ADR-0005] Install Doctrine packages in apps/api: doctrine/dbal, doctrine/doctrine-migrations-bundle
- [x] [T15] [ADR-0004] Configure NelmioCorsBundle in apps/api/config/packages/nelmio_cors.yaml: allow origins matching %env(DEV_FRONT_URL)%; allow methods GET, POST, OPTIONS; allow headers Content-Type, Accept, Accept-Language
- [x] [T16] [ADR-0004] Configure MonologBundle in apps/api/config/packages/monolog.yaml: single json_formatter handler writing to stderr in all environments; remove file-based handlers
- [x] [T17] [ADR-0005] Configure apps/api/config/packages/doctrine.yaml: DATABASE_URL env var; doctrine_migrations search_path set to %env(DATABASE_SCHEMA)% (bulletin); migrations_paths under src/Migrations
- [x] [T18] [ADR-0005] Create apps/api/.env.local.dist with DATABASE_URL, DATABASE_SCHEMA=bulletin, APP_SECRET placeholder, DEV_FRONT_URL=https://app.dev.social.aleherse.com:3000
- [x] [T19] [P] [ADR-0009] Install symfony/translation in apps/api; configure translator.yaml with default_locale: en, available_locales: [en], fallbacks: [en]
- [x] [T20] [ADR-0009] Create translation YAML ICU catalogues: apps/api/translations/validators+intl-icu.en.yaml, notifications+intl-icu.en.yaml, errors+intl-icu.en.yaml with empty placeholder entries
- [x] [T21] [ADR-0009] Create AcceptLanguageListener in apps/api/src/EventListener/AcceptLanguageListener.php; register as kernel.request event subscriber; sets request locale from Accept-Language header, fallback en
- [x] [T22] [P] [ADR-0010] Install lexik/jwt-authentication-bundle in apps/api
- [x] [T23] [ADR-0010] Configure apps/api/config/packages/security.yaml: stateless firewall; jwt_authenticator reads only from token cookie; configure apps/api/config/packages/lexik_jwt_authentication.yaml with cookie delivery (httpOnly, Secure, SameSite=Strict, Path=/)

## Phase 5 — Frontend Setup

- [x] [T24] [ADR-0006] Bootstrap Vite app: run `docker compose run --rm --workdir /var/www/app node npx --yes create-vite@latest apps/web --template react-ts` from project root
- [x] [T25] [ADR-0006] Install @tanstack/react-query in apps/web via docker compose exec node npm install @tanstack/react-query
- [x] [T26] [ADR-0010] Configure apps/web/vite.config.ts: server.https using fs.readFileSync for cert/key from docker/php/certs/vite.crt and vite.key; host: true; port: 3000; strictPort: true
- [x] [T27] [ADR-0007] Initialise shadcn/ui in apps/web: run npx shadcn@latest init in Node container; configure components.json; install Tailwind CSS; set up globals.css with CSS custom properties
- [x] [T28] [ADR-0008] Install i18n packages in apps/web: i18next, react-i18next, i18next-browser-languagedetector
- [x] [T29] [ADR-0008] Create apps/web/src/shared/i18n/: i18n.ts (i18next init with language detector), I18nProvider.tsx (wraps I18nextProvider), index.ts (re-exports useTranslation), locales/en/common.json with initial keys

## Phase 6 — Linting

- [x] [T30] [P] [ADR-0012] Install PHP static analysis in apps/api: deptrac/deptrac, phpstan/phpstan, symplify/easy-coding-standard (--dev)
- [x] [T31] [P] [ADR-0012] Install TypeScript tooling in apps/web: eslint, @eslint/js, typescript-eslint, eslint-plugin-react, eslint-plugin-react-hooks, eslint-plugin-jsx-a11y, eslint-config-prettier, prettier, knip (--save-dev)
- [x] [T32] [ADR-0012] Create PHP config files: apps/api/deptrac.yaml (layers: Application, Domain, Infrastructure, HttpApi; ruleset enforcing one-way dependencies), apps/api/phpstan.neon (level: 8, paths: src/), apps/api/ecs.php (Symfony default set)
- [x] [T33] [ADR-0012] Create frontend config files: apps/web/eslint.config.js (flat config with TS, React, React Hooks, jsx-a11y, prettier), apps/web/.prettierrc (semi:true, singleQuote:true, trailingComma:all, printWidth:100, tabWidth:2, arrowParens:always, endOfLine:lf), apps/web/.prettierignore, apps/web/knip.json (FSD-aware paths)
- [x] [T34] [ADR-0012] Create root .editorconfig: charset utf-8, end_of_line lf, indent_style space; indent_size 4 for PHP; indent_size 2 for JS/TS/JSON/CSS/YAML; insert_final_newline true; trim_trailing_whitespace true
- [x] [T35] [ADR-0012] Add Makefile linting targets: lint (all checks), php-deptrac, php-stan, php-cs, ts-check, ts-lint, ts-knip, ts-format — all using docker compose run --rm for hook-safe execution

## Phase 7 — Testing Toolchain

- [x] [T36] [P] [ADR-0011] Install PHPSpec in packages/core: phpspec/phpspec (--dev); create packages/core/phpspec.yml with suites mapping spec/ to SocialBulletin\Core namespace
- [x] [T37] [P] [ADR-0011] Install Behat stack in apps/api (--dev): behat/behat, friends-of-behat/symfony-extension, friends-of-behat/mink-extension, behat/mink-browserkit-driver, mtdowling/jmespath.php
- [x] [T38] [ADR-0011] Create apps/api/behat.yml.dist: configure SymfonyExtension (kernel: AppKernel, env: test), MinkExtension (base_url: http://php), gherkin tag filter (tags: ~@fixtures), default suite features/; copy to behat.yml in make init
- [x] [T39] [ADR-0011] Add dslr to PHP Dockerfile: install python3-pip + `pip3 install --break-system-packages DSLR`; create apps/api/.dslr.yaml pointing to bulletin schema; add DSLR snapshot/restore to make db and make api-tests
- [x] [T40] [ADR-0011] Create apps/api/features/fixtures.feature (@fixtures tag with @fixtures-tag annotation) and apps/api/features/bootstrap/FeatureContext.php with BeforeScenario dslr restore hook using Process
- [x] [T41] [P] [ADR-0011] Install Vitest stack in apps/web: vitest, @vitest/coverage-v8, @testing-library/react, @testing-library/user-event, @testing-library/jest-dom, jsdom (--save-dev); create apps/web/vitest.config.ts
- [x] [T42] [P] [ADR-0011] Install Playwright in apps/web: @playwright/test (--save-dev); create apps/web/playwright.config.ts (baseURL from DEV_FRONT_URL, no webServer block — containers serve it); run playwright install --with-deps chromium in Node container
- [x] [T43] [ADR-0011] Add Makefile test targets: php-unit (PHPSpec in packages/core), api-tests (Behat in apps/api), web-unit (Vitest), web-e2e (Playwright headless), web-e2e-ui (Playwright UI mode), db (create DB + run migrations + load fixtures + dslr snapshot)

## Phase 8 — Quality Gates

- [x] [T44] [ADR-0014] Add lefthook to apps/web devDependencies; add `npx lefthook install` to make init; create root lefthook.yml
- [x] [T45] [ADR-0014] Configure lefthook.yml: pre-commit (ts-format, ts-lint, ts-check, php-cs — docker compose run --rm); commit-msg (regex for Conventional Commits optionally prefixed with task-tool ID); pre-push (php-unit, ts-check)
- [x] [T46] [P] [ADR-0014] Create .github/PULL_REQUEST_TEMPLATE.md: Closes link, Description, CI checkboxes (PHPSpec, Behat, Vitest, Playwright), Risks/rollout notes
- [x] [T47] [P] [ADR-0014] Create .github/workflows/ci.yml: on pull_request [opened, edited, synchronize, reopened]; jobs php-spec, behat, vitest, playwright each gated by PR body checkbox using contains(github.event.pull_request.body, '- [x] ...')

## Phase 9 — Walking Skeleton Feature

- [x] [T48] [ADR-0013] Generate and write Doctrine migration in apps/api: create users table in bulletin schema (id UUID v7 PK, email VARCHAR(255) UNIQUE NOT NULL, created_at TIMESTAMPTZ NOT NULL)
- [x] [T49] [ADR-0013] Implement POST /api/register in apps/api: validate email, find-or-create user in bulletin.users, issue JWT as httpOnly Secure SameSite=Strict token cookie via LexikJWTAuthenticationBundle, return 200 {email}
- [x] [T50] [P] [ADR-0013] Implement GET /api/me in apps/api: return 200 {id, email} for valid JWT cookie; Symfony security returns 401 automatically for unauthenticated
- [x] [T51] [P] [ADR-0013] Implement POST /api/logout in apps/api: clear token cookie (set to empty string with past expiry, same HttpOnly Secure SameSite=Strict flags), return 200
- [x] [T52] [ADR-0013] Create apps/web/src/pages/home/ui/RegistrationPage.tsx: email text input, submit button; calls POST /api/register via TanStack Query mutation; on success parent switches to hello view
- [x] [T53] [ADR-0013] Create apps/web/src/pages/home/ui/HelloPage.tsx: greet user by email; logout button calls POST /api/logout mutation then parent switches to registration view
- [x] [T54] [ADR-0013] Wire apps/web/src/app/App.tsx: on mount call GET /api/me; show HelloPage if 200; show RegistrationPage if 401/error; wrap with QueryClientProvider and I18nProvider

## Phase 10 — Tests

- [x] [T55] [P] [ADR-0011] Write PHPSpec unit specs in packages/core/spec/: UserSpec (create user, email stored) and EmailSpec (valid email accepted, invalid rejected, email normalised to lowercase)
- [x] [T56] [P] [ADR-0011] Write Behat feature file apps/api/features/registration.feature: scenarios for register new user (cookie set), login existing user (cookie set), GET /me authenticated (200 + email), GET /me unauthenticated (401), POST /logout (200 + cookie cleared)
- [x] [T57] [P] [ADR-0011] Write Vitest unit tests in apps/web/src: useRegister.test.ts, useCurrentUser.test.ts, useLogout.test.ts using msw or vi.mock for API calls
- [x] [T58] [P] [ADR-0011] Write Playwright E2E test apps/web/e2e/registration.spec.ts: navigate to homepage → submit email → verify hello view shows email → click logout → verify registration form appears

## Phase 11 — AWS Deployment

- [x] [T59] [P] [ADR-0015] Create infrastructure/ CDK TypeScript app: npm init + cdk init; install aws-cdk-lib, constructs; define SocialBulletinStack with private S3 bucket + CloudFront (frontend), Bref PHP-FPM Lambda + CloudFront (API), Bref console Lambda, Aurora Serverless v2 writer-only, Route53 aliases, ACM certificates, SSM Parameter Store paths under /{env}/socialbulletin/
- [x] [T60] [P] [ADR-0015] Configure infrastructure/bin/app.ts for two environments (live, preview) with isolated stacks; add make target deploy-live
- [x] [T61] [ADR-0015] Create .github/workflows/deploy.yml: trigger on push to live branch; steps: checkout, setup Node, npm ci in infrastructure/, build apps/web (npm run build), cdk deploy SocialBulletinStack-live using OIDC-based AWS credentials from GitHub secrets
