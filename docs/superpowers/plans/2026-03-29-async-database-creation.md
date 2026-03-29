# Async Database Creation + Notifications Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement async database creation with real-time progress updates via WebSocket, toast notifications, and a notification center.

**Architecture:** Database creation dispatches a job that broadcasts progress events via Laravel Echo + Reverb. The Show page subscribes to WebSocket events and displays a horizontal timeline. On completion, users receive toast notifications and persistent notifications in a notification center.

**Tech Stack:** Laravel Reverb (WebSocket), Laravel Echo (frontend), Sonner (toast), Redis (queue/broadcast)

---

## File Structure

### Backend (New Files)
```
app/
├── Enums/
│   └── DatabaseCreationStepEnum.php       # 7 steps: validating, creating, etc.
├── Events/
│   ├── DatabaseStepUpdated.php            # Broadcast step progress
│   ├── DatabaseCreated.php                # Broadcast completion
│   └── DatabaseFailed.php                 # Broadcast failure
├── Jobs/
│   └── CreateDatabaseJob.php              # Main async job
├── Notifications/
│   └── DatabaseCreatedNotification.php    # Persistent notification
├── Services/
│   └── DatabaseProvisioningService.php    # Actual DB provisioning logic
├── Models/
│   ├── Notification.php                   # User notifications
│   └── DatabaseSchemaHistory.php          # Schema change history
└── Http/Controllers/
    └── Api/
        └── NotificationController.php     # Notification API
```

### Backend (Modified Files)
```
app/Models/Database.php                    # Add status, current_step, progress columns
app/Http/Controllers/App/DatabaseController.php  # Dispatch job instead of sync create
app/Services/DatabaseService.php           # Update for async flow
database/migrations/2026_03_28_100001_create_databases_table.php  # Already exists
routes/api.php                             # Notification endpoints
config/broadcasting.php                    # Reverb config
```

### Frontend (New Files)
```
resources/js/
├── components/
│   ├── CreationTimeline.vue               # Horizontal 7-step timeline
│   └── NotificationCenter.vue             # Bell icon + dropdown
├── composables/
│   └── useToast.ts                        # Sonner wrapper
├── lib/
│   └── echo.ts                            # Laravel Echo setup
└── types/
    └── notification.ts                    # TypeScript types
```

### Frontend (Modified Files)
```
resources/js/Pages/App/Databases/Show.vue  # Add timeline + WebSocket
resources/js/Layouts/AuthenticatedLayout.vue  # Add notification center
resources/js/bootstrap.js                  # Add Echo initialization
```

### Migrations (New)
```
database/migrations/
├── 2026_03_29_000001_add_status_to_databases_table.php
├── 2026_03_29_000002_create_notifications_table.php
└── 2026_03_29_000003_create_database_schema_histories_table.php
```

---

## Task 1: Install Dependencies

**Files:**
- Modify: `composer.json`
- Modify: `package.json`

- [ ] **Step 1: Install Laravel Reverb (WebSocket server)**

Run:
```bash
composer require laravel/reverb
```

Expected: Package installed successfully

- [ ] **Step 2: Install Pusher JS and Laravel Echo (WebSocket client)**

Run:
```bash
npm install laravel-echo pusher-js sonner
```

Expected: Packages added to package.json

- [ ] **Step 3: Publish Reverb configuration**

Run:
```bash
php artisan reverb:install
```

Expected: `config/reverb.php` created, `BROADCAST_CONNECTION=reverb` added to `.env.example`

- [ ] **Step 4: Commit dependencies**

```bash
git add composer.json composer.lock package.json package-lock.json config/reverb.php
git commit -m "feat: install reverb, echo, pusher-js, and sonner for realtime"
```

---

## Task 2: Create DatabaseCreationStepEnum

**Files:**
- Create: `app/Enums/DatabaseCreationStepEnum.php`
- Test: `tests/Unit/Enums/DatabaseCreationStepEnumTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Enums/DatabaseCreationStepEnumTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\DatabaseCreationStepEnum;
use Tests\TestCase;

class DatabaseCreationStepEnumTest extends TestCase
{
    public function test_has_seven_steps(): void
    {
        $this->assertCount(7, DatabaseCreationStepEnum::cases());
    }

    public function test_steps_are_in_correct_order(): void
    {
        $steps = DatabaseCreationStepEnum::cases();

        $this->assertEquals('validating', $steps[0]->value);
        $this->assertEquals('creating', $steps[1]->value);
        $this->assertEquals('configuring', $steps[2]->value);
        $this->assertEquals('migrating', $steps[3]->value);
        $this->assertEquals('permissions', $steps[4]->value);
        $this->assertEquals('testing', $steps[5]->value);
        $this->assertEquals('ready', $steps[6]->value);
    }

    public function test_label_returns_human_readable_string(): void
    {
        $this->assertEquals('Validando', DatabaseCreationStepEnum::VALIDATING->label());
        $this->assertEquals('Criando', DatabaseCreationStepEnum::CREATING->label());
        $this->assertEquals('Configurando', DatabaseCreationStepEnum::CONFIGURING->label());
        $this->assertEquals('Migrações', DatabaseCreationStepEnum::MIGRATING->label());
        $this->assertEquals('Permissões', DatabaseCreationStepEnum::PERMISSIONS->label());
        $this->assertEquals('Testando', DatabaseCreationStepEnum::TESTING->label());
        $this->assertEquals('Pronto', DatabaseCreationStepEnum::READY->label());
    }

    public function test_progress_percentage(): void
    {
        $this->assertEquals(14, DatabaseCreationStepEnum::VALIDATING->progress());
        $this->assertEquals(28, DatabaseCreationStepEnum::CREATING->progress());
        $this->assertEquals(42, DatabaseCreationStepEnum::CONFIGURING->progress());
        $this->assertEquals(56, DatabaseCreationStepEnum::MIGRATING->progress());
        $this->assertEquals(71, DatabaseCreationStepEnum::PERMISSIONS->progress());
        $this->assertEquals(85, DatabaseCreationStepEnum::TESTING->progress());
        $this->assertEquals(100, DatabaseCreationStepEnum::READY->progress());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Unit/Enums/DatabaseCreationStepEnumTest.php
```

Expected: FAIL - Class `App\Enums\DatabaseCreationStepEnum` not found

- [ ] **Step 3: Write the enum implementation**

