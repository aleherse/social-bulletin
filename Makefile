COMPOSE := docker compose
PHP := $(COMPOSE) run --rm php
NODE := $(COMPOSE) run --rm node

.PHONY: help init up down logs shell console tests tests-api tests-unit-api tests-core tests-web build-web dev-web clean

help:
	@printf '%s\n' 'Available targets:'
	@printf '%s\n' '  init        Build containers and install dependencies'
	@printf '%s\n' '  up          Start development stack'
	@printf '%s\n' '  down        Stop development stack'
	@printf '%s\n' '  logs        Follow service logs'
	@printf '%s\n' '  shell-php   Open shell in PHP container'
	@printf '%s\n' '  shell-node  Open shell in Node container'
	@printf '%s\n' '  console     Run a Symfony console command (e.g. make console CMD="debug:router")'
	@printf '%s\n' '  tests       Run all tests'
	@printf '%s\n' '  tests-api   Run API Behat integration tests'
	@printf '%s\n' '  tests-unit-api Run API PHPUnit unit tests'
	@printf '%s\n' '  tests-core  Run core phpspec tests'
	@printf '%s\n' '  tests-web   Run web Vitest tests'
	@printf '%s\n' '  build-web   Compile web frontend assets'
	@printf '%s\n' '  dev-web     Start web frontend dev server with hot-reload'
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

shell-php:
	$(PHP) bash

shell-node:
	$(NODE) sh

console:
	$(PHP) php apps/api/bin/console $(CMD)

tests: tests-core tests-unit-api tests-api tests-web

tests-unit-api:
	$(PHP) apps/api/vendor/bin/phpunit --configuration apps/api/phpunit.xml.dist

tests-api:
	$(PHP) apps/api/vendor/bin/behat --config apps/api/behat.yml

tests-core:
	$(PHP) packages/core/vendor/bin/phpspec run --config packages/core/phpspec.yml

tests-web:
	$(NODE) npm test --prefix apps/web

build-web:
	$(NODE) npm run build --prefix apps/web

dev-web:
	LOCAL_UID=$$(id -u) LOCAL_GID=$$(id -g) $(COMPOSE) run --rm -p 5173:5173 node npm run dev --prefix apps/web

clean:
	$(PHP) sh -lc 'rm -rf apps/api/vendor apps/api/var packages/core/vendor apps/web/node_modules apps/web/dist apps/web/coverage'
