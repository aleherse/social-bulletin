.DEFAULT_GOAL := help
.PHONY: help init build up down ps logs shell console tests clean destroy \
        db php-unit api-tests web-unit web-e2e web-e2e-ui \
        lint php-deptrac php-stan php-cs ts-check ts-lint ts-knip ts-format \
        deploy-live

# Detect UID/GID for container user mapping
export UID  := $(shell id -u)
export GID  := $(shell id -g)

##@ General

help: ## Show this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} \
	/^[a-zA-Z_0-9-]+:.*?##/ { printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2 } \
	/^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)

##@ Development

init: ## Initialise local development environment
	@test -f docker-compose.override.yml || cp docker-compose.override.yml.dist docker-compose.override.yml
	@test -f apps/api/behat.yml || cp apps/api/behat.yml.dist apps/api/behat.yml
	$(MAKE) build
	$(MAKE) up
	@echo "Waiting for services to be ready..."
	@sleep 3
	@$(MAKE) _init-lefthook
	@echo "Environment ready. Run 'make console cmd=\"doctrine:migrations:migrate\"' to apply migrations."

_init-lefthook:
	@if [ -f apps/web/node_modules/.bin/lefthook ]; then \
		docker compose exec node npx lefthook install; \
	fi

build: ## Build Docker images
	docker compose build

up: ## Start the development stack (without building)
	docker compose up -d

down: ## Stop the development stack
	docker compose down

ps: ## List running containers
	docker compose ps

logs: ## Tail logs (usage: make logs [s=service-name])
	docker compose logs -f $(s)

shell: ## Open a shell in the PHP container (usage: make shell [s=node] for Node)
	docker compose exec $(or $(s),php) bash

console: ## Run a Symfony console command (usage: make console cmd="cache:clear")
	docker compose exec php php bin/console $(cmd)

##@ Database

db: ## Create DB, run migrations, load fixtures, create DSLR snapshot
	docker compose exec php php bin/console doctrine:database:create --if-not-exists
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec php php bin/console behat --tags=@fixtures --no-interaction 2>/dev/null || true
	docker compose exec php dslr snapshot fixtures

##@ Testing

php-unit: ## Run PHPSpec unit tests (packages/core)
	docker compose run --rm php bash -c "cd /var/www/app/packages/core && php vendor/bin/phpspec run --no-interaction"

api-tests: ## Run Behat API/integration tests (apps/api)
	docker compose run --rm php bash -c "cd /var/www/app/apps/api && dslr restore fixtures && php vendor/bin/behat --no-interaction"

web-unit: ## Run Vitest unit tests (apps/web)
	docker compose run --rm node npx vitest run

web-e2e: ## Run Playwright E2E tests headless (apps/web)
	docker compose run --rm node npx playwright test

web-e2e-ui: ## Open Playwright UI mode (apps/web)
	docker compose run --rm --service-ports node npx playwright test --ui

tests: ## Run all test suites
	$(MAKE) php-unit
	$(MAKE) api-tests
	$(MAKE) web-unit
	$(MAKE) web-e2e

##@ Linting

lint: ## Run all linting and static analysis checks
	$(MAKE) php-deptrac
	$(MAKE) php-stan
	$(MAKE) php-cs
	$(MAKE) ts-check
	$(MAKE) ts-lint
	$(MAKE) ts-knip
	$(MAKE) ts-format

php-deptrac: ## Run Deptrac PHP dependency analysis
	docker compose run --rm php bash -c "cd /var/www/app/apps/api && php vendor/bin/deptrac analyse"

php-stan: ## Run PHPStan static analysis
	docker compose run --rm php bash -c "cd /var/www/app/apps/api && php vendor/bin/phpstan analyse"

php-cs: ## Run Easy Coding Standard PHP style checks
	docker compose run --rm php bash -c "cd /var/www/app/apps/api && php vendor/bin/ecs check"

ts-check: ## Run TypeScript type checking
	docker compose run --rm node npx tsc --noEmit

ts-lint: ## Run ESLint on frontend code
	docker compose run --rm node npx eslint .

ts-knip: ## Run Knip unused dependency/export check
	docker compose run --rm node npx knip

ts-format: ## Run Prettier format check
	docker compose run --rm node npx prettier --check .

##@ Cleanup

clean: ## Remove vendor, node_modules, and build artefacts (keeps containers)
	docker compose run --rm php bash -c "rm -rf /var/www/app/apps/api/vendor /var/www/app/apps/api/var"
	docker compose run --rm node bash -c "rm -rf /var/www/app/apps/web/node_modules /var/www/app/apps/web/dist"

destroy: ## Stop and remove all containers and named volumes
	docker compose down -v --remove-orphans

##@ Deployment

deploy-live: ## Deploy to live AWS environment via CDK
	cd infrastructure && npm ci && npx cdk deploy SocialBulletinStack-live --require-approval never