Create `app/Enums/DatabaseCreationStepEnum.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum DatabaseCreationStepEnum: string
{
    case VALIDATING = 'validating';
    case CREATING = 'creating';
    case CONFIGURING = 'configuring';
    case MIGRATING = 'migrating';
    case PERMISSIONS = 'permissions';
    case TESTING = 'testing';
    case READY = 'ready';

    public function label(): string
    {
        return match ($this) {
            self::VALIDATING => 'Validando',
            self::CREATING => 'Criando',
            self::CONFIGURING => 'Configurando',
            self::MIGRATING => 'Migrações',
            self::PERMISSIONS => 'Permissões',
            self::TESTING => 'Testando',
            self::READY => 'Pronto',
        };
    }

    public function progress(): int
    {
        return match ($this) {
            self::VALIDATING => 14,
            self::CREATING => 28,
            self::CONFIGURING => 42,
            self::MIGRATING => 56,
            self::PERMISSIONS => 71,
            self::TESTING => 85,
            self::READY => 100,
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run:
```bash
php artisan test tests/Unit/Enums/DatabaseCreationStepEnumTest.php
```

Expected: PASS - 3 tests

- [ ] **Step 5: Commit**

```bash
git add app/Enums/DatabaseCreationStepEnum.php tests/Unit/Enums/DatabaseCreationStepEnumTest.php
git commit -m "feat: add DatabaseCreationStepEnum with 7 provisioning steps"
```

---

## Task 3: Add Status Columns to Databases Table

**Files:**
- Create: `database/migrations/2026_03_29_000001_add_status_to_databases_table.php`
- Modify: `app/Models/Database.php`
- Test: `tests/Unit/Models/DatabaseTest.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Unit/Models/DatabaseTest.php`:

```php
    public function test_status_defaults_to_pending(): void
    {
        $database = Database::factory()->create();

        $this->assertEquals('pending', $database->status);
    }

    public function test_current_step_is_nullable(): void
    {
        $database = Database::factory()->create();

        $this->assertNull($database->current_step);
    }

    public function test_progress_defaults_to_zero(): void
    {
        $database = Database::factory()->create();

        $this->assertEquals(0, $database->progress);
    }

    public function test_error_message_is_nullable(): void
    {
        $database = Database::factory()->create();

        $this->assertNull($database->error_message);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Unit/Models/DatabaseTest.php
```

Expected: FAIL - Unknown column "status"

- [ ] **Step 3: Create migration**

Create `database/migrations/2026_03_29_000001_add_status_to_databases_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('is_active');
            $table->string('current_step', 30)->nullable()->after('status');
            $table->unsignedTinyInteger('progress')->default(0)->after('current_step');
            $table->text('error_message')->nullable()->after('progress');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropColumn(['status', 'current_step', 'progress', 'error_message']);
        });
    }
};
```

- [ ] **Step 4: Run migration**

Run:
```bash
php artisan migrate
```

Expected: Migration ran successfully

- [ ] **Step 5: Update Database model**

Update `app/Models/Database.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Database extends Model
{
    use HasFactory, HasKsuid;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'host',
        'port',
        'database_name',
        'is_active',
        'settings',
        'status',
        'current_step',
        'progress',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
            'progress' => 'integer',
        ];
    }

    public function credentials(): BelongsToMany
    {
        return $this->belongsToMany(Credential::class, 'credential_database', 'database_id', 'credential_id')
            ->withTimestamps();
    }

    public function schemaHistories(): HasMany
    {
        return $this->hasMany(DatabaseSchemaHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfName($query, string $name)
    {
        return $query->where('name', $name);
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run:
```bash
php artisan test tests/Unit/Models/DatabaseTest.php
```

Expected: PASS - All tests

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_03_29_000001_add_status_to_databases_table.php app/Models/Database.php tests/Unit/Models/DatabaseTest.php
git commit -m "feat: add status, current_step, progress columns to databases table"
```

---

## Task 4: Create Notifications Table and Model

**Files:**
- Create: `database/migrations/2026_03_29_000002_create_notifications_table.php`
- Create: `app/Models/Notification.php`
- Test: `tests/Unit/Models/NotificationTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Models/NotificationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'database_created',
            'title' => 'Database criado',
            'message' => 'O database dev foi criado com sucesso',
            'data' => ['database_id' => '123'],
        ]);

        $this->assertEquals('database_created', $notification->type);
        $this->assertFalse($notification->read);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($notification->user->is($user));
    }

    public function test_scope_unread_filters_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->create(['user_id' => $user->id, 'read' => false]);
        Notification::factory()->create(['user_id' => $user->id, 'read' => true]);

        $unread = Notification::unread()->get();

        $this->assertCount(1, $unread);
    }

    public function test_mark_as_read(): void
    {
        $notification = Notification::factory()->create(['read' => false]);

        $notification->markAsRead();

        $this->assertTrue($notification->fresh()->read);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Unit/Models/NotificationTest.php
```

Expected: FAIL - Class `App\Models\Notification` not found

- [ ] **Step 3: Create migration**

Create `database/migrations/2026_03_29_000002_create_notifications_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title');
            $table->text('message');
            $table->jsonb('data')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

- [ ] **Step 4: Create Notification model**

Create `app/Models/Notification.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    public function scopeOfUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function markAsRead(): void
    {
        $this->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    public function isRead(): bool
    {
        return $this->read;
    }
}
```

- [ ] **Step 5: Create Notification factory**

Add to `database/factories/NotificationFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'database_created',
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->sentence(),
            'data' => null,
            'read' => false,
            'read_at' => null,
        ];
    }
}
```

- [ ] **Step 6: Run migration**

Run:
```bash
php artisan migrate
```

Expected: Migration ran successfully

- [ ] **Step 7: Run test to verify it passes**

Run:
```bash
php artisan test tests/Unit/Models/NotificationTest.php
```

Expected: PASS - 4 tests

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_03_29_000002_create_notifications_table.php app/Models/Notification.php database/factories/NotificationFactory.php tests/Unit/Models/NotificationTest.php
git commit -m "feat: add Notification model with read/unread scope"
```

---

## Task 5: Create DatabaseSchemaHistory Model

**Files:**
- Create: `database/migrations/2026_03_29_000003_create_database_schema_histories_table.php`
- Create: `app/Models/DatabaseSchemaHistory.php`
- Test: `tests/Unit/Models/DatabaseSchemaHistoryTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Models/DatabaseSchemaHistoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Database;
use App\Models\DatabaseSchemaHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_schema_history(): void
    {
        $database = Database::factory()->create();
        $history = DatabaseSchemaHistory::create([
            'database_id' => $database->id,
            'action' => 'table_created',
            'table_name' => 'users',
            'new_value' => ['columns' => ['id', 'name']],
        ]);

        $this->assertEquals('table_created', $history->action);
        $this->assertEquals('users', $history->table_name);
    }

    public function test_belongs_to_database(): void
    {
        $database = Database::factory()->create();
        $history = DatabaseSchemaHistory::factory()->create(['database_id' => $database->id]);

        $this->assertTrue($history->database->is($database));
    }

    public function test_scope_by_action(): void
    {
        $database = Database::factory()->create();
        DatabaseSchemaHistory::factory()->create(['database_id' => $database->id, 'action' => 'table_created']);
        DatabaseSchemaHistory::factory()->create(['database_id' => $database->id, 'action' => 'column_added']);

        $results = DatabaseSchemaHistory::ofAction('table_created')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('table_created', $results->first()->action);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Unit/Models/DatabaseSchemaHistoryTest.php
```

Expected: FAIL - Class not found

- [ ] **Step 3: Create migration**

Create `database/migrations/2026_03_29_000003_create_database_schema_histories_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_schema_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_id')->constrained()->cascadeOnDelete();
            $table->string('action', 50);
            $table->string('table_name', 255)->nullable();
            $table->string('column_name', 255)->nullable();
            $table->jsonb('old_value')->nullable();
            $table->jsonb('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['database_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_schema_histories');
    }
};
```

- [ ] **Step 4: Create model**

Create `app/Models/DatabaseSchemaHistory.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseSchemaHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_id',
        'action',
        'table_name',
        'column_name',
        'old_value',
        'new_value',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
        ];
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeOfTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeOfDatabase($query, string $databaseId)
    {
        return $query->where('database_id', $databaseId);
    }
}
```

- [ ] **Step 5: Create factory**

Create `database/factories/DatabaseSchemaHistoryFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Database;
use App\Models\DatabaseSchemaHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseSchemaHistoryFactory extends Factory
{
    protected $model = DatabaseSchemaHistory::class;

    public function definition(): array
    {
        return [
            'database_id' => Database::factory(),
            'action' => 'table_created',
            'table_name' => $this->faker->word(),
            'column_name' => null,
            'old_value' => null,
            'new_value' => null,
            'user_id' => null,
        ];
    }
}
```

- [ ] **Step 6: Run migration**

Run:
```bash
php artisan migrate
```

Expected: Migration ran successfully

- [ ] **Step 7: Run test to verify it passes**

Run:
```bash
php artisan test tests/Unit/Models/DatabaseSchemaHistoryTest.php
```

Expected: PASS - 3 tests

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_03_29_000003_create_database_schema_histories_table.php app/Models/DatabaseSchemaHistory.php database/factories/DatabaseSchemaHistoryFactory.php tests/Unit/Models/DatabaseSchemaHistoryTest.php
git commit -m "feat: add DatabaseSchemaHistory model for tracking schema changes"
```

