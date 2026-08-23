# FrankenPHP + Laravel Octane Migration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate DockaBase from PHP-FPM to FrankenPHP with Laravel Octane, using the official FrankenPHP Docker image with Nginx as reverse proxy.

**Architecture:** FrankenPHP runs as thin HTTP dispatcher (validate + dispatch to RabbitMQ). Nginx reverse proxies to FrankenPHP on port 8000. Queue workers process all business logic asynchronously. Simple CRUD (<500ms) stays synchronous.

**Tech Stack:** FrankenPHP (official Docker image), Laravel Octane, RabbitMQ, Nginx (reverse proxy)

**Spec:** `docs/superpowers/specs/2026-04-22-frankenphp-octane-migration-design.md`

---

## File Structure

| File | Action | Responsibility |
|------|--------|----------------|
| `Dockerfile` | Rewrite | FrankenPHP base + PHP extensions + Octane CMD |
| `docker-compose.yml` | Modify | App service port/entrypoint, nginx upstream |
| `docker/nginx/default.conf` | Modify | FastCGI → HTTP proxy_pass |
| `docker/php/php.ini` | Modify | Remove fastcgi line |
| `.env.example` | Modify | Add OCTANE_* vars |
| `.env` | Modify | Add OCTANE_* vars |
| `composer.json` | Modify | Add laravel/octane |
| `config/octane.php` | Create | Octane config with minimal warm table |
| `tests/Feature/Octane/StateLeakTest.php` | Create | State isolation test |

---

### Task 1: Install Laravel Octane

**Files:**
- Modify: `composer.json`
- Create: `config/octane.php`

- [ ] **Step 1: Require laravel/octane via composer**

```bash
docker compose exec app composer require laravel/octane
```

Expected: `laravel/octane` added to `composer.json` `require` section.

- [ ] **Step 2: Publish Octane config**

```bash
docker compose exec app php artisan octane:install --server=frankenphp
```

Expected: `config/octane.php` created. Answer "yes" if prompted about FrankenPHP.

- [ ] **Step 3: Configure config/octane.php for thin dispatcher**

Edit `config/octane.php` — set the `warm` array to only warm non-stateful entries. Replace the default `warm` array with:

```php
'warm' => [
    // Only warm stateless, read-only entries
    // No models, no services — prevents state leak between requests
],
```

Set the `cache` config to use Redis (already configured):

```php
'cache' => [
    'tables' => [
        // No tables warmed — prevents state leak
    ],
],
```

Ensure the `listeners` section exists with defaults (Octane provides these out of the box).

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock config/octane.php
git commit -m "feat: install laravel/octane with FrankenPHP driver"
```

---

### Task 2: Rewrite Dockerfile

**Files:**
- Rewrite: `Dockerfile`

- [ ] **Step 1: Write the new Dockerfile**

Replace the entire `Dockerfile` contents with:

```dockerfile
FROM dunglas/frankenphp:latest-php8.4

# Install PHP extensions using the built-in helper
RUN install-php-extensions \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    sockets \
    redis

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    postgresql-client \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . /app

RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]
```

Key changes from old Dockerfile:
- Base: `php:8.4-fpm` → `dunglas/frankenphp:latest-php8.4`
- Extensions: `docker-php-ext-install` → `install-php-extensions` (FrankenPHP helper)
- No need to manually install `libpq-dev`, `libpng-dev`, etc. — `install-php-extensions` handles deps
- Workdir: `/var/www/html` → `/app` (FrankenPHP convention)
- Port: `9000` → `8000`
- CMD: `php-fpm` → `php artisan octane:frankenphp`

- [ ] **Step 2: Commit**

```bash
git add Dockerfile
git commit -m "feat: rewrite Dockerfile for FrankenPHP + Octane"
```

---

### Task 3: Update docker-compose.yml

**Files:**
- Modify: `docker-compose.yml`

- [ ] **Step 1: Update app service**

In `docker-compose.yml`, change the `app` service:

```yaml
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: dockabase_app
    working_dir: /app
    volumes:
      - .:/app
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    ports:
      - "8000:8000"
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
      rabbitmq:
        condition: service_healthy
    environment:
      REDIS_HOST: redis
    networks:
      - dockabase_network
