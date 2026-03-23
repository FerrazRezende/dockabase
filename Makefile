.PHONY: up down build shell composer-install npm-install npm-dev migrate migrate-fresh tinker test clean help

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

build:
	docker compose build --no-cache

rebuild:
	docker compose down 
	docker compose build --no-cache
	docker compose up -d

shell:
	docker compose exec app bash

composer-install:
	docker compose exec app composer install

composer-update:
	docker compose exec app composer update

npm-install:
	docker compose exec app npm install

npm-dev:
	docker compose exec app npm run dev

npm-build:
	docker compose exec app npm run build

migrate:
	docker compose exec app php artisan migrate

migrate-fresh:
	docker compose exec app php artisan migrate:fresh --seed

migrate-rollback:
	docker compose exec app php artisan migrate:rollback

seed:
	docker compose exec app php artisan db:seed

cache-clear:
	docker compose exec app php artisan cache:clear

config-clear:
	docker compose exec app php artisan config:clear

clear-all:
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

tinker:
	docker compose exec app php artisan tinker

artisan:
	docker compose exec app php artisan $(cmd)

test:
	docker compose exec app php artisan test

queue-work:
	docker compose exec app php artisan queue:work

logs:
	docker compose logs -f app

logs-all:
	docker compose logs -f

setup:
	@echo "Setting up DockaBase"
	@cp .env.example .env
	@make up
	@sleep 5
	@make composer-install
	@make npm-install
	@docker compose exec app php artisan key:generate
	@make migrate
	@make npm-dev
	@echo "DockaBase is ready! Access at http://localhost"

ps:
	docker compose ps

clean:
	docker compose down -v
	docker compose rm -f
	@rm -rf vendor node_modules

help:
	@echo "DockaBase Makefile Commands:"
	@echo ""
	@echo "Docker:"
	@echo "  make up              - Start containers"
	@echo "  make down            - Stop containers"
	@echo "  make restart         - Restart containers"
	@echo "  make build           - Rebuild containers"
	@echo "  make shell           - Access app container"
	@echo ""
	@echo "Dependencies:"
	@echo "  make composer-install - Install PHP deps"
	@echo "  make npm-install      - Install Node deps"
	@echo "  make npm-dev          - Run Vite dev server"
	@echo ""
	@echo "Database:"
	@echo "  make migrate          - Run migrations"
	@echo "  make migrate-fresh    - Fresh migration with seed"
	@echo ""
	@echo "Laravel:"
	@echo "  make artisan cmd=xxx  - Run artisan command"
	@echo "  make tinker           - Open tinker"
	@echo "  make test             - Run tests"
	@echo ""
	@echo "Logs:"
	@echo "  make logs             - Follow app logs"
	@echo "  make logs-all         - Follow all logs"

