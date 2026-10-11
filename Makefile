.DEFAULT_GOAL := help
SHELL := /bin/sh

COMPOSE := docker compose
DEV_IMAGE := product-import-dev
RUN_DEV := docker run --rm -v "$(CURDIR)/backend:/var/www/html" -v /var/www/html/vendor $(DEV_IMAGE)

.PHONY: help setup env key up down restart logs ps migrate seed fresh shell \
        dev-image test phpstan cs cs-fix lint front-test e2e clean

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

setup: env key up ## First run: create .env, generate APP_KEY, build and start the stack
	@echo ""
	@echo "  App:        http://localhost:$${APP_PORT:-8080}  (admin@example.com / password)"
	@echo "  Swagger:    http://localhost:$${APP_PORT:-8080}/docs"
	@echo "  RabbitMQ:   http://localhost:15672  (guest / guest)"

env: ## Create .env from .env.example (if missing)
	@test -f .env || cp .env.example .env

key: ## Generate APP_KEY in .env (if empty)
	@if grep -qE '^APP_KEY=$$' .env; then \
		KEY="base64:$$(docker run --rm php:8.4-cli-alpine php -r 'echo base64_encode(random_bytes(32));')"; \
		sed -i.bak "s|^APP_KEY=$$|APP_KEY=$$KEY|" .env && rm -f .env.bak; \
		echo "APP_KEY generated"; \
	fi

up: ## Build images and start all services
	$(COMPOSE) up -d --build --wait

down: ## Stop all services
	$(COMPOSE) down

restart: down up ## Restart the stack

logs: ## Follow logs of app and worker
	$(COMPOSE) logs -f app worker

ps: ## Show service status
	$(COMPOSE) ps

migrate: ## Run database migrations
	$(COMPOSE) exec app php artisan migrate --force

seed: ## Fill the database with demo fixtures (products, attributes, images)
	$(COMPOSE) exec app php artisan db:seed --force

fresh: ## Drop all tables, migrate and seed
	$(COMPOSE) exec app php artisan migrate:fresh --seed --force

shell: ## Shell inside the app container
	$(COMPOSE) exec app sh

dev-image: ## Build the backend image with dev dependencies (tests, linters)
	docker build --target dev -t $(DEV_IMAGE) ./backend

test: dev-image ## Backend tests: unit, feature (CRUD, import) and API e2e
	$(RUN_DEV) php artisan test

phpstan: dev-image ## Static analysis (PHPStan level 6)
	$(RUN_DEV) vendor/bin/phpstan analyse --memory-limit=1G

cs: dev-image ## Code style check (PHP CS Fixer)
	$(RUN_DEV) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: dev-image ## Fix code style
	$(RUN_DEV) vendor/bin/php-cs-fixer fix

lint: phpstan cs ## All backend linters

front-test: ## Frontend unit tests (Vitest)
	cd frontend && npm ci && npm run test:ci

e2e: ## Browser e2e tests (Playwright) against the running stack
	cd frontend && npm ci && npx playwright install --with-deps chromium && npm run e2e

clean: ## Stop the stack and remove volumes (DB, images)
	$(COMPOSE) down -v