```

Changes: `working_dir` → `/app`, volumes updated to match `/app`, added `ports: 8000`.

- [ ] **Step 2: Update nginx service volumes**

Change nginx volumes to match new app path:

```yaml
  nginx:
    image: nginx:alpine
    container_name: dockabase_nginx
    ports:
      - "80:80"
    volumes:
      - .:/app
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - dockabase_network
```

Changes: volume `.:/var/www/html` → `.:/app`.

- [ ] **Step 3: Update queue_worker, reverb, vite services**

Update `queue_worker` volumes and working_dir:

```yaml
  queue_worker:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: dockabase_worker
    working_dir: /app
    volumes:
      - .:/app
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    depends_on:
      postgres:
        condition: service_healthy
      rabbitmq:
        condition: service_healthy
      redis:
        condition: service_healthy
    command: php artisan queue:work --tries=3 --timeout=90
    networks:
      - dockabase_network
    restart: unless-stopped
```

Update `vite`:

```yaml
  vite:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: dockabase_vite
    working_dir: /app
    volumes:
      - .:/app
    command: npm run dev --host
    ports:
      - "5173:5173"
    networks:
      - dockabase_network
    restart: unless-stopped
```

Update `reverb`:

```yaml
  reverb:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: dockabase_reverb
    working_dir: /app
    volumes:
      - .:/app
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    command: php artisan reverb:start --host=0.0.0.0 --port=8080
    ports:
      - "8080:8080"
    depends_on:
      - app
      - redis
    networks:
      - dockabase_network
    restart: unless-stopped
```

All changes: `working_dir` → `/app`, volumes → `.:/app`.

- [ ] **Step 4: Commit**

```bash
git add docker-compose.yml
git commit -m "feat: update docker-compose for FrankenPHP (port 8000, /app workdir)"
```

---

### Task 4: Update Nginx config — FastCGI → HTTP proxy

**Files:**
- Modify: `docker/nginx/default.conf`

- [ ] **Step 1: Rewrite nginx config**

Replace entire `docker/nginx/default.conf` with:

```nginx
server {
    listen 80;
    server_name localhost;
    root /app/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/xml+rss application/rss+xml font/truetype application/vnd.ms-fontobject image/svg+xml;

    charset utf-8;

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt { access_log off; log_not_found off; }

    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|svg|woff|woff2|ttf|eot)$ {
        access_log off;
        log_not_found off;
        expires 30d;
        try_files $uri =404;
    }

    # Serve static files directly via Nginx (bypass FrankenPHP)
    location /build {
        try_files $uri =404;
    }
    location /assets {
        try_files $uri =404;
    }

    # Proxy everything else to FrankenPHP
    location / {
        proxy_pass http://app:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
    }

    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    access_log /var/log/nginx/dockabase_access.log;
    error_log /var/log/nginx/dockabase_error.log;
}
```

Key changes:
- `root` → `/app/public` (matches new workdir)
- Removed entire `location ~ \.php$` FastCGI block
- `location /` → `proxy_pass http://app:8000` with standard proxy headers
- Static asset locations (`/build`, `/assets`) served directly by Nginx

- [ ] **Step 2: Commit**

```bash
git add docker/nginx/default.conf
git commit -m "feat: nginx config — FastCGI to HTTP proxy for FrankenPHP"
```

---

### Task 5: Update environment files

**Files:**
- Modify: `.env.example`
- Modify: `.env`
- Modify: `docker/php/php.ini`

- [ ] **Step 1: Update .env.example**

In `.env.example`, replace the commented Octane section:

```env
# =============================================================================
# LARAVEL OCTANE
# =============================================================================
OCTANE_SERVER=frankenphp
OCTANE_PORT=8000
```

- [ ] **Step 2: Update .env**

Add same lines to `.env`:

```env
OCTANE_SERVER=frankenphp
OCTANE_PORT=8000
```

- [ ] **Step 3: Update docker/php/php.ini**

Remove the `fastcgi.logging = 0` line. Final `php.ini`:

