SHELL := /bin/sh
COMPOSE := docker compose
MAKE := make --no-print-directory

# Containers that bind-mount the tree write as whoever runs make, never root;
# under sudo that is the invoking user. An identity already exported wins:
# under a rootless engine (the Overboards worker exports 0:0) inner root is
# what maps back to the owner of the tree, and the caller's own UID does not.
export HOST_UID ?= $(or $(SUDO_UID),$(shell id -u))
export HOST_GID ?= $(or $(SUDO_GID),$(shell id -g))

# Host ports for `make urls`, read from .env with the .env.dist defaults.
# Only these keys are read: including .env whole would re-export its
# COMPOSE_PROJECT_NAME over the gate's own.
env_value = $(shell sed -n 's/^$(1)=//p' .env 2>/dev/null | tail -n 1)
NODE_PORT := $(or $(call env_value,NODE_PORT),3000)
NGINX_HTTPS_PORT := $(or $(call env_value,NGINX_HTTPS_PORT),443)
POSTGRES_PORT := $(or $(call env_value,POSTGRES_PORT),5432)

RUN_PHP := $(COMPOSE) run --rm --no-deps php
RUN_NODE := $(COMPOSE) run --rm --no-deps node

# Focussed checks narrow to FILES (lint) or PATHS (tests), given from the
# repository root. Each check keeps the files it understands, strips the
# prefix its container works from, and says so when none are left.
FILES ?=
PATHS ?=
php_files = $(filter %.php,$(FILES))
web_files = $(patsubst apps/web/%,%,$(filter apps/web/%,$(FILES)))
# $(call narrowed,<matching files>,<command>): whole project without FILES,
# the matching files with it, nothing when FILES matches none.
narrowed = $(if $(FILES),$(if $(strip $(1)),$(2) $(1),@echo "$@: no matching files in FILES, skipped"),$(2) $(3))

# The local gate applies deterministic formatters; CI (which sets CI=true)
# checks them strictly.
ECS_MODE := $(if $(CI),,--fix)
PRETTIER_MODE := $(if $(CI),--check,--write --list-different)

.DEFAULT_GOAL := help

.PHONY: help
help: ## List supported targets and their purpose
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z0-9_-]+:.*## / {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## --- Setup and daily use ----------------------------------------------------

.PHONY: init
init: env images deps web-build up db hooks setup-claude ## Initialise (or update) everything from scratch; safe to re-run

.PHONY: env
env: ## Create .env from .env.dist when missing
	@test -f .env || cp .env.dist .env

.PHONY: images
images: ## Build the container images
	$(COMPOSE) build

.PHONY: deps
deps: ## Install Composer and npm dependencies and the JWT key pair
	$(RUN_PHP) composer install --working-dir=/app/packages/core --no-interaction
	$(RUN_PHP) composer install --working-dir=/app/apps/api --no-interaction
	$(RUN_PHP) sh -c 'cd /app/apps/api && php bin/console lexik:jwt:generate-keypair --skip-if-exists'
	$(RUN_NODE) npm install --no-audit --no-fund --prefix /app/apps/web
	$(RUN_NODE) npm install --no-audit --no-fund --prefix /app/infrastructure

.PHONY: hooks
hooks: ## Install the Lefthook git hooks when npm is available on the host
	@if command -v npm >/dev/null 2>&1; then \
		{ test -x node_modules/.bin/lefthook || npm install --no-audit --no-fund; } && npx lefthook install; \
	else \
		echo "npm not found on the host: skipping the optional lefthook install"; \
	fi

.PHONY: up
up: ## Start the development stack and wait until it is healthy
	$(COMPOSE) up -d --no-build --wait
	@$(MAKE) urls

.PHONY: urls
urls: ## Print the addresses the development stack serves
	@echo "Frontend (compiled, nginx): https://dev.app.social.aleherse.com$(if $(filter 443,$(NGINX_HTTPS_PORT)),,:$(NGINX_HTTPS_PORT))"
	@echo "Frontend (Vite dev server): https://dev.app.social.aleherse.com:$(NODE_PORT)"
	@echo "API:                        https://dev.api.social.aleherse.com$(if $(filter 443,$(NGINX_HTTPS_PORT)),,:$(NGINX_HTTPS_PORT))"
	@echo "OpenAPI document:           https://dev.api.social.aleherse.com$(if $(filter 443,$(NGINX_HTTPS_PORT)),,:$(NGINX_HTTPS_PORT))/api/doc.json"
	@echo "PostgreSQL:                 localhost:$(POSTGRES_PORT) (bulletin/bulletin)"

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

.PHONY: shell
shell: ## Open an interactive shell in the PHP container (or another via `make shell service=node`)
	$(COMPOSE) exec $(or $(service),php) bash

