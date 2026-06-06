SHELL := /bin/sh
COMPOSE := docker compose
SERVICE ?=

.PHONY: help init up down logs ps shell api-shell web-shell console db dev-web certs checks php-deptrac php-stan php-cs php-translation-lint ts-type ts-lint ts-knip ts-format hook-pre-commit hook-pre-push php-unit web-unit clean destroy tests

help:
	@printf '%s\n' 'Available targets:'
	@printf '%s\n' '  make init        Prepare local environment and start containers'
	@printf '%s\n' '  make up          Start the development stack'
	@printf '%s\n' '  make down        Stop the development stack'
	@printf '%s\n' '  make logs        Show logs, or pass SERVICE=name'
	@printf '%s\n' '  make ps          List running containers'
	@printf '%s\n' '  make shell       Open shell in SERVICE, defaults to api'
	@printf '%s\n' '  make api-shell   Open shell in API container'
	@printf '%s\n' '  make web-shell   Open shell in web tooling container'
	@printf '%s\n' '  make console     Run Symfony console, pass ARGS="..."'
	@printf '%s\n' '  make db          Create database, migrate, load fixtures, snapshot DB'
	@printf '%s\n' '  make dev-web     Run Vite dev server with HMR on port 3000'
	@printf '%s\n' '  make certs       Generate local TLS certificates with mkcert'
	@printf '%s\n' '  make checks      Run all linting and static-analysis checks'
	@printf '%s\n' '  make hook-pre-commit Run fast hook checks'
	@printf '%s\n' '  make hook-pre-push   Run medium hook checks'
	@printf '%s\n' '  make tests       Run the full automated test suite'
	@printf '%s\n' '  make clean       Remove recreated local artefacts and dependencies'
	@printf '%s\n' '  make destroy     Remove containers, volumes, and local artefacts'

init:
	@test -f .env || cp .env.example .env
	$(COMPOSE) up --build --detach
	$(MAKE) certs
	$(COMPOSE) run --rm api mkdir -p config/jwt
	$(COMPOSE) run --rm api php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

up:
	$(COMPOSE) up --detach

down:
	$(COMPOSE) down

logs:
	@if [ -n "$(SERVICE)" ]; then $(COMPOSE) logs --follow $(SERVICE); else $(COMPOSE) logs --follow; fi

ps:
	$(COMPOSE) ps

shell:
	$(COMPOSE) run --rm $${SERVICE:-api} sh

api-shell:
	$(COMPOSE) run --rm api sh

web-shell:
	$(COMPOSE) run --rm web sh

console:
	$(COMPOSE) run --rm api php bin/console $(ARGS)

db:
	$(COMPOSE) run --rm api mkdir -p config/jwt
	$(COMPOSE) run --rm api php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
	$(COMPOSE) run --rm api php bin/console doctrine:database:create --if-not-exists
	$(COMPOSE) run --rm api php bin/console doctrine:migrations:migrate --no-interaction
	$(COMPOSE) run --rm api vendor/bin/behat features/fixtures.feature --tags=@fixtures
	$(COMPOSE) run --rm api dslr snapshot fixtures

dev-web:
	$(COMPOSE) run --rm --service-ports web npm run dev -- --host 0.0.0.0

certs:
	$(COMPOSE) run --rm mkcert sh -lc 'mkdir -p docker/nginx/certs/ca && test -f docker/nginx/certs/bulletin.local.pem || CAROOT=/workspace/docker/nginx/certs/ca mkcert -cert-file docker/nginx/certs/bulletin.local.pem -key-file docker/nginx/certs/bulletin.local-key.pem api.bulletin.local app.bulletin.local localhost 127.0.0.1 ::1'

checks: php-deptrac php-stan php-cs php-translation-lint ts-type ts-lint ts-knip ts-format

php-deptrac:
	$(COMPOSE) run --rm api vendor/bin/deptrac analyse --config-file=deptrac.yaml

php-stan:
	$(COMPOSE) run --rm api vendor/bin/phpstan analyse --configuration=phpstan.neon

php-cs:
	$(COMPOSE) run --rm api vendor/bin/ecs check

php-translation-lint:
	$(COMPOSE) run --rm api php bin/console lint:yaml translations
	$(COMPOSE) run --rm api php bin/console translation:extract en --force --format=yaml --domain=validators

ts-type:
	$(COMPOSE) run --rm web npm run typecheck

ts-lint:
	$(COMPOSE) run --rm web npm run lint

ts-knip:
	$(COMPOSE) run --rm web npm run knip

ts-format:
	$(COMPOSE) run --rm web npm run format:check

hook-pre-commit: php-cs ts-type ts-lint ts-format

hook-pre-push: ts-knip php-unit web-unit

php-unit:
	$(COMPOSE) run --rm --workdir /workspace/packages/core api vendor/bin/phpspec run --format=pretty

web-unit:
	$(COMPOSE) run --rm web npm run test:unit

tests:
	$(COMPOSE) run --rm api vendor/bin/behat --tags='~@fixtures'
	$(COMPOSE) run --rm --workdir /workspace/packages/core api vendor/bin/phpspec run
	$(COMPOSE) run --rm web npm test
	$(MAKE) certs
	$(COMPOSE) run --rm web npm run build
	$(COMPOSE) up --detach nginx
	$(COMPOSE) run --rm playwright npx playwright test

clean:
	rm -rf apps/web/node_modules apps/web/dist apps/web/coverage apps/web/.vite
	rm -rf apps/api/vendor apps/api/var/cache apps/api/var/log apps/api/coverage
	rm -rf packages/core/vendor packages/core/coverage
	rm -rf playwright-report test-results .dslr

destroy: down clean
	$(COMPOSE) down --volumes --remove-orphans