---

## Task 6: Create Broadcast Events

**Files:**
- Create: `app/Events/DatabaseStepUpdated.php`
- Create: `app/Events/DatabaseCreated.php`
- Create: `app/Events/DatabaseFailed.php`
- Test: `tests/Unit/Events/DatabaseEventsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Events/DatabaseEventsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\DatabaseCreated;
use App\Events\DatabaseFailed;
use App\Events\DatabaseStepUpdated;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class DatabaseEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_step_updated_broadcasts_on_database_channel(): void
    {
        $database = Database::factory()->create();
        $event = new DatabaseStepUpdated($database, 'validating', 14);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('database.' . $database->id, $channels[0]->name);
    }

    public function test_database_step_updated_broadcasts_with_step_data(): void
    {
        $database = Database::factory()->create();
        $event = new DatabaseStepUpdated($database, 'creating', 28);

        $broadcastData = $event->broadcastWith();

        $this->assertEquals('creating', $broadcastData['step']);
        $this->assertEquals(28, $broadcastData['progress']);
        $this->assertArrayHasKey('database', $broadcastData);
    }

    public function test_database_created_broadcasts_on_database_channel(): void
    {
        $database = Database::factory()->create();
        $event = new DatabaseCreated($database);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('database.' . $database->id, $channels[0]->name);
    }

    public function test_database_failed_broadcasts_with_error(): void
    {
        $database = Database::factory()->create();
        $event = new DatabaseFailed($database, 'Connection refused');

        $broadcastData = $event->broadcastWith();

        $this->assertEquals('failed', $broadcastData['status']);
        $this->assertEquals('Connection refused', $broadcastData['error']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Unit/Events/DatabaseEventsTest.php
```

Expected: FAIL - Class not found

- [ ] **Step 3: Create DatabaseStepUpdated event**

Create `app/Events/DatabaseStepUpdated.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Database;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DatabaseStepUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Database $database,
        public string $step,
        public int $progress,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('database.' . $this->database->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'step' => $this->step,
            'progress' => $this->progress,
            'database' => [
                'id' => $this->database->id,
                'name' => $this->database->name,
                'status' => $this->database->status,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'step.updated';
    }
}
```

- [ ] **Step 4: Create DatabaseCreated event**

Create `app/Events/DatabaseCreated.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Database;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DatabaseCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Database $database,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('database.' . $this->database->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'database' => [
                'id' => $this->database->id,
                'name' => $this->database->name,
                'status' => $this->database->status,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'database.created';
    }
}
```

- [ ] **Step 5: Create DatabaseFailed event**