.PHONY: db
db: ## Create and migrate the database, load the @fixtures baseline and snapshot it for the suites
	$(COMPOSE) run --rm --workdir /app/apps/api php php bin/console doctrine:database:create --if-not-exists
	$(COMPOSE) run --rm --workdir /app/apps/api php php bin/console dbal:run-sql 'CREATE SCHEMA IF NOT EXISTS bulletin'
	$(COMPOSE) run --rm --workdir /app/apps/api php php bin/console dbal:run-sql 'ALTER DATABASE bulletin SET search_path TO bulletin, public'
	$(COMPOSE) run --rm --workdir /app/apps/api php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(COMPOSE) run --rm --workdir /app/apps/api php vendor/bin/behat --tags=@fixtures
	$(COMPOSE) run --rm php sh -c 'dslr --url "$$DSLR_DATABASE_URL" delete fixtures 2>/dev/null; dslr --url "$$DSLR_DATABASE_URL" snapshot fixtures'

.PHONY: web-build
web-build: ## Build the production frontend bundle nginx serves
	$(RUN_NODE) npm run build

## --- Focussed checks (FILES=... narrows linters, PATHS=... narrows tests) -----

.PHONY: lint
lint: ## Run every linter in parallel; narrow with FILES="apps/web/src/a.tsx packages/core/src/B.php"
	+@$(MAKE) -k -j --output-sync=target $(LINT_CHECKS)

.PHONY: php-deptrac
php-deptrac: ## Check PHP architecture boundaries with Deptrac (whole project only: it judges the dependency graph)
	$(RUN_PHP) apps/api/vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress

.PHONY: php-stan
php-stan: ## Run PHPStan static analysis (FILES narrows)
	$(call narrowed,$(php_files),$(RUN_PHP) apps/api/vendor/bin/phpstan analyse -c phpstan.dist.neon --no-progress)

.PHONY: php-ecs
php-ecs: ## Apply (locally) or check (CI) the PHP coding standard with ECS (FILES narrows)
	$(call narrowed,$(php_files),$(RUN_PHP) apps/api/vendor/bin/ecs check --config ecs.php --no-progress-bar $(ECS_MODE))

.PHONY: web-tsc
web-tsc: ## Type-check the frontend (whole project only: tsc -b builds project references)
	$(RUN_NODE) npx tsc -b

.PHONY: web-eslint
web-eslint: ## Lint the frontend with ESLint (FILES narrows)
	$(call narrowed,$(web_files),$(RUN_NODE) npx eslint --max-warnings 0 --no-warn-ignored,.)

.PHONY: web-knip
web-knip: ## Check frontend project hygiene with knip (whole project only: it looks for unused exports)
	$(RUN_NODE) npx knip

.PHONY: web-prettier
web-prettier: ## Apply (locally) or check (CI) frontend formatting with Prettier (FILES narrows)
	$(call narrowed,$(web_files),$(RUN_NODE) npx prettier $(PRETTIER_MODE) --ignore-unknown,.)

.PHONY: infra-tsc
infra-tsc: ## Type-check the AWS CDK infrastructure app (whole project only)
	$(COMPOSE) run --rm --no-deps --workdir /app/infrastructure node npm run typecheck

.PHONY: tests
tests: php-unit api-tests web-unit web-e2e ## Run the four test suites against the development stack

.PHONY: php-unit
php-unit: ## Run core PHPSpec specs; narrow with PATHS=packages/core/spec/UserServiceSpec.php
	$(COMPOSE) run --rm --no-deps --workdir /app/packages/core php vendor/bin/phpspec run --no-interaction $(patsubst packages/core/%,%,$(PATHS))

.PHONY: api-tests
api-tests: ## Run the Behat API suite (needs `make db`); narrow with PATHS=apps/api/features/session.feature
	$(COMPOSE) run --rm --workdir /app/apps/api php vendor/bin/behat $(patsubst apps/api/%,%,$(PATHS))

.PHONY: web-unit
web-unit: ## Run Vitest; narrow with PATHS=apps/web/src/pages/home/ui/home-page.test.tsx
	$(RUN_NODE) npx vitest run $(patsubst apps/web/%,%,$(PATHS))

.PHONY: web-e2e
web-e2e: web-build web-e2e-run ## Build the frontend, then run Playwright; narrow with PATHS=apps/web/e2e/session.spec.ts

.PHONY: web-e2e-run
web-e2e-run: ## Run Playwright against the current frontend build (needs `make db`)
	$(COMPOSE) run --rm node npx playwright test $(patsubst apps/web/%,%,$(PATHS))

.PHONY: web-e2e-ui
web-e2e-ui: web-build ## Open the Playwright UI on https://dev.app.social.aleherse.com:9323
	$(COMPOSE) run --rm -p 9323:9323 node npx playwright test --ui-host=0.0.0.0 --ui-port=9323

## --- The full gate ----------------------------------------------------------

