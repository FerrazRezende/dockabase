# FrankenPHP + Laravel Octane Migration

**Date:** 2026-04-22
**Status:** Draft
**Scope:** Infrastructure — migrate PHP-FPM to FrankenPHP with Laravel Octane

## Context

DockaBase currently runs on `php:8.4-fpm` with Nginx as FastCGI proxy. The planned architecture (per CLAUDE.md) uses Laravel Octane. FrankenPHP is the chosen Octane driver — modern, Go-based, worker-mode, maintained by Kévin Dunglas.

## Architecture

```
Client → Nginx (reverse proxy) → FrankenPHP (Octane worker) → Laravel
                                      :8000 (HTTP)
                                         │
                                    Dispatch to RabbitMQ
                                         │
                                    Queue Workers (PHP CLI)
                                         │
                              PostgreSQL / Redis / MinIO
```

### Key Principle: FrankenPHP is a Thin Dispatcher

FrankenPHP handles HTTP requests only: route, validate, dispatch to RabbitMQ, return response. All business logic runs in queue workers. This eliminates state leak risk and keeps workers stateless.

**Nothing is processed synchronously by FrankenPHP.** Everything goes through RabbitMQ first.

### State Leak Prevention

| Strategy | Implementation |
|----------|---------------|
| Thin dispatcher | FrankenPHP only validates + dispatches to queue |
| No business state in HTTP process | Services in HTTP context are validation-only |
| Octane warm table | Only cache/config entries warmed |
| Queue-driven execution | All DB, file, provisioning ops run in workers |
| Feature test | Sequential requests verify state isolation |

## Changes

### 1. Dockerfile — Rewrite

Base image: `dunglas/frankenphp:latest-php8.4`

- Replace `php:8.4-fpm` with `dunglas/frankenphp:latest-php8.4`
- Install same PHP extensions (pdo_pgsql, redis, gd, zip, pcntl, bcmath, etc.)
- Install Composer, Node.js (same as current)
- CMD: `php artisan octane:frankenphp --host=0.0.0.0 --port=8000`
- Remove `EXPOSE 9000`, add `EXPOSE 8000`

### 2. docker-compose.yml

| Service | Change |
|---------|--------|
| `app` | Expose port 8000, CMD via Octane |
| `nginx` | Upstream changes from FastCGI `app:9000` to HTTP `app:8000` |
| `queue_worker` | No change — already uses PHP CLI |
| `reverb` | No change — already uses PHP CLI |
| `vite` | No change — Node.js process |
| `postgres`, `redis`, `rabbitmq`, `minio` | No change |

### 3. docker/nginx/default.conf

- Replace `fastcgi_pass` with `proxy_pass http://app:8000`
- Remove FastCGI-specific block (`location ~ \.php$`)
- Add standard HTTP proxy headers
- Keep static file caching, gzip, security headers

### 4. .env / .env.example

```env
OCTANE_SERVER=frankenphp
OCTANE_PORT=8000
```

### 5. Composer Packages

```
composer require laravel/octane
```

FrankenPHP binary is included in the Docker image — no separate composer package needed.

### 6. config/octane.php

Published via `php artisan octane:install`. Configure:

- `warm` array: only `config`, `cache` stores — no models, no services
- `listeners`: `RequestReceived`, `TaskReceived`, `TickReceived` — flush any app-level state
- No tables warmed (prevent state persistence)

### 7. docker/php/php.ini

- Remove `fastcgi.logging = 0` (not FPM anymore)
- Keep OPcache settings (FrankenPHP uses opcache)
- Keep all other PHP settings

### 8. MinIO Upload Flow (Queue-Based)

```
Client → POST /upload → FrankenPHP validates → Dispatch UploadFileJob → RabbitMQ
                                                                          ↓
                                                              Worker: upload to MinIO
                                                                          ↓
                                                              Event: FileUploaded
                                                                          ↓
                                                              Client via WebSocket
```

### 9. RabbitMQ as Universal Message Broker

All operations that involve I/O or business logic go through RabbitMQ:

| Operation | Job | Worker |
|-----------|-----|--------|
| Database creation | `CreateDatabaseJob` | `queue_worker` |
| File uploads | `UploadFileJob` | `queue_worker` |
| Schema migrations | `RunMigrationJob` | `queue_worker` |
| Backups | `CreateBackupJob` | `queue_worker` |
| Notifications | `SendNotificationJob` | `queue_worker` |

FrankenPHP HTTP handler flow:
1. Receive request
2. Validate via FormRequest
3. Dispatch job to RabbitMQ
4. Return 202 Accepted with job tracking info (or appropriate response)

### 10. State Leak Test

Feature test that:
1. Creates an entity via request A
2. Makes request B with different context
3. Verifies request B has no residual state from request A
4. Runs both via Octane test trait (`WithFaker` + sequential requests)

## Files Changed

| File | Action |
|------|--------|
| `Dockerfile` | Rewrite — FrankenPHP base |
| `docker-compose.yml` | Edit — app port, nginx upstream |
| `docker/nginx/default.conf` | Edit — FastCGI → proxy_pass |
| `docker/php/php.ini` | Edit — remove fastcgi line |
| `.env.example` | Edit — add OCTANE_* vars |
| `.env` | Edit — add OCTANE_* vars |
| `config/octane.php` | Create — `php artisan octane:install` |
| `composer.json` | Edit — add laravel/octane |
| `tests/Feature/Octane/StateLeakTest.php` | Create — state isolation test |

## Risks

| Risk | Mitigation |
|------|------------|
| State leak between requests | Thin dispatcher pattern + queue everything + warm table only config/cache |
| MinIO upload compatibility | Test FrankenPHP worker with MinIO SDK via queue jobs |
| WebSocket (Reverb) conflict | Reverb runs separate process, no conflict |
| Queue throughput | RabbitMQ handles message routing; workers scale independently |
| FrankenPHP image size | Larger than FPM (~200MB vs ~100MB) but acceptable |

## Not Changed

- PostgreSQL, Redis, RabbitMQ, MinIO configs
- Queue worker setup (already PHP CLI)
- Reverb WebSocket server
- Vite dev server
- Application code (controllers, services, models)
- Feature flags, RBAC, translations