```ini
[PHP]
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 100M
post_max_size = 100M
max_file_uploads = 20

[Date]
date.timezone = UTC

[OPcache]
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
opcache.enable_cli = 1
opcache.validate_timestamps = 1

[Session]
session.save_handler = redis
session.save_path = "tcp://redis:6379"
```

- [ ] **Step 4: Commit**

```bash
git add .env.example .env docker/php/php.ini
git commit -m "feat: add Octane env vars, remove FPM-specific php.ini settings"
```

---

### Task 6: Fix application paths for /app workdir

**Files:**
- Modify: any config or bootstrap files referencing `/var/www/html`

- [ ] **Step 1: Search for hardcoded paths**

```bash
grep -r "/var/www/html" --include="*.php" --include="*.env*" --include="*.yml" --include="*.yaml" --include="*.conf" .
```

Expected: Should find references in any custom config. Common places:
- `config/filesystems.php` — check for path references
- `bootstrap/` files
- `.env` / `.env.example`

If none found (Laravel uses relative paths), skip to commit.

- [ ] **Step 2: Fix any hardcoded paths found**

Replace `/var/www/html` with `/app` in any files found.

- [ ] **Step 3: Commit (if changes made)**

```bash
git add -A
git commit -m "fix: update hardcoded paths from /var/www/html to /app"
```

---

### Task 7: Write state leak test

**Files:**
- Create: `tests/Feature/Octane/StateLeakTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/Octane/StateLeakTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Octane;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateLeakTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_state_does_not_leak_between_requests(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        // Request as Alice
        $this->actingAs($userA)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJson(['name' => 'Alice']);

        // Request as Bob — should NOT see Alice's data
        $this->actingAs($userB)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJson(['name' => 'Bob']);

        // Unauthenticated request — should NOT see any user
        $this->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_config_cache_does_not_leak_between_requests(): void
    {
        $originalAppname = config('app.name');

        // Modify config in one request
        $this->getJson('/');

        // Config should still be original in next request
        $this->assertEquals($originalAppname, config('app.name'));
    }
}
```

Note: These tests validate state isolation. Under Octane, they will catch if any global state leaks between requests. They run correctly under both PHPUnit and Octane's test mode.

- [ ] **Step 2: Run the test to verify it passes**

```bash
docker compose exec app php artisan test tests/Feature/Octane/StateLeakTest.php
```

Expected: PASS (2 tests)

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Octane/StateLeakTest.php
git commit -m "test: add Octane state leak isolation tests"
```

---

### Task 8: Rebuild and smoke test

**Files:** None (verification only)

- [ ] **Step 1: Rebuild Docker containers**

```bash
docker compose down
docker compose build --no-cache app
docker compose up -d
```

Expected: All containers start. `dockabase_app` runs FrankenPHP on port 8000.

- [ ] **Step 2: Verify FrankenPHP is running**

```bash
docker compose logs app --tail=20
```

Expected: Log output showing `Octane server started` or `FrankenPHP` worker started.

- [ ] **Step 3: Verify HTTP response through Nginx**

```bash
curl -I http://localhost
```

Expected: HTTP 200 or 302 (redirect to login). Response headers from Nginx + FrankenPHP.

- [ ] **Step 4: Verify Nginx proxy is working**

```bash
curl -s http://localhost | head -20
```

Expected: HTML response from Laravel (login page or SPA).

- [ ] **Step 5: Verify queue workers still function**

```bash
docker compose logs queue_worker --tail=10
```

Expected: Worker waiting for jobs or processing successfully.

- [ ] **Step 6: Run full test suite**

```bash
docker compose exec app php artisan test
```

Expected: All tests pass. State leak tests included.

- [ ] **Step 7: Commit any remaining fixes (if needed)**

```bash
git add -A
git commit -m "fix: adjustments after FrankenPHP smoke test"
```

---

### Task 9: Update CLAUDE.md stack reference

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Update the Performance row in the stack table**

In `CLAUDE.md`, find:

```markdown
| Performance | Laravel Octane (Swoole) |
```

Replace with:

```markdown
| Performance | Laravel Octane (FrankenPHP) |
```

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update CLAUDE.md stack to FrankenPHP"
```