Create `app/Events/DatabaseFailed.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Database;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DatabaseFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Database $database,
        public string $error,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('database.' . $this->database->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'status' => 'failed',
            'error' => $this->error,
            'database' => [
                'id' => $this->database->id,
                'name' => $this->database->name,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'database.failed';
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run:
```bash
php artisan test tests/Unit/Events/DatabaseEventsTest.php
```

Expected: PASS - 4 tests

- [ ] **Step 7: Commit**

```bash
git add app/Events tests/Unit/Events/DatabaseEventsTest.php
git commit -m "feat: add broadcast events for database creation progress"
```

---

## Task 7: Create DatabaseProvisioningService

**Files:**
- Create: `app/Services/DatabaseProvisioningService.php`
- Test: `tests/Unit/Services/DatabaseProvisioningServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/DatabaseProvisioningServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\DatabaseCreationStepEnum;
use App\Models\Database;
use App\Services\DatabaseProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DatabaseProvisioningService::class);
    }

    public function test_validate_step_validates_schema(): void
    {
        $database = Database::factory()->create([
            'database_name' => 'valid_name',
        ]);

        $result = $this->service->validateStep($database);

        $this->assertTrue($result);
    }

    public function test_validate_step_rejects_invalid_name(): void
    {
        $database = Database::factory()->create([
            'database_name' => 'invalid-name-with-dash',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->validateStep($database);
    }

    public function test_get_steps_returns_ordered_steps(): void
    {
        $steps = $this->service->getSteps();

        $this->assertCount(7, $steps);
        $this->assertEquals(DatabaseCreationStepEnum::VALIDATING, $steps[0]);
        $this->assertEquals(DatabaseCreationStepEnum::READY, $steps[6]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Unit/Services/DatabaseProvisioningServiceTest.php
```

Expected: FAIL - Class not found

- [ ] **Step 3: Create service**

Create `app/Services/DatabaseProvisioningService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DatabaseCreationStepEnum;
use App\Models\Database;
use Illuminate\Support\Facades\DB;

class DatabaseProvisioningService
{
    /**
     * @return DatabaseCreationStepEnum[]
     */
    public function getSteps(): array
    {
        return DatabaseCreationStepEnum::cases();
    }

    public function validateStep(Database $database): bool
    {
        $name = $database->database_name;

        // Database name must be valid PostgreSQL identifier
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Invalid database name: {$name}");
        }

        if (strlen($name) > 63) {
            throw new \InvalidArgumentException("Database name too long: {$name}");
        }

        return true;
    }

    public function createDatabase(Database $database): bool
    {
        $name = $database->database_name;

        try {
            // Create the PostgreSQL database
            DB::connection('pgsql')->statement("CREATE DATABASE \"{$name}\"");

            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to create database: {$e->getMessage()}");
        }
    }

    public function configureExtensions(Database $database): bool
    {
        // Configure extensions on the new database
        // This would connect to the new database and enable extensions
        // For now, we'll just return true as a placeholder
        return true;
    }

    public function runMigrations(Database $database): bool
    {
        // Run base migrations on the new database
        return true;
    }

    public function configurePermissions(Database $database): bool
    {
        // Configure database permissions
        return true;
    }

    public function testConnection(Database $database): bool
    {
        // Test the connection to the new database
        return true;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run:
```bash
php artisan test tests/Unit/Services/DatabaseProvisioningServiceTest.php
```

Expected: PASS - 3 tests

- [ ] **Step 5: Commit**

```bash
git add app/Services/DatabaseProvisioningService.php tests/Unit/Services/DatabaseProvisioningServiceTest.php
git commit -m "feat: add DatabaseProvisioningService with step validation"
```

---

## Task 8: Create CreateDatabaseJob

**Files:**
- Create: `app/Jobs/CreateDatabaseJob.php`
- Test: `tests/Feature/Jobs/CreateDatabaseJobTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Jobs/CreateDatabaseJobTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\DatabaseCreationStepEnum;
use App\Events\DatabaseCreated;
use App\Events\DatabaseStepUpdated;
use App\Jobs\CreateDatabaseJob;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateDatabaseJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_updates_database_status_to_processing(): void
    {
        $database = Database::factory()->create(['status' => 'pending']);

        $job = new CreateDatabaseJob($database);
        $job->handle(app(\App\Services\DatabaseProvisioningService::class));

        $database->refresh();

        $this->assertEquals('ready', $database->status);
    }

    public function test_job_broadcasts_step_updates(): void
    {
        Event::fake([DatabaseStepUpdated::class, DatabaseCreated::class]);

        $database = Database::factory()->create(['status' => 'pending']);

        $job = new CreateDatabaseJob($database);
        $job->handle(app(\App\Services\DatabaseProvisioningService::class));

        Event::assertDispatchedTimes(DatabaseStepUpdated::class, 7);
        Event::assertDispatched(DatabaseCreated::class);
    }

    public function test_job_handles_failure_gracefully(): void
    {
        $database = Database::factory()->create([
            'status' => 'pending',
            'database_name' => 'invalid-name!',
        ]);

        $job = new CreateDatabaseJob($database);

        try {
            $job->handle(app(\App\Services\DatabaseProvisioningService::class));
        } catch (\Exception $e) {
            // Expected
        }

        $database->refresh();

        $this->assertEquals('failed', $database->status);
        $this->assertNotNull($database->error_message);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Feature/Jobs/CreateDatabaseJobTest.php
```

Expected: FAIL - Class not found

- [ ] **Step 3: Create job**

Create `app/Jobs/CreateDatabaseJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DatabaseCreationStepEnum;
use App\Events\DatabaseCreated;
use App\Events\DatabaseFailed;
use App\Events\DatabaseStepUpdated;
use App\Models\Database;
use App\Services\DatabaseProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Database $database,
    ) {}

    public function handle(DatabaseProvisioningService $service): void
    {
        $this->database->update(['status' => 'processing']);

        try {
            $steps = $service->getSteps();

            foreach ($steps as $step) {
                $this->executeStep($service, $step);

                $this->database->update([
                    'current_step' => $step->value,
                    'progress' => $step->progress(),
                ]);

                DatabaseStepUpdated::dispatch(
                    $this->database,
                    $step->value,
                    $step->progress()
                );
            }

            $this->database->update([
                'status' => 'ready',
                'current_step' => DatabaseCreationStepEnum::READY->value,
                'progress' => 100,
            ]);

            DatabaseCreated::dispatch($this->database);

        } catch (Throwable $e) {
            $this->database->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            DatabaseFailed::dispatch($this->database, $e->getMessage());

            throw $e;
        }
    }

    private function executeStep(DatabaseProvisioningService $service, DatabaseCreationStepEnum $step): void
    {
        match ($step) {
            DatabaseCreationStepEnum::VALIDATING => $service->validateStep($this->database),
            DatabaseCreationStepEnum::CREATING => $service->createDatabase($this->database),
            DatabaseCreationStepEnum::CONFIGURING => $service->configureExtensions($this->database),
            DatabaseCreationStepEnum::MIGRATING => $service->runMigrations($this->database),
            DatabaseCreationStepEnum::PERMISSIONS => $service->configurePermissions($this->database),
            DatabaseCreationStepEnum::TESTING => $service->testConnection($this->database),
            DatabaseCreationStepEnum::READY => null, // Final step, no action needed
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run:
```bash
php artisan test tests/Feature/Jobs/CreateDatabaseJobTest.php
```

Expected: PASS - 3 tests

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/CreateDatabaseJob.php tests/Feature/Jobs/CreateDatabaseJobTest.php
git commit -m "feat: add CreateDatabaseJob with step broadcasting"
```

---

## Task 9: Create DatabaseCreatedNotification

**Files:**
- Create: `app/Notifications/DatabaseCreatedNotification.php`
- Test: `tests/Unit/Notifications/DatabaseCreatedNotificationTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Notifications/DatabaseCreatedNotificationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Database;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\DatabaseCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_notification_in_database(): void
    {
        $user = User::factory()->create();
        $database = Database::factory()->create(['name' => 'dev']);

        $notification = new DatabaseCreatedNotification($database);
        $notification->toDatabase($user);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'database_created',
            'title' => 'Database criado com sucesso',
        ]);
    }

    public function test_notification_contains_database_data(): void
    {
        $user = User::factory()->create();
        $database = Database::factory()->create(['name' => 'staging']);

        $notification = new DatabaseCreatedNotification($database);
        $notification->toDatabase($user);

        $dbNotification = Notification::where('user_id', $user->id)->first();

        $this->assertEquals($database->id, $dbNotification->data['database_id']);
        $this->assertEquals('staging', $dbNotification->data['database_name']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Unit/Notifications/DatabaseCreatedNotificationTest.php
```

Expected: FAIL - Class not found

- [ ] **Step 3: Create notification**

Create `app/Notifications/DatabaseCreatedNotification.php`:

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Database;
use App\Models\Notification;
use App\Models\User;

class DatabaseCreatedNotification
{
    public function __construct(
        public Database $database,
    ) {}

    public function toDatabase(User $user): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'database_created',
            'title' => 'Database criado com sucesso',
            'message' => "O database {$this->database->name} foi criado e está pronto para uso.",
            'data' => [
                'database_id' => $this->database->id,
                'database_name' => $this->database->name,
            ],
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run:
```bash
php artisan test tests/Unit/Notifications/DatabaseCreatedNotificationTest.php
```

Expected: PASS - 2 tests

- [ ] **Step 5: Commit**

```bash
git add app/Notifications/DatabaseCreatedNotification.php tests/Unit/Notifications/DatabaseCreatedNotificationTest.php
git commit -m "feat: add DatabaseCreatedNotification for persistent notifications"
```

---

## Task 10: Create Notification API Controller

**Files:**
- Create: `app/Http/Controllers/Api/NotificationController.php`
- Create: `app/Http/Resources/NotificationResource.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/NotificationControllerTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Api/NotificationControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_returns_user_notifications(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_mark_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk();

        $this->assertTrue($notification->fresh()->read);
    }

    public function test_mark_all_as_read(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/read-all');

        $response->assertOk();

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->where('read', false)->count());
    }

    public function test_unread_count(): void
    {
        Notification::factory()->count(2)->create(['user_id' => $this->user->id, 'read' => false]);
        Notification::factory()->count(3)->create(['user_id' => $this->user->id, 'read' => true]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['count' => 2]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Feature/Api/NotificationControllerTest.php
```

Expected: FAIL - 404

- [ ] **Step 3: Create NotificationResource**

Create `app/Http/Resources/NotificationResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'read' => $this->read,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

- [ ] **Step 4: Create controller**

Create `app/Http/Controllers/Api/NotificationController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::ofUser($request->user()->id)
            ->recent(7)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => NotificationResource::collection($notifications),
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::ofUser($request->user()->id)
            ->unread()
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::ofUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'count' => $count,
        ]);
    }
}
```

- [ ] **Step 5: Add routes**

Create or update `routes/api.php`:

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.read-all');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.unread-count');
});
```

- [ ] **Step 6: Run test to verify it passes**

Run:
```bash
php artisan test tests/Feature/Api/NotificationControllerTest.php
```

Expected: PASS - 4 tests

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/NotificationController.php app/Http/Resources/NotificationResource.php routes/api.php tests/Feature/Api/NotificationControllerTest.php
git commit -m "feat: add Notification API endpoints"
```

---

## Task 11: Update DatabaseController for Async Creation

**Files:**
- Modify: `app/Http/Controllers/App/DatabaseController.php`
- Modify: `tests/Feature/System/DatabaseControllerTest.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/System/DatabaseControllerTest.php`:

```php
    public function test_store_dispatches_create_database_job(): void
    {
        Queue::fake();

        $user = User::factory()->admin()->create();
        $credential = Credential::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('app.databases.store'), [
                'name' => 'testdb',
                'database_name' => 'testdb',
                'credential_ids' => [$credential->id],
            ]);

        $response->assertRedirect();

        Queue::assertPushed(CreateDatabaseJob::class);
    }

    public function test_store_creates_database_with_pending_status(): void
    {
        Queue::fake();

        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)
            ->postJson(route('app.databases.store'), [
                'name' => 'pending_db',
                'database_name' => 'pending_db',
            ]);

        $this->assertDatabaseHas('databases', [
            'name' => 'pending_db',
            'status' => 'pending',
        ]);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
php artisan test tests/Feature/System/DatabaseControllerTest.php --filter=test_store
```

Expected: FAIL - Job not dispatched

- [ ] **Step 3: Update controller**

Update `app/Http/Controllers/App/DatabaseController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\CreateDatabaseRequest;
use App\Http\Requests\System\UpdateDatabaseRequest;
use App\Http\Resources\DatabaseCollection;
use App\Http\Resources\DatabaseResource;
use App\Jobs\CreateDatabaseJob;
use App\Models\Credential;
use App\Models\Database;
use App\Services\DatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseController extends Controller
{
    public function __construct(
        private DatabaseService $databaseService
    ) {}

    public function index(Request $request): DatabaseCollection|Response
    {
        $this->authorize('viewAny', Database::class);

        $databases = Database::withCount('credentials')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return new DatabaseCollection($databases);
        }

        return Inertia::render('App/Databases/Index', [
            'databases' => (new DatabaseCollection($databases))->toArray($request),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Database::class);

        return Inertia::render('App/Databases/Create');
    }

    public function store(CreateDatabaseRequest $request): JsonResponse
    {
        $this->authorize('create', Database::class);

        $database = $this->databaseService->create(array_merge(
            $request->validated(),
            ['status' => 'pending']
        ));

        if ($request->has('credential_ids')) {
            foreach ($request->validated('credential_ids') as $credentialId) {
                $credential = Credential::find($credentialId);
                if ($credential) {
                    $this->databaseService->attachCredential($database, $credential);
                }
            }
        }

        // Dispatch async job
        CreateDatabaseJob::dispatch($database);

        return response()->json([
            'data' => (new DatabaseResource($database->fresh()))->toArray($request),
        ], 201);
    }

    public function show(Request $request, Database $database): DatabaseResource|Response
    {
        $this->authorize('view', $database);

        $database->load(['credentials.users']);

        if ($request->wantsJson()) {
            return new DatabaseResource($database);
        }

        return Inertia::render('App/Databases/Show', [
            'database' => (new DatabaseResource($database))->toArray($request),
        ]);
    }

    public function update(UpdateDatabaseRequest $request, Database $database): DatabaseResource
    {
        $this->authorize('update', $database);

        $database = $this->databaseService->update($database, $request->validated());

        return new DatabaseResource($database);
    }

    public function destroy(Database $database): JsonResponse
    {
        $this->authorize('delete', $database);

        $this->databaseService->delete($database->id);

        return response()->json(null, 204);
    }

    public function attachCredential(Request $request, Database $database): DatabaseResource
    {
        $this->authorize('update', $database);

        $request->validate([
            'credential_id' => ['required', 'string', 'size:27', 'exists:credentials,id'],
        ]);

        $credential = Credential::findOrFail($request->input('credential_id'));
        $this->databaseService->attachCredential($database, $credential);

        return new DatabaseResource($database->fresh());
    }

    public function detachCredential(Database $database, Credential $credential): JsonResponse
    {
        $this->authorize('update', $database);

        $this->databaseService->detachCredential($database, $credential);

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 4: Update DatabaseResource to include new fields**

Update `app/Http/Resources/DatabaseResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DatabaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'host' => $this->host,
            'port' => $this->port,
            'database_name' => $this->database_name,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'current_step' => $this->current_step,
            'progress' => $this->progress,
            'error_message' => $this->error_message,
            'settings' => $this->settings,
            'credentials_count' => $this->whenCounted('credentials'),
            'credentials' => CredentialResource::collection($this->whenLoaded('credentials')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run:
```bash
php artisan test tests/Feature/System/DatabaseControllerTest.php --filter=test_store
```

Expected: PASS - 2 tests

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/App/DatabaseController.php app/Http/Resources/DatabaseResource.php tests/Feature/System/DatabaseControllerTest.php
git commit -m "feat: dispatch CreateDatabaseJob async from controller"
```

---

## Task 12: Configure Laravel Echo on Frontend

**Files:**
- Create: `resources/js/lib/echo.ts`
- Modify: `resources/js/bootstrap.js`
- Modify: `resources/js/types/database.ts`

- [ ] **Step 1: Update TypeScript types**

Update `resources/js/types/database.ts`:

```typescript
export type DatabaseStatus = 'pending' | 'processing' | 'ready' | 'failed';

export type CreationStep = 'validating' | 'creating' | 'configuring' | 'migrating' | 'permissions' | 'testing' | 'ready';

export interface Database {
  id: string;
  name: string;
  display_name: string | null;
  description: string | null;
  host: string;
  port: number;
  database_name: string;
  is_active: boolean;
  status: DatabaseStatus;
  current_step: CreationStep | null;
  progress: number;
  error_message: string | null;
  settings: Record<string, unknown> | null;
  credentials_count?: number;
  created_at: string;
  updated_at: string;
}

export interface DatabaseCollection {
  data: Database[];
}

export interface StepUpdatePayload {
  step: CreationStep;
  progress: number;
  database: {
    id: string;
    name: string;
    status: DatabaseStatus;
  };
}

export interface DatabaseCreatedPayload {
  database: {
    id: string;
    name: string;
    status: DatabaseStatus;
  };
}

export interface DatabaseFailedPayload {
  status: 'failed';
  error: string;
  database: {
    id: string;
    name: string;
  };
}
```

- [ ] **Step 2: Create notification types**

Create `resources/js/types/notification.ts`:

```typescript
export type NotificationType = 'database_created' | 'database_failed' | 'schema_changed' | 'backup_completed';

export interface Notification {
  id: number;
  type: NotificationType;
  title: string;
  message: string;
  data: Record<string, unknown> | null;
  read: boolean;
  read_at: string | null;
  created_at: string;
}

export interface NotificationCollection {
  data: Notification[];
}
```

- [ ] **Step 3: Create Echo setup**

Create `resources/js/lib/echo.ts`:

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Echo: Echo;
    Pusher: typeof Pusher;
  }
}

window.Pusher = Pusher;

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY || 'app-key',
  wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
  wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
  wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
});