# The gate runs under its own Compose project, with no published ports
# (docker-compose.gate.yml), so it never touches the development stack's
# containers or database and two checkouts can gate at once.
GATE_PROJECT := social-bulletin-gate-$(shell printf '%s' '$(CURDIR)' | cksum | cut -d' ' -f1)
GATE_COMPOSE_FILE ?= docker-compose.yml:docker-compose.gate.yml
GATE := COMPOSE_PROJECT_NAME=$(GATE_PROJECT) COMPOSE_FILE=$(GATE_COMPOSE_FILE) $(MAKE)

# The gate's stages, declared once: `make ci` runs them and `make ci-stages`
# lists them from here.
# - prepare: images and dependencies every later stage runs on.
# - cheap:   no service, build or suite; run together, every failure reported.
# - build:   unit suites, the production bundle and the database snapshot, in parallel.
# - suites:  the database-backed suites, one after the other: both restore
#            the same snapshot into the same database.
GATE_PREPARE := images deps
LINT_CHECKS := php-deptrac php-stan php-ecs web-tsc web-eslint web-knip web-prettier infra-tsc
GATE_CHEAP := $(LINT_CHECKS)
GATE_BUILD := php-unit web-unit web-build db
GATE_SUITES := api-tests web-e2e-run
GATE_STAGES := $(GATE_PREPARE) $(GATE_CHEAP) $(GATE_BUILD) $(GATE_SUITES)

# What a suite stage needs from earlier tiers when re-run on its own.
STAGE_NEEDS_api-tests := db
STAGE_NEEDS_web-e2e-run := web-build db

.PHONY: ci
ci: ## The full gate CI runs: prepare, cheap checks, then builds and suites, in an isolated Compose project
	+@status=0; \
	{ $(GATE) $(GATE_PREPARE) \
	  && echo "== cheap checks: $(GATE_CHEAP)" \
	  && $(GATE) -k -j --output-sync=target $(GATE_CHEAP) \
	  && echo "== build: $(GATE_BUILD)" \
	  && $(GATE) -k -j --output-sync=target $(GATE_BUILD) \
	  && echo "== suites: $(GATE_SUITES)" \
	  && $(GATE) -k $(GATE_SUITES); } || status=$$?; \
	$(MAKE) ci-down; \
	if [ $$status -eq 0 ]; then echo "== make ci: PASSED"; else echo "== make ci: FAILED"; fi; \
	exit $$status

.PHONY: ci-stages
ci-stages: ## List the gate's stages in the order `make ci` runs them
	@echo "prepare: $(GATE_PREPARE)"
	@echo "cheap:   $(GATE_CHEAP)"
	@echo "build:   $(GATE_BUILD)"
	@echo "suites:  $(GATE_SUITES)"

.PHONY: ci-stage
ci-stage: ## Diagnose one gate stage in the gate's own project, e.g. `make ci-stage STAGE=api-tests` (never a verdict)
	@test -n "$(filter $(STAGE),$(GATE_STAGES))" || { echo "Unknown STAGE '$(STAGE)'; see make ci-stages"; exit 2; }
	+@status=0; \
	$(GATE) $(STAGE_NEEDS_$(STAGE)) $(STAGE) || status=$$?; \
	$(MAKE) ci-down; \
	echo "== diagnostic only: a single stage is not the gate's verdict"; \
	exit $$status

.PHONY: ci-down
ci-down: ## Remove the gate's containers, network and database volume
	COMPOSE_PROJECT_NAME=$(GATE_PROJECT) COMPOSE_FILE=$(GATE_COMPOSE_FILE) $(COMPOSE) down --volumes --remove-orphans

## --- Housekeeping -----------------------------------------------------------

.PHONY: clean
clean: ## Safely remove recreated local artefacts and dependencies
	rm -rf apps/api/vendor packages/core/vendor apps/web/node_modules apps/web/dist infrastructure/node_modules node_modules apps/api/var apps/api/.install.lock apps/web/.install.lock .deptrac.cache

.PHONY: destroy
destroy: ## Delete all containers, volumes and artefacts, including the gate's
	$(COMPOSE) down --volumes --remove-orphans --rmi local
	$(MAKE) ci-down
	$(MAKE) clean

.PHONY: setup-claude
setup-claude: ## Create Claude Code links (.claude/skills/*, CLAUDE.md) if missing
	@mkdir -p .claude/skills
	@for skill in .agents/skills/*/; do \
		name=$$(basename "$$skill"); \
		[ -e ".claude/skills/$$name" ] || { ln -s "../../.agents/skills/$$name" ".claude/skills/$$name" && echo "Linked skill: $$name"; }; \
	done
	@[ -e CLAUDE.md ] || { ln -s AGENTS.md CLAUDE.md && echo "Linked: CLAUDE.md -> AGENTS.md"; }
