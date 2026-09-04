.DEFAULT_GOAL := help

COMPOSE = docker compose

.PHONY: help up down build rebuild logs ps shell-backend shell-frontend db test lint lint-fix clean

help: ## Show available commands
	@echo Available commands:
	@echo   make up            Start backend, database and frontend
	@echo   make down          Stop all services
	@echo   make build         Build Docker images
	@echo   make rebuild       Rebuild images and restart services
	@echo   make logs          Tail logs from all services
	@echo   make ps            List running containers
	@echo   make shell-backend Open bash inside the backend container
	@echo   make shell-frontend Open sh inside the frontend container
	@echo   make db            Open MySQL as root inside the database container
	@echo   make test          Run tests (not configured yet)
	@echo   make lint          Check code style (php-cs-fixer dry-run + phpcs)
	@echo   make lint-fix      Auto-fix code style and re-check with phpcs
	@echo   make clean         Stop and remove containers, networks and volumes

up: ## Start backend, database and frontend
	if not exist .env copy .env.example .env
	$(COMPOSE) up -d

down: ## Stop all services
	$(COMPOSE) down

build: ## Build Docker images
	$(COMPOSE) build

rebuild: ## Rebuild images and restart services
	$(COMPOSE) up -d --build

logs: ## Tail logs from all services
	$(COMPOSE) logs -f

ps: ## List running containers
	$(COMPOSE) ps

shell-backend: ## Open bash inside the backend container
	$(COMPOSE) exec backend bash

shell-frontend: ## Open sh inside the frontend container
	$(COMPOSE) exec frontend sh

db: ## Open MySQL as root inside the database container
	$(COMPOSE) exec db mysql -uroot -prootpass

test: ## Run PHP unit tests
	$(COMPOSE) exec backend sh -c "composer install && ./vendor/bin/phpunit"

lint: ## Check code style (php-cs-fixer dry-run + phpcs)
	$(COMPOSE) exec backend sh -c "composer install && ./vendor/bin/php-cs-fixer fix --dry-run --diff && ./vendor/bin/phpcs"

lint-fix: ## Auto-fix code style with php-cs-fixer and re-check with phpcs
	$(COMPOSE) exec backend sh -c "composer install && ./vendor/bin/php-cs-fixer fix && ./vendor/bin/phpcs"

clean: ## Stop and remove containers, networks and volumes
	$(COMPOSE) down -v