export default window.Echo;
```

- [ ] **Step 4: Update bootstrap.js**

Update `resources/js/bootstrap.js`:

```javascript
import axios from 'axios';
import './lib/echo';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
```

- [ ] **Step 5: Add Vite environment variables**

Add to `.env.example`:

```
VITE_REVERB_APP_KEY=app-key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/lib/echo.ts resources/js/bootstrap.js resources/js/types/database.ts resources/js/types/notification.ts .env.example
git commit -m "feat: configure Laravel Echo for WebSocket connections"
```

---

## Task 13: Create CreationTimeline Component

**Files:**
- Create: `resources/js/components/CreationTimeline.vue`

- [ ] **Step 1: Create timeline component**

Create `resources/js/components/CreationTimeline.vue`:

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { Check, Loader2, Circle } from 'lucide-vue-next';
import type { CreationStep } from '@/types/database';

interface Step {
  key: CreationStep;
  label: string;
}

const props = defineProps<{
  currentStep: CreationStep | null;
  progress: number;
  status: string;
}>();

const steps: Step[] = [
  { key: 'validating', label: 'Validando' },
  { key: 'creating', label: 'Criando' },
  { key: 'configuring', label: 'Config' },
  { key: 'migrating', label: 'Migra' },
  { key: 'permissions', label: 'Perms' },
  { key: 'testing', label: 'Teste' },
  { key: 'ready', label: 'Pronto' },
];

const stepOrder = steps.map(s => s.key);

const getStepStatus = (step: CreationStep): 'completed' | 'running' | 'pending' => {
  if (props.status === 'failed') {
    const currentIndex = stepOrder.indexOf(props.currentStep || 'validating');
    const stepIndex = stepOrder.indexOf(step);
    return stepIndex < currentIndex ? 'completed' : 'pending';
  }

  if (props.status === 'ready') {
    return 'completed';
  }

  const currentIndex = stepOrder.indexOf(props.currentStep || 'validating');
  const stepIndex = stepOrder.indexOf(step);

  if (stepIndex < currentIndex) {
    return 'completed';
  }
  if (stepIndex === currentIndex) {
    return 'running';
  }
  return 'pending';
};

const getStepColor = (status: 'completed' | 'running' | 'pending'): string => {
  switch (status) {
    case 'completed':
      return 'bg-green-500 text-white';
    case 'running':
      return 'bg-blue-500 text-white animate-pulse';
    default:
      return 'bg-muted text-muted-foreground';
  }
};

const getLineColor = (index: number): string => {
  const nextStep = steps[index + 1];
  if (!nextStep) return 'bg-muted';

  const nextStatus = getStepStatus(nextStep.key);
  return nextStatus === 'completed' ? 'bg-green-500' : 'bg-muted';
};
</script>

<template>
  <div class="w-full py-6">
    <!-- Progress bar -->
    <div class="mb-4">
      <div class="flex justify-between text-sm text-muted-foreground mb-1">
        <span>Progresso</span>
        <span>{{ progress }}%</span>
      </div>
      <div class="h-2 bg-muted rounded-full overflow-hidden">
        <div
          class="h-full bg-primary transition-all duration-500 ease-out"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>

    <!-- Steps -->
    <div class="flex items-center justify-between">
      <template v-for="(step, index) in steps" :key="step.key">
        <!-- Step -->
        <div class="flex flex-col items-center">
          <div
            :class="[
              'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300',
              getStepColor(getStepStatus(step.key))
            ]"
          >
            <Check
              v-if="getStepStatus(step.key) === 'completed'"
              class="h-5 w-5"
            />
            <Loader2
              v-else-if="getStepStatus(step.key) === 'running'"
              class="h-5 w-5 animate-spin"
            />
            <Circle
              v-else
              class="h-5 w-5"
            />
          </div>
          <span
            :class="[
              'text-xs mt-2 font-medium',
              getStepStatus(step.key) === 'running' ? 'text-primary' : 'text-muted-foreground'
            ]"
          >
            {{ step.label }}
          </span>
        </div>

        <!-- Connector line -->
        <div
          v-if="index < steps.length - 1"
          :class="[
            'flex-1 h-1 mx-2 rounded transition-all duration-300',
            getLineColor(index)
          ]"
        />
      </template>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/CreationTimeline.vue
git commit -m "feat: add CreationTimeline horizontal step component"
```

