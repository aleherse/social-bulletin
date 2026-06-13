.DEFAULT_GOAL := help

COMPOSE := docker compose
COMPOSE_RUN := $(COMPOSE) run --rm
SERVICE ?= php

.PHONY: help init build up down ps logs shell clean destroy tests console db \
	php-unit api-tests web-unit web-e2e web-e2e-ui \
	lint lint-php lint-ts phpstan deptrac ecs eslint prettier knip typecheck \
	hook-pre-commit hook-pre-push hook-commit-msg

help: ## List supported targets and their purpose
	@awk 'BEGIN {FS = ":.*?## "}; /^[a-zA-Z0-9_.-]+:.*?## / {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

init: ## Prepare local dev: override file, containers, deps, JWT keys, lefthook
	@test -f docker-compose.override.yml || cp docker-compose.override.yml.example docker-compose.override.yml
	$(COMPOSE) build
	$(COMPOSE) up -d
	$(MAKE) build
	$(COMPOSE) exec php symfony server:ca:install --no-interaction || true
	$(COMPOSE) exec php php apps/api/bin/console lexik:jwt:generate-keypair --skip-if-exists || true
	@command -v lefthook >/dev/null && lefthook install || echo "Install lefthook locally to enable git hooks"

build: ## Install all package manager dependencies
	$(COMPOSE) exec php composer install --working-dir=apps/api
	$(COMPOSE) exec php composer install --working-dir=packages/core
	$(COMPOSE) exec node npm install --prefix apps/web

up: ## Start the development stack without building
	$(COMPOSE) up -d

down: ## Stop the development stack
	$(COMPOSE) down

ps: ## List running containers
	$(COMPOSE) ps

logs: ## Inspect service logs (SERVICE=php|node|postgres)
	$(COMPOSE) logs -f $(SERVICE)

shell: ## Open shell in a service container (SERVICE=php|node|postgres)
	$(COMPOSE) exec $(SERVICE) bash

clean: ## Remove recreated local artefacts and dependencies
	rm -rf apps/api/vendor apps/web/node_modules packages/core/vendor
	rm -rf apps/api/var/cache apps/web/dist apps/web/coverage
	rm -rf xdebug .dslr

destroy: ## Delete all containers, volumes, and local artefacts
	$(COMPOSE) down -v --remove-orphans
	$(MAKE) clean

console: ## Run Symfony console command (ARGS="...")
	$(COMPOSE) exec php php apps/api/bin/console $(ARGS)

db: ## Create database, migrate, load fixtures, snapshot
	$(COMPOSE) exec php php apps/api/bin/console doctrine:database:create --if-not-exists
	$(COMPOSE) exec php php apps/api/bin/console dbal:run-sql "CREATE SCHEMA IF NOT EXISTS bulletin"
	$(COMPOSE) exec php php apps/api/bin/console doctrine:migrations:migrate --no-interaction
	$(COMPOSE) exec php vendor/bin/behat --tags=@fixtures --no-interaction || true
	$(COMPOSE) exec php pip install DSLR 2>/dev/null || pip3 install DSLR 2>/dev/null || true
	$(COMPOSE) exec php dslr snapshot fixtures || true

php-unit: ## Run PHPSpec unit tests in packages/core
	$(COMPOSE_RUN) -w /app/packages/core php vendor/bin/phpspec run --no-code-generation

api-tests: ## Run Behat API integration tests
	$(COMPOSE_RUN) -e APP_ENV=test -w /app/apps/api php sh -c 'php bin/console doctrine:database:create --if-not-exists && php bin/console dbal:run-sql "CREATE SCHEMA IF NOT EXISTS bulletin" && php bin/console doctrine:migrations:migrate --no-interaction && php bin/console lexik:jwt:generate-keypair --skip-if-exists && vendor/bin/behat --config=behat.yml.dist'

web-unit: ## Run Vitest unit tests in apps/web
	$(COMPOSE_RUN) node npm run test --prefix apps/web

web-e2e: ## Run Playwright end-to-end tests
	$(COMPOSE_RUN) node npm run test:e2e --prefix apps/web

web-e2e-ui: ## Run Playwright with UI mode
	$(COMPOSE_RUN) node npm run test:e2e:ui --prefix apps/web

tests: php-unit api-tests web-unit web-e2e ## Run the full automated test suite

lint: lint-php lint-ts ## Run all linting and static analysis checks

lint-php: deptrac phpstan ecs ## Run all PHP checks

lint-ts: typecheck eslint knip prettier ## Run all TypeScript checks

deptrac: ## Run Deptrac dependency analysis
	$(COMPOSE_RUN) php apps/api/vendor/bin/deptrac analyse --config-file=deptrac.yaml

phpstan: ## Run PHPStan static analysis
	$(COMPOSE_RUN) php apps/api/vendor/bin/phpstan analyse -c phpstan.neon

ecs: ## Run Easy Coding Standard
	$(COMPOSE_RUN) php apps/api/vendor/bin/ecs check

eslint: ## Run ESLint
	$(COMPOSE_RUN) node npm run lint --prefix apps/web

prettier: ## Run Prettier check
	$(COMPOSE_RUN) node npm run format:check --prefix apps/web

knip: ## Run Knip unused export/dependency checks
	$(COMPOSE_RUN) node npm run knip --prefix apps/web

typecheck: ## Run TypeScript compiler
	$(COMPOSE_RUN) node npm run typecheck --prefix apps/web

hook-pre-commit: lint ## Hook-safe pre-commit checks
hook-pre-push: php-unit web-unit ## Hook-safe pre-push checks
hook-commit-msg: ## Validate conventional commit message (MSG_FILE=$(1))
	@test -n "$(MSG_FILE)"
