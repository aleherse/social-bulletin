.DEFAULT_GOAL := help
SHELL := /bin/sh
COMPOSE := docker compose
RUN_API := $(COMPOSE) run --rm api
RUN_WEB := $(COMPOSE) run --rm web
EXEC_API := $(COMPOSE) exec api
EXEC_WEB := $(COMPOSE) exec web

.PHONY: help init buid build up down ps logs shell api-shell web-shell console tests clean destroy \
	php-unit api-tests web-unit web-e2e web-e2e-ui db \
	checks lint lint-php lint-ts format format-check deptrac phpstan ecs ecs-fix typecheck eslint prettier prettier-fix knip \
	hook-pre-commit hook-pre-push hook-commit-msg

help: ## List supported targets.
	@awk 'BEGIN {FS = ":.*## "; printf "SocialBulletin targets:\n"} /^[a-zA-Z0-9_.-]+:.*## / {printf "  %-22s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

init: ## Prepare local environment, containers, dependencies, database, hooks, and secrets.
	@test -f .env || cp .env.example .env
	@test -f compose.override.yml || cp compose.override.yml.dist compose.override.yml
	$(COMPOSE) up -d --build database api web nginx
	$(RUN_API) php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction || true
	$(RUN_API) php bin/console doctrine:database:create --if-not-exists --no-interaction || true
	$(RUN_API) php bin/console dbal:run-sql 'CREATE SCHEMA IF NOT EXISTS bulletin' || true
	$(RUN_API) php bin/console doctrine:migrations:migrate --no-interaction || true
	$(RUN_WEB) npx lefthook install || true

buid: build ## Install all package-manager dependencies. Kept for ADR-0003 spelling.

build: ## Build containers and install dependencies.
	$(COMPOSE) build
	$(RUN_API) composer install --no-interaction
	$(RUN_API) composer install --working-dir=/workspace/packages/core --no-interaction
	$(RUN_WEB) npm install
	$(RUN_WEB) npx playwright install chromium
	$(RUN_WEB) npm install --prefix /workspace/infrastructure

up: ## Start development stack without rebuilding containers.
	$(COMPOSE) up -d

down: ## Stop development stack.
	$(COMPOSE) down

ps: ## List running containers.
	$(COMPOSE) ps

logs: ## Show all service logs. Use SERVICE=name to narrow.
	@if [ -n "$(SERVICE)" ]; then $(COMPOSE) logs -f $(SERVICE); else $(COMPOSE) logs -f; fi

shell: api-shell ## Open API shell by default.

api-shell: ## Open shell in API container.
	$(EXEC_API) sh

web-shell: ## Open shell in web container.
	$(EXEC_WEB) sh

console: ## Run Symfony console. Use ARGS="cache:clear".
	$(RUN_API) php bin/console $(ARGS)

db: ## Recreate DB, run migrations, load fixtures, and create DSLR snapshot.
	$(RUN_API) php bin/console doctrine:database:drop --force --if-exists --no-interaction
	$(RUN_API) php bin/console doctrine:database:create --if-not-exists --no-interaction
	$(RUN_API) php bin/console dbal:run-sql 'CREATE SCHEMA IF NOT EXISTS bulletin'
	$(RUN_API) php bin/console doctrine:migrations:migrate --no-interaction
	$(RUN_API) vendor/bin/behat --tags='@fixtures' || true
	$(COMPOSE) run --rm database sh -lc 'pip install DSLR && dslr snapshot fixtures' || true

tests: php-unit api-tests web-unit web-e2e ## Run full automated test suite.

php-unit: ## Run PHPSpec core tests.
	$(RUN_API) composer --working-dir=/workspace/packages/core phpspec

api-tests: ## Run Behat API tests.
	$(RUN_API) vendor/bin/behat

web-unit: ## Run Vitest unit/component tests.
	$(RUN_WEB) npm run test:unit -- --run

web-e2e: ## Run Playwright E2E tests.
	$(RUN_WEB) npm run test:e2e

web-e2e-ui: ## Run Playwright UI.
	$(RUN_WEB) npm run test:e2e:ui

checks: lint typecheck knip php-unit web-unit ## Run local pre-merge checks.

lint: lint-php lint-ts format-check ## Run all linting and formatting checks.

lint-php: deptrac phpstan ecs ## Run PHP dependency, static, and style checks.

lint-ts: eslint ## Run TypeScript lint checks.

format: ecs-fix prettier-fix ## Fix supported formatting.

format-check: ecs prettier ## Check formatting.

deptrac: ## Run PHP dependency analysis.
	$(RUN_API) vendor/bin/deptrac analyse --config-file=/workspace/deptrac.yaml

phpstan: ## Run PHP static analysis.
	$(RUN_API) vendor/bin/phpstan analyse --configuration=/workspace/phpstan.neon

ecs: ## Run Easy Coding Standard check.
	$(RUN_API) vendor/bin/ecs check --config=/workspace/ecs.php

ecs-fix: ## Fix PHP coding standard issues.
	$(RUN_API) vendor/bin/ecs check --config=/workspace/ecs.php --fix

typecheck: ## Run TypeScript compiler checks.
	$(RUN_WEB) npm run typecheck

eslint: ## Run ESLint.
	$(RUN_WEB) npm run lint

prettier: ## Check frontend formatting with Prettier.
	$(RUN_WEB) npm run format:check

prettier-fix: ## Fix frontend formatting with Prettier.
	$(RUN_WEB) npm run format

knip: ## Run frontend unused code/dependency checks.
	$(RUN_WEB) npm run knip

hook-pre-commit: ## Hook-safe fast checks for pre-commit.
	$(COMPOSE) run --rm api vendor/bin/ecs check --config=/workspace/ecs.php
	$(COMPOSE) run --rm web npm run lint
	$(COMPOSE) run --rm web npm run typecheck
	$(COMPOSE) run --rm web npm run format:check

hook-pre-push: ## Hook-safe medium checks for pre-push.
	$(COMPOSE) run --rm api composer --working-dir=/workspace/packages/core phpspec
	$(COMPOSE) run --rm web npm run test:unit -- --run
	$(COMPOSE) run --rm web npm run knip

hook-commit-msg: ## Validate Conventional Commit message. Use MSG_FILE=.git/COMMIT_EDITMSG.
	$(COMPOSE) run --rm web node /workspace/scripts/validate-commit-msg.mjs "$${MSG_FILE:-.git/COMMIT_EDITMSG}"

clean: ## Safely remove recreated local artefacts and dependencies.
	rm -rf apps/web/node_modules apps/web/dist apps/web/coverage apps/web/playwright-report apps/web/test-results
	rm -rf apps/api/vendor apps/api/var packages/core/vendor packages/core/build packages/core/coverage
	rm -rf infrastructure/node_modules infrastructure/dist infrastructure/cdk.out .phpstan.cache .phpunit.cache

destroy: ## Delete containers, volumes, and recreated artefacts.
	$(COMPOSE) down -v --remove-orphans
	$(MAKE) clean
