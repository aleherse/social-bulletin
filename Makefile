SHELL := /bin/sh
COMPOSE := docker compose

export HOST_UID ?= $(shell id -u)
export HOST_GID ?= $(shell id -g)

.DEFAULT_GOAL := help

.PHONY: help
help: ## List supported targets and their purpose
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z0-9_-]+:.*## / {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.PHONY: init
init: ## Prepare the local development environment (env templates, containers, infrastructure)
	@test -f docker-compose.override.yml || cp docker-compose.override.yml.dist docker-compose.override.yml
	$(COMPOSE) build
	$(COMPOSE) up -d
	$(COMPOSE) exec --user root node sh -c 'npm install -g npm@latest'
	@test -f apps/api/config/jwt/private.pem || $(COMPOSE) exec --workdir /app/apps/api php php bin/console lexik:jwt:generate-keypair
	@test -x node_modules/.bin/lefthook || npm install --no-audit --no-fund
	npx lefthook install

.PHONY: build
build: ## Install all package manager dependencies and build the frontend assets
	$(COMPOSE) run --rm php sh -c 'test -f apps/api/composer.json && composer install --working-dir=apps/api --no-interaction || true'
	$(COMPOSE) run --rm --entrypoint /app/docker/node/entrypoint.sh node sh -c 'test -f /app/apps/web/package.json && npm install --prefix /app/apps/web || true'
	$(COMPOSE) run --rm --entrypoint /app/docker/node/entrypoint.sh node sh -c 'test -f /app/apps/web/package.json && npm run build --prefix /app/apps/web || true'

.PHONY: up
up: ## Start the development stack without building containers
	$(COMPOSE) up -d --no-build

.PHONY: down
down: ## Stop the development stack
	$(COMPOSE) down

.PHONY: ps
ps: ## List running containers
	$(COMPOSE) ps

.PHONY: logs
logs: ## Inspect all service logs, or one via `make logs service=php`
	$(COMPOSE) logs -f $(service)

.PHONY: console
console: ## Run a Symfony console command, e.g. `make console cmd="cache:clear"`
	$(COMPOSE) exec --workdir /app/apps/api php php bin/console $(cmd)

.PHONY: db
db: ## Prepare the test database: create, migrate, load @fixtures dataset, snapshot
	$(COMPOSE) run --rm --workdir /app/apps/api php php bin/console doctrine:database:create --if-not-exists
	$(COMPOSE) run --rm --workdir /app/apps/api php php bin/console dbal:run-sql 'CREATE SCHEMA IF NOT EXISTS bulletin'
	$(COMPOSE) run --rm --workdir /app/apps/api php php bin/console dbal:run-sql 'ALTER DATABASE bulletin SET search_path TO bulletin, public'
	$(COMPOSE) run --rm --workdir /app/apps/api php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(COMPOSE) run --rm --workdir /app/apps/api php vendor/bin/behat --tags=@fixtures
	$(COMPOSE) run --rm php sh -c 'dslr --url "$$DSLR_DATABASE_URL" delete fixtures 2>/dev/null; dslr --url "$$DSLR_DATABASE_URL" snapshot fixtures'

.PHONY: php-unit
php-unit: ## Run core package unit tests with PHPSpec
	$(COMPOSE) run --rm --workdir /app/packages/core php vendor/bin/phpspec run --no-interaction

.PHONY: api-tests
api-tests: ## Run API tests with Behat (requires a `make db` snapshot)
	$(COMPOSE) run --rm --workdir /app/apps/api php vendor/bin/behat

.PHONY: web-unit
web-unit: ## Run frontend unit tests with Vitest
	$(COMPOSE) run --rm node npm run test

.PHONY: web-e2e
web-e2e: ## Run frontend E2E tests with Playwright (builds the frontend first)
	$(COMPOSE) run --rm node npm run build
	$(COMPOSE) run --rm node npx playwright test

.PHONY: web-e2e-ui
web-e2e-ui: ## Open the Playwright UI on https://dev.app.social.aleherse.com:9323
	$(COMPOSE) run --rm node npm run build
	$(COMPOSE) run --rm -p 9323:9323 node npx playwright test --ui-host=0.0.0.0 --ui-port=9323

.PHONY: tests
tests: php-unit api-tests web-unit web-e2e ## Run the full automated test suite

.PHONY: lint
lint: php-deptrac php-stan php-ecs web-tsc web-eslint web-knip web-prettier ## Run all linting and static analysis checks

.PHONY: php-deptrac
php-deptrac: ## Check PHP architecture boundaries with Deptrac
	$(COMPOSE) run --rm php apps/api/vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress

.PHONY: php-stan
php-stan: ## Run PHP static analysis with PHPStan
	$(COMPOSE) run --rm php apps/api/vendor/bin/phpstan analyse -c phpstan.dist.neon --no-progress

.PHONY: php-ecs
php-ecs: ## Check PHP coding standards with Easy Coding Standard
	$(COMPOSE) run --rm php apps/api/vendor/bin/ecs check --config ecs.php --no-progress-bar

.PHONY: web-tsc
web-tsc: ## Type-check the frontend with the TypeScript compiler
	$(COMPOSE) run --rm node npx tsc -b

.PHONY: web-eslint
web-eslint: ## Lint the frontend with ESLint
	$(COMPOSE) run --rm node npm run lint

.PHONY: web-knip
web-knip: ## Check frontend project hygiene with knip
	$(COMPOSE) run --rm node npx knip

.PHONY: web-prettier
web-prettier: ## Check frontend formatting with Prettier
	$(COMPOSE) run --rm node npm run format:check

.PHONY: shell
shell: ## Open an interactive shell in the PHP container (or another via `make shell service=node`)
	$(COMPOSE) exec $(or $(service),php) bash

.PHONY: clean
clean: ## Safely remove recreated local artefacts and dependencies
	rm -rf apps/api/vendor packages/core/vendor apps/web/node_modules apps/web/dist apps/api/var .install.lock .deptrac.cache

.PHONY: destroy
destroy: ## Delete all containers, volumes, and artefacts
	$(COMPOSE) down --volumes --remove-orphans --rmi local
	$(MAKE) clean

.PHONY: setup-claude
setup-claude: ## create symlinks for Claude Code (.claude/skills and CLAUDE.md)
	@mkdir -p .claude/skills
	@for skill in .agents/skills/*/; do \
		skill_name=$$(basename "$$skill"); \
		if [ ! -e ".claude/skills/$$skill_name" ]; then \
			ln -s "$$(pwd)/.agents/skills/$$skill_name" ".claude/skills/$$skill_name" && echo "Linked skill: $$skill_name"; \
		else \
			echo "Skipping (already exists): $$skill_name"; \
		fi \
	done
	@if [ ! -e "CLAUDE.md" ]; then \
		ln -s "$$(pwd)/AGENTS.md" "$$(pwd)/CLAUDE.md" && echo "Linked: CLAUDE.md -> AGENTS.md"; \
	else \
		echo "Skipping (already exists): CLAUDE.md"; \
	fi