---

## Task 14: Create Toast Composable

**Files:**
- Create: `resources/js/composables/useToast.ts`
- Create: `resources/js/components/ui/toast/Toaster.vue`
- Create: `resources/js/components/ui/toast/index.ts`

- [ ] **Step 1: Install sonner if not already**

Run:
```bash
npm install sonner
```

Expected: Already installed from Task 1

- [ ] **Step 2: Create Toaster component**

Create `resources/js/components/ui/toast/Toaster.vue`:

```vue
<script setup lang="ts">
import { Toaster as Sonner, type ToasterProps } from 'sonner';

const props = withDefaults(defineProps<ToasterProps>(), {
  position: 'bottom-right',
  richColors: true,
  closeButton: true,
  duration: 4000,
});
</script>

<template>
  <Sonner v-bind="props" class="toaster group" />
</template>
```

- [ ] **Step 3: Create toast index**

Create `resources/js/components/ui/toast/index.ts`:

```typescript
export { default as Toaster } from './Toaster.vue';
export { toast } from 'sonner';
```

- [ ] **Step 4: Create useToast composable**

Create `resources/js/composables/useToast.ts`:

```typescript
import { toast } from 'sonner';

export function useToast() {
  const success = (message: string, description?: string) => {
    toast.success(message, {
      description,
    });
  };

  const error = (message: string, description?: string) => {
    toast.error(message, {
      description,
    });
  };

  const warning = (message: string, description?: string) => {
    toast.warning(message, {
      description,
    });
  };

  const info = (message: string, description?: string) => {
    toast.info(message, {
      description,
    });
  };

  const loading = (message: string, description?: string) => {
    return toast.loading(message, {
      description,
    });
  };

  const dismiss = (toastId?: string | number) => {
    toast.dismiss(toastId);
  };

  return {
    success,
    error,
    warning,
    info,
    loading,
    dismiss,
    toast,
  };
}
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/composables/useToast.ts resources/js/components/ui/toast/
git commit -m "feat: add toast notification system with sonner"
```

