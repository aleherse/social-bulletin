SHELL := /bin/sh
COMPOSE := docker compose
SERVICE ?=

.PHONY: help copy-env init build up down logs ps shell api-shell web-shell console db certs checks php-deptrac php-stan php-cs php-translation-lint ts-type ts-lint ts-knip ts-format hook-pre-commit hook-pre-push tests api-tests php-unit web-unit web-build web-e2e web-e2e-ui clean destroy

help: ## Show available targets
	@printf '%s\n' 'Available targets:'
	@awk 'BEGIN { FS = ":.*##" } /^[A-Za-z0-9][A-Za-z0-9_-]*:.*##/ { printf "  make %-20s %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

copy-env: ## Copy default environment file when .env is missing
	cp --update=none .env .env.local
	cp --update=none apps/api/.env apps/api/.env.local

init: ## Prepare local environment and start containers
	$(MAKE) copy-env
	$(MAKE) certs
	$(COMPOSE) build
	$(MAKE) up
	$(MAKE) build
	./apps/web/node_modules/lefthook-linux-x64/bin/lefthook install
	$(COMPOSE) exec api mkdir -p config/jwt
	$(COMPOSE) exec api php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

build: ## Install package-manager dependencies
	$(MAKE) up
	$(COMPOSE) exec --workdir /workspace/apps/api api composer install
	$(COMPOSE) exec --workdir /workspace/packages/core api composer install
	$(COMPOSE) exec --workdir /workspace/apps/web web npm install
	$(COMPOSE) exec --workdir /workspace/apps/web web npm run build
	$(COMPOSE) exec --workdir /workspace/deploy/aws web npm install

up: ## Start development stack
	@if [ -z "$$($(COMPOSE) ps --quiet --status running api)" ]; then \
		$(COMPOSE) up --detach postgres api web nginx; \
	else \
		printf '%s\n' 'Development stack already running'; \
	fi

down: ## Stop development stack
	$(COMPOSE) down

logs: ## Show logs, or pass SERVICE=name
	@if [ -n "$(SERVICE)" ]; then $(COMPOSE) logs --follow $(SERVICE); else $(COMPOSE) logs --follow; fi

ps: ## List running containers
	$(COMPOSE) ps

shell: ## Open shell in SERVICE, defaults to api
	$(MAKE) up
	$(COMPOSE) exec $${SERVICE:-api} sh

api-shell: ## Open shell in API container
	$(COMPOSE) exec api sh

web-shell: ## Open shell in web tooling container
	$(COMPOSE) exec web sh

console: ## Run Symfony console, pass ARGS="..."
	$(COMPOSE) exec api php bin/console $(ARGS)

db: up ## Create database, migrate, load fixtures, snapshot DB
	$(COMPOSE) exec api php bin/console doctrine:database:drop --if-exists --force
	$(COMPOSE) exec api php bin/console doctrine:database:create --if-not-exists
	$(COMPOSE) exec api php bin/console doctrine:migrations:migrate --no-interaction
	$(COMPOSE) exec api vendor/bin/behat features/fixtures.feature --tags=@fixtures
	$(COMPOSE) exec api dslr snapshot fixtures --yes

certs: ## Generate local TLS certificates with mkcert
	test -f docker/nginx/certs/bulletin.local.pem || docker run --volume ${PWD}/docker/nginx/certs:/app --workdir /app --env "CAROOT=/app" --user 1000:1000 alpine/mkcert -cert-file bulletin.local.pem -key-file bulletin.local-key.pem api.bulletin.local app.bulletin.local localhost 127.0.0.1

checks: up php-deptrac php-stan php-cs php-translation-lint ts-type ts-lint ts-knip ts-format ## Run all linting and static-analysis checks

php-deptrac: ## Run PHP dependency analysis
	$(COMPOSE) exec api vendor/bin/deptrac analyse --config-file=deptrac.yaml

php-stan: ## Run PHP static analysis
	$(COMPOSE) exec api vendor/bin/phpstan analyse --configuration=phpstan.dist.neon

php-cs: ## Run PHP coding-standard checks
	$(COMPOSE) exec api vendor/bin/ecs check

php-translation-lint: ## Run Symfony translation lint checks
	$(COMPOSE) exec api php bin/console lint:yaml translations
	$(COMPOSE) exec api php bin/console translation:extract en --force --format=yaml --domain=validators

ts-type: ## Run TypeScript type checks
	$(COMPOSE) exec web npm run typecheck

ts-lint: ## Run TypeScript lint checks
	$(COMPOSE) exec web npm run lint

ts-knip: ## Run frontend project hygiene checks
	$(COMPOSE) exec web npm run knip

ts-format: ## Run frontend format checks
	$(COMPOSE) exec web npm run format:check

hook-pre-commit: up php-cs ts-type ts-lint ts-format ## Run fast hook checks

hook-pre-push: up ts-knip php-unit web-unit ## Run medium hook checks

tests: up api-tests php-unit web-unit web-e2e ## Run full automated test suite

api-tests: ## Run API Behat tests
	$(COMPOSE) exec api vendor/bin/behat

php-unit: ## Run core PHPSpec tests
	$(COMPOSE) exec --workdir /workspace/packages/core api vendor/bin/phpspec run --format=pretty

web-unit: ## Run web unit tests
	$(COMPOSE) exec web npm run test:unit

web-build: up ## Build web production assets
	$(COMPOSE) exec web npm run build

web-e2e: web-build ## Run Playwright end-to-end tests
	$(COMPOSE) run --rm playwright npx playwright test

web-e2e-ui: web-build ## Start Playwright UI for host browser at http://localhost:9324
	$(COMPOSE) run --rm --publish 9324:9324 playwright npx playwright test --ui --ui-host 0.0.0.0 --ui-port 9324

clean: ## Remove recreated local artefacts and dependencies
	rm -rf apps/web/node_modules apps/web/dist apps/web/coverage apps/web/.vite apps/web/test-results
	rm -rf apps/api/vendor apps/api/var/cache apps/api/var/log apps/api/coverage
	rm -rf packages/core/vendor packages/core/coverage

destroy: down clean ## Remove containers, volumes, and local artefacts
	$(COMPOSE) down --volumes --remove-orphans
	$(COMPOSE) rm --volumes --force
