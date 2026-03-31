.PHONY: up down build shell composer-install npm-install npm-dev migrate migrate-fresh tinker test clean help

# =============================================================================
# DOCKER
# =============================================================================
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

ps:
	docker compose ps

clean:
	docker compose down -v
	docker compose rm -f
	@rm -rf vendor node_modules

# =============================================================================
# DEPENDENCIES
# =============================================================================
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

# =============================================================================
# DATABASE
# =============================================================================
migrate:
	docker compose exec app php artisan migrate

migrate-fresh:
	docker compose exec app php artisan migrate:fresh --seed

migrate-rollback:
	docker compose exec app php artisan migrate:rollback

seed:
	docker compose exec app php artisan db:seed

# =============================================================================
# CACHE & CONFIG
# =============================================================================
cache-clear:
	docker compose exec app php artisan cache:clear

config-clear:
	docker compose exec app php artisan config:clear

clear-all:
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

# =============================================================================
# LARAVEL
# =============================================================================
tinker:
	docker compose exec app php artisan tinker

artisan:
	docker compose exec app php artisan $(cmd)

test:
	docker compose exec app php artisan test

# =============================================================================
# QUEUE (RabbitMQ)
# =============================================================================
queue-work:
	docker compose exec app php artisan queue:work --tries=3 --timeout=90

queue-listen:
	docker compose exec app php artisan queue:listen

queue-retry:
	docker compose exec app php artisan queue:retry all

queue-failed:
	docker compose exec app php artisan queue:failed

queue-flush:
	docker compose exec app php artisan queue:flush

# =============================================================================
# REVERB (WebSocket)
# =============================================================================
reverb-start:
	docker compose exec app php artisan reverb:start

reverb-logs:
	docker compose logs -f reverb

# =============================================================================
# LOGS
# =============================================================================
logs:
	docker compose logs -f app

logs-all:
	docker compose logs -f

logs-worker:
	docker compose logs -f queue_worker

logs-reverb:
	docker compose logs -f reverb

logs-nginx:
	docker compose logs -f nginx

# =============================================================================
# SETUP
# =============================================================================
setup:
	@echo "🚀 Setting up DockaBase..."
	@cp .env.example .env
	@make up
	@sleep 10
	@make composer-install
	@make npm-install
	@docker compose exec app php artisan key:generate
	@make migrate
	@echo "✅ DockaBase is ready!"
	@echo ""
	@echo "Services:"
	@echo "  • App:      http://localhost"
	@echo "  • pgAdmin:  http://localhost:5050"
	@echo "  • RabbitMQ: http://localhost:15672"
	@echo "  • MinIO:    http://localhost:9001"
	@echo ""
	@echo "Run 'make npm-dev' to start Vite"

# =============================================================================
# HELP
# =============================================================================
help:
	@echo "DockaBase Makefile Commands:"
	@echo ""
	@echo "Docker:"
	@echo "  make up              - Start containers"
	@echo "  make down            - Stop containers"
	@echo "  make restart         - Restart containers"
	@echo "  make build           - Rebuild containers (no cache)"
	@echo "  make rebuild         - Down + Build + Up"
	@echo "  make shell           - Access app container"
	@echo "  make ps              - List containers"
	@echo "  make clean           - Remove volumes + vendor"
	@echo ""
	@echo "Dependencies:"
	@echo "  make composer-install - Install PHP deps"
	@echo "  make composer-update  - Update PHP deps"
	@echo "  make npm-install      - Install Node deps"
	@echo "  make npm-dev          - Start Vite dev server"
	@echo "  make npm-build        - Build for production"
	@echo ""
	@echo "Database:"
	@echo "  make migrate          - Run migrations"
	@echo "  make migrate-fresh    - Fresh migration + seed"
	@echo "  make migrate-rollback - Rollback last batch"
	@echo "  make seed             - Run seeders"
	@echo ""
	@echo "Queue (RabbitMQ):"
	@echo "  make queue-work       - Process jobs (once)"
	@echo "  make queue-listen     - Listen for jobs"
	@echo "  make queue-retry      - Retry all failed"
	@echo "  make queue-failed     - List failed jobs"
	@echo "  make queue-flush      - Delete all failed"
	@echo ""
	@echo "Reverb (WebSocket):"
	@echo "  make reverb-start     - Start Reverb server"
	@echo "  make reverb-logs      - View Reverb logs"
	@echo ""
	@echo "Laravel:"
	@echo "  make artisan cmd=xxx  - Run artisan command"
	@echo "  make tinker           - Open tinker"
	@echo "  make test             - Run tests"
	@echo "  make clear-all        - Clear all caches"
	@echo ""
	@echo "Logs:"
	@echo "  make logs             - App logs"
	@echo "  make logs-all         - All services"
	@echo "  make logs-worker      - Queue worker logs"
	@echo "  make logs-reverb      - WebSocket logs"
	@echo "  make logs-nginx       - Nginx logs"