---

## Task 15: Update Show Page with Timeline and WebSocket

**Files:**
- Modify: `resources/js/Pages/App/Databases/Show.vue`

- [ ] **Step 1: Update Show page**

Update `resources/js/Pages/App/Databases/Show.vue`:

```vue
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreationTimeline from '@/components/CreationTimeline.vue';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/composables/useToast';
import type { Database, StepUpdatePayload, DatabaseCreatedPayload, DatabaseFailedPayload } from '@/types/database';
import { ArrowLeft, Server, Database as DatabaseIcon, Calendar, Link2, AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import echo from '@/lib/echo';

const props = defineProps<{
    database: Database;
}>();

const { success, error } = useToast();

const currentStep = ref(props.database.current_step);
const progress = ref(props.database.progress);
const status = ref(props.database.status);
const errorMessage = ref(props.database.error_message);

const getStatusBadge = () => {
    switch (status.value) {
        case 'pending':
            return { variant: 'outline', class: 'bg-yellow-500/10 text-yellow-500', label: 'Pendente' };
        case 'processing':
            return { variant: 'outline', class: 'bg-blue-500/10 text-blue-500', label: 'Processando' };
        case 'ready':
            return { variant: 'default', class: 'bg-green-500/10 text-green-500', label: 'Pronto' };
        case 'failed':
            return { variant: 'destructive', class: '', label: 'Falhou' };
        default:
            return { variant: 'outline', class: '', label: status.value };
    }
};

let channel: ReturnType<typeof echo['private']>;

onMounted(() => {
    channel = echo.private(`database.${props.database.id}`);

    channel.listen('.step.updated', (data: StepUpdatePayload) => {
        currentStep.value = data.step;
        progress.value = data.progress;
        status.value = data.database.status;
    });

    channel.listen('.database.created', (data: DatabaseCreatedPayload) => {
        status.value = 'ready';
        progress.value = 100;
        success('Database criado!', `O database ${data.database.name} está pronto para uso.`);
    });

    channel.listen('.database.failed', (data: DatabaseFailedPayload) => {
        status.value = 'failed';
        errorMessage.value = data.error;
        error('Falha na criação', data.error);
    });
});

onUnmounted(() => {
    if (channel) {
        echo.leave(`database.${props.database.id}`);
    }
});
</script>

<template>
    <Head :title="`Database: ${database.name}`" />
    <Toaster />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('app.databases.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground flex items-center gap-2">
                        <DatabaseIcon class="h-6 w-6 text-muted-foreground" />
                        {{ database.display_name || database.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Detalhes do database
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Timeline Card (only for pending/processing databases) -->
            <Card v-if="status === 'pending' || status === 'processing'">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Loader2 v-if="status === 'processing'" class="h-5 w-5 animate-spin text-primary" />
                        Criação do Database
                    </CardTitle>
                    <CardDescription>
                        Acompanhe o progresso da criação em tempo real
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <CreationTimeline
                        :current-step="currentStep"
                        :progress="progress"
                        :status="status"
                    />
                </CardContent>
            </Card>

            <!-- Error Alert -->
            <Alert v-if="status === 'failed'" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Erro na criação</AlertTitle>
                <AlertDescription>
                    {{ errorMessage }}
                </AlertDescription>
            </Alert>

            <!-- Success Alert -->
            <Alert v-if="status === 'ready'" class="border-green-500/50 bg-green-500/10">
                <CheckCircle2 class="h-4 w-4 text-green-500" />
                <AlertTitle class="text-green-500">Database pronto</AlertTitle>
                <AlertDescription>
                    O database está criado e disponível para uso.
                </AlertDescription>
            </Alert>

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Informações</CardTitle>
                        <CardDescription>Detalhes do database</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Nome</span>
                            <span class="font-medium">{{ database.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Database Name</span>
                            <span class="font-medium font-mono text-sm">{{ database.database_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Status</span>
                            <Badge
                                :variant="getStatusBadge().variant"
                                :class="getStatusBadge().class"
                            >
                                {{ getStatusBadge().label }}
                            </Badge>
                        </div>
                        <div v-if="database.description" class="pt-2 border-t">
                            <span class="text-muted-foreground text-sm">Descrição</span>
                            <p class="mt-1">{{ database.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Conexão</CardTitle>
                        <CardDescription>Configurações de conexão</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground flex items-center gap-2">
                                <Server class="h-4 w-4" />
                                Host
                            </span>
                            <span class="font-medium font-mono text-sm">{{ database.host }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Port</span>
                            <span class="font-medium">{{ database.port }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Credentials</CardTitle>
                        <CardDescription>Credenciais com acesso a este database</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Total de credenciais</span>
                            <Badge variant="secondary">
                                <Link2 class="h-3 w-3 mr-1" />
                                {{ database.credentials_count ?? 0 }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Metadados</CardTitle>
                        <CardDescription>Informações de criação</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                Criado em
                            </span>
                            <span class="text-sm">{{ new Date(database.created_at).toLocaleString('pt-BR') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                Atualizado em
                            </span>
                            <span class="text-sm">{{ new Date(database.updated_at).toLocaleString('pt-BR') }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 2: Add missing import**

The page uses `Loader2` from lucide-vue-next, add it to imports.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/App/Databases/Show.vue
git commit -m "feat: add realtime timeline to database show page"
```

---

## Task 16: Create NotificationCenter Component

**Files:**
- Create: `resources/js/components/NotificationCenter.vue`

- [ ] **Step 1: Create NotificationCenter component**

Create `resources/js/components/NotificationCenter.vue`:

