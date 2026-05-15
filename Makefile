COMPOSE := docker compose
PHP := $(COMPOSE) run --rm php
NODE := $(COMPOSE) run --rm node

.PHONY: help init up down logs shell console tests tests-api tests-core tests-web build-web clean

help:
	@printf '%s\n' 'Available targets:'
	@printf '%s\n' '  init        Build containers and install dependencies'
	@printf '%s\n' '  up          Start development stack'
	@printf '%s\n' '  down        Stop development stack'
	@printf '%s\n' '  logs        Follow service logs'
	@printf '%s\n' '  shell       Open shell in PHP container'
	@printf '%s\n' '  console     Run a Symfony console command (e.g. make console CMD="debug:router")'
	@printf '%s\n' '  tests       Run all tests'
	@printf '%s\n' '  tests-api   Run API Behat tests'
	@printf '%s\n' '  tests-core  Run core phpspec tests'
	@printf '%s\n' '  tests-web   Run web Vitest tests'
	@printf '%s\n' '  build-web   Compile web frontend assets'
	@printf '%s\n' '  clean       Remove generated local dependencies and cache'

init:
	LOCAL_UID=$$(id -u) LOCAL_GID=$$(id -g) $(COMPOSE) build
	$(PHP) composer install --working-dir=packages/core
	$(PHP) composer install --working-dir=apps/api
	$(NODE) npm install --prefix apps/web
	$(NODE) npm run build --prefix apps/web

up:
	LOCAL_UID=$$(id -u) LOCAL_GID=$$(id -g) $(COMPOSE) up -d

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f

shell:
	$(PHP) sh

console:
	$(PHP) php apps/api/bin/console $(CMD)

tests: tests-core tests-api tests-web

tests-api:
	$(PHP) apps/api/vendor/bin/behat --config apps/api/behat.yml

tests-core:
	$(PHP) packages/core/vendor/bin/phpspec run --config packages/core/phpspec.yml

tests-web:
	$(NODE) npm test --prefix apps/web

build-web:
	$(NODE) npm run build --prefix apps/web

clean:
	$(PHP) sh -lc 'rm -rf apps/api/vendor apps/api/var packages/core/vendor apps/web/node_modules apps/web/dist apps/web/coverage'
