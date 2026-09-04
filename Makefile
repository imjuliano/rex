.DEFAULT_GOAL := help

.PHONY: help up build down down-v logs shell test openapi

help: ## Exibe os comandos disponíveis
	@echo Comandos disponiveis:
	@echo   make up       - sobe a aplicacao (docker compose up -d --build)
	@echo   make build    - builda os containers
	@echo   make down     - derruba os containers
	@echo   make down-v   - derruba e remove volumes
	@echo   make logs     - exibe logs do backend
	@echo   make shell    - abre shell no container backend
	@echo   make test     - roda a suite de testes PHP
	@echo   make openapi  - regenera o public/openapi.json

up: ## Sobe a aplicação
	docker compose up -d --build

build: ## Builda os containers
	docker compose build

down: ## Derruba os containers
	docker compose down

down-v: ## Derruba e remove volumes
	docker compose down -v

logs: ## Exibe logs do backend
	docker compose logs -f backend

shell: ## Abre um shell no container backend
	docker compose exec backend sh

test: ## Roda a suite de testes PHP
	docker run --rm -v "$(CURDIR)/backend:/app" -w /app composer:2 php vendor/bin/phpunit

openapi: ## Regenera o public/openapi.json
	docker run --rm -v "$(CURDIR)/backend:/app" -w /app composer:2 php vendor/bin/openapi src -o public/openapi.json