```vue
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Bell, Check, CheckCheck, Loader2 } from 'lucide-vue-next';
import type { Notification } from '@/types/notification';

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);

const fetchNotifications = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/notifications', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });

        if (response.ok) {
            const data = await response.json();
            notifications.value = data.data;
        }
    } catch (e) {
        console.error('Failed to fetch notifications:', e);
    } finally {
        loading.value = false;
    }
};

const fetchUnreadCount = async () => {
    try {
        const response = await fetch('/api/notifications/unread-count', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });

        if (response.ok) {
            const data = await response.json();
            unreadCount.value = data.count;
        }
    } catch (e) {
        console.error('Failed to fetch unread count:', e);
    }
};

const markAsRead = async (notificationId: number) => {
    try {
        await fetch(`/api/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'include',
        });

        const notification = notifications.value.find(n => n.id === notificationId);
        if (notification) {
            notification.read = true;
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (e) {
        console.error('Failed to mark notification as read:', e);
    }
};

const markAllAsRead = async () => {
    try {
        await fetch('/api/notifications/read-all', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'include',
        });

        notifications.value.forEach(n => n.read = true);
        unreadCount.value = 0;
    } catch (e) {
        console.error('Failed to mark all as read:', e);
    }
};

const formatTime = (dateString: string): string => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();

    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Agora';
    if (minutes < 60) return `${minutes}m atrás`;
    if (hours < 24) return `${hours}h atrás`;
    if (days < 7) return `${days}d atrás`;

    return date.toLocaleDateString('pt-BR');
};

onMounted(() => {
    fetchNotifications();
    fetchUnreadCount();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="h-5 w-5" />
                <Badge
                    v-if="unreadCount > 0"
                    variant="destructive"
                    class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notificações</span>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-auto py-1 px-2 text-xs"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="h-3 w-3 mr-1" />
                    Marcar todas
                </Button>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div v-if="loading" class="p-4 flex justify-center">
                <Loader2 class="h-5 w-5 animate-spin text-muted-foreground" />
            </div>

            <div v-else-if="notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                Nenhuma notificação
            </div>

            <template v-else>
                <div class="max-h-64 overflow-y-auto">
                    <DropdownMenuItem
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex flex-col items-start gap-1 p-3 cursor-pointer"
                        :class="{ 'bg-muted/50': !notification.read }"
                        @click="!notification.read && markAsRead(notification.id)"
                    >
                        <div class="flex items-center gap-2 w-full">
                            <span class="font-medium text-sm flex-1">{{ notification.title }}</span>
                            <span class="text-xs text-muted-foreground">{{ formatTime(notification.created_at) }}</span>
                        </div>
                        <p class="text-xs text-muted-foreground line-clamp-2">
                            {{ notification.message }}
                        </p>
                    </DropdownMenuItem>
                </div>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/NotificationCenter.vue
git commit -m "feat: add NotificationCenter component with dropdown"
```

---

## Task 17: Add NotificationCenter to Layout

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.vue`

- [ ] **Step 1: Update layout**

Update `resources/js/Layouts/AuthenticatedLayout.vue` to add NotificationCenter:

Find the header section with the dark mode toggle and add NotificationCenter:

```vue
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Database,
    Home,
    Sun,
    Moon,
    LogOut,
    Settings,
    PanelLeftClose,
    PanelLeft,
    ChevronDown,
    Flag,
    Key,
} from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';
import { ref } from 'vue';
import NotificationCenter from '@/components/NotificationCenter.vue';

// ... rest of the component stays the same
```

Then update the header buttons section:

```vue
            <div class="flex items-center gap-2">
                <NotificationCenter />
                <Button variant="ghost" size="icon" @click="toggleDark">
                    <Sun v-if="isDark" class="h-5 w-5" />
                    <Moon v-else class="h-5 w-5" />
                </Button>
                <Link :href="route('logout')" method="post" as="button">
                    <Button variant="ghost" size="sm" class="gap-2">
                        <LogOut class="h-4 w-4" />
                        Sair
                    </Button>
                </Link>
            </div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.vue
git commit -m "feat: add NotificationCenter to authenticated layout"
```

---

## Task 18: Update Create Page to Redirect Properly

**Files:**
- Modify: `resources/js/Pages/App/Databases/Create.vue`

- [ ] **Step 1: Update create page to show toast and redirect**

Update the submit function in `resources/js/Pages/App/Databases/Create.vue`:

```vue
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from '@/composables/useToast';
import type { CredentialCollection } from '@/types/credential';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';

const props = defineProps<{
    credentials?: CredentialCollection;
}>();

const { info } = useToast();

const form = ref({
    name: '',
    display_name: '',
    description: '',
    host: 'localhost',
    port: 5432,
    database_name: '',
    is_active: true,
    credential_ids: [] as string[],
});

const loading = ref(false);
const errors = ref<Record<string, string>>({});

const getCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    return meta?.content || '';
};

const submit = async (): Promise<void> => {
    loading.value = true;
    errors.value = {};

    try {
        const response = await fetch(route('app.databases.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422) {
                errors.value = data.errors || {};
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        info('Criando database...', 'Você será redirecionado para acompanhar o progresso.');

        router.visit(route('app.databases.show', data.data.id));
    } catch (error) {
        console.error('Failed to create database:', error);
    } finally {
        loading.value = false;
    }
};
</script>

<!-- Template stays the same -->
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/App/Databases/Create.vue
git commit -m "feat: show info toast on database creation"
```

---

## Task 19: Run All Tests and Fix Issues

**Files:**
- Various test fixes

- [ ] **Step 1: Run all tests**

Run:
```bash
php artisan test
```

Expected: All tests pass

- [ ] **Step 2: Fix any failing tests**

If any tests fail, debug and fix them.

- [ ] **Step 3: Run frontend build**

Run:
```bash
npm run build
```

Expected: Build succeeds without errors

- [ ] **Step 4: Commit any fixes**

```bash
git add -A
git commit -m "fix: resolve test failures and build issues"
```

---

## Task 20: Update CLAUDE.md Documentation

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Update documentation**

Add to `CLAUDE.md` in the appropriate section:

```markdown
### Async Database Creation

- **Job:** `CreateDatabaseJob` - Dispatched when database is created
- **Events:** `DatabaseStepUpdated`, `DatabaseCreated`, `DatabaseFailed` - Broadcast via WebSocket
- **Steps:** 7 steps (validating, creating, configuring, migrating, permissions, testing, ready)
- **WebSocket:** Laravel Echo + Reverb on `database.{id}` channel
- **Notifications:** Toast (sonner) + Notification Center (persistent)
```

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update CLAUDE.md with async database creation info"
```

---

## Success Criteria Checklist

- [ ] Database creation is async via Redis queue
- [ ] Timeline shows 7 steps horizontally with real-time updates
- [ ] Toast appears when database is created
- [ ] Notification center shows history (7 days)
- [ ] Schema changes are logged to `database_schema_histories`
- [ ] WebSocket works via Laravel Echo + Reverb
- [ ] All tests pass
- [ ] Frontend builds without errors

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-03-29-async-database-creation.md`. Two execution options:

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

**Which approach?**
