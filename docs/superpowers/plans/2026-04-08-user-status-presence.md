# User Status & Presence System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement real-time user presence and status system with Redis for live tracking and PostgreSQL for persistent history of manual changes and important events.

**Architecture:** Hybrid approach using Redis for real-time presence (with TTL/heartbeat) and PostgreSQL for persistent activity logging. Laravel Echo/Reverb broadcasts status changes to admin users in real-time.

**Tech Stack:** Laravel 13, Redis 7+, PostgreSQL 8+, Laravel Echo/Reverb, Vue 3 + TypeScript, Pinia

---

## File Structure

```
app/
├── Enums/
│   └── UserStatusEnum.php (NEW) - Status enum with labels/colors for each state
├── Services/
│   ├── UserStatusService.php (NEW) - Redis operations for status management
│   └── UserActivityService.php (NEW) - PostgreSQL operations for activity logging
├── Events/
│   ├── UserStatusUpdated.php (NEW) - Broadcast status changes via Echo
│   └── UserActivityLogged.php (NEW) - Broadcast activity logs via Echo
├── Listeners/
│   ├── HandleUserLogout.php (NEW) - Set offline on logout event
│   ├── LogDatabaseCreated.php (NEW) - Log database creation activity
│   └── LogCredentialCreated.php (NEW) - Log credential creation activity
├── Http/
│   ├── Controllers/
│   │   ├── UserStatusController.php (NEW) - API endpoints for status
│   │   └── UserActivityController.php (NEW) - API endpoints for activities
│   └── Middleware/
│       └── TrackUserStatus.php (NEW) - Auto online + heartbeat middleware
└── Models/
    ├── User.php (MODIFY) - Add status relationship
    └── UserActivity.php (NEW) - Activity model

resources/js/
├── components/
│   ├── StatusPickerDropdown.vue (NEW) - Status selector button with dropdown
│   ├── UserAvatarWithStatus.vue (NEW) - Avatar with colored status border
│   └── UserActivityTimeline.vue (NEW) - Timeline of user activities
├── composables/
│   ├── useUserStatus.ts (NEW) - Status management composable
│   └── useEchoChannels.ts (NEW) - Echo channel listeners
└── types/
    └── status.ts (NEW) - TypeScript types for status

database/
└── migrations/
    └── 2026_04_08_000001_create_user_activities_table.php (NEW)

lang/
├── pt.json (MODIFY) - Add PT translations
├── en.json (MODIFY) - Add EN translations
└── es.json (MODIFY) - Add ES translations

routes/
├── api.php (MODIFY) - Add status/activity routes
└── channels.php (MODIFY) - Add user status channel authorization

tests/
├── Unit/
│   ├── Enums/
│   │   └── UserStatusEnumTest.php (NEW)
│   └── Services/
│       ├── UserStatusServiceTest.php (NEW)
│       └── UserActivityServiceTest.php (NEW)
└── Feature/
    ├── UserStatusTest.php (NEW)
    └── UserActivityTest.php (NEW)
```

---

## Task 1: Create UserStatusEnum

**Files:**
- Create: `app/Enums/UserStatusEnum.php`
- Test: `tests/Unit/Enums/UserStatusEnumTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Enums/UserStatusEnumTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserStatusEnum;
use Tests\TestCase;

class UserStatusEnumTest extends TestCase
{
    public function test_online_case_exists(): void
    {
        $status = UserStatusEnum::ONLINE;
        $this->assertEquals('online', $status->value);
    }

    public function test_away_case_exists(): void
    {
        $status = UserStatusEnum::AWAY;
        $this->assertEquals('away', $status->value);
    }

    public function test_busy_case_exists(): void
    {
        $status = UserStatusEnum::BUSY;
        $this->assertEquals('busy', $status->value);
    }

    public function test_offline_case_exists(): void
    {
        $status = UserStatusEnum::OFFLINE;
        $this->assertEquals('offline', $status->value);
    }

    public function test_label_returns_portuguese_translation(): void
    {
        $this->assertEquals('Online', UserStatusEnum::ONLINE->label());
        $this->assertEquals('Ausente', UserStatusEnum::AWAY->label());
        $this->assertEquals('Ocupado', UserStatusEnum::BUSY->label());
        $this->assertEquals('Offline', UserStatusEnum::OFFLINE->label());
    }

    public function test_color_returns_hex_value(): void
    {
        $this->assertEquals('#22c55e', UserStatusEnum::ONLINE->color());
        $this->assertEquals('#eab308', UserStatusEnum::AWAY->color());
        $this->assertEquals('#ef4444', UserStatusEnum::BUSY->color());
        $this->assertEquals('#6b7280', UserStatusEnum::OFFLINE->color());
    }

    public function test_all_statuses_returns_array(): void
    {
        $statuses = UserStatusEnum::all();
        $this->assertCount(4, $statuses);
        $this->assertArrayHasKey('online', $statuses);
        $this->assertArrayHasKey('away', $statuses);
        $this->assertArrayHasKey('busy', $statuses);
        $this->assertArrayHasKey('offline', $statuses);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/UserStatusEnumTest.php`
Expected: FAIL with "Class App\Enums\UserStatusEnum not found"

- [ ] **Step 3: Write minimal implementation**

Create `app/Enums/UserStatusEnum.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatusEnum: string
{
    case ONLINE = 'online';
    case AWAY = 'away';
    case BUSY = 'busy';
    case OFFLINE = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'Online',
            self::AWAY => 'Ausente',
            self::BUSY => 'Ocupado',
            self::OFFLINE => 'Offline',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ONLINE => '#22c55e',
            self::AWAY => '#eab308',
            self::BUSY => '#ef4444',
            self::OFFLINE => '#6b7280',
        };
    }

    public static function all(): array
    {
        return [
            'online' => self::ONLINE,
            'away' => self::AWAY,
            'busy' => self::BUSY,
            'offline' => self::OFFLINE,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/UserStatusEnumTest.php`
Expected: PASS (8 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Enums/UserStatusEnum.php tests/Unit/Enums/UserStatusEnumTest.php
git commit -m "feat: add UserStatusEnum with labels and colors"
```

---

## Task 2: Create user_activities Migration and Model

**Files:**
- Create: `database/migrations/2026_04_08_000001_create_user_activities_table.php`
- Create: `app/Models/UserActivity.php`
- Test: `tests/Unit/Models/UserActivityTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Models/UserActivityTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_user_has_activities_relationship(): void
    {
        $activity = UserActivity::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertCount(1, $this->user->activities);
        $this->assertEquals($activity->id, $this->user->activities->first()->id);
    }

    public function test_status_changed_activity_type(): void
    {
        $activity = UserActivity::factory()->statusChanged()->create([
            'user_id' => $this->user->id,
            'from_status' => 'online',
            'to_status' => 'away',
        ]);

        $this->assertEquals('status_changed', $activity->activity_type);
        $this->assertEquals('online', $activity->from_status);
        $this->assertEquals('away', $activity->to_status);
    }

    public function test_database_created_activity_type(): void
    {
        $activity = UserActivity::factory()->databaseCreated()->create([
            'user_id' => $this->user->id,
            'metadata' => ['database_name' => 'production'],
        ]);

        $this->assertEquals('database_created', $activity->activity_type);
        $this->assertEquals(['database_name' => 'production'], $activity->metadata);
    }

    public function test_credential_created_activity_type(): void
    {
        $activity = UserActivity::factory()->credentialCreated()->create([
            'user_id' => $this->user->id,
            'metadata' => ['credential_name' => 'Dev Team'],
        ]);

        $this->assertEquals('credential_created', $activity->activity_type);
        $this->assertEquals(['credential_name' => 'Dev Team'], $activity->metadata);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Models/UserActivityTest.php`
Expected: FAIL with "Table 'dockabase_testing.user_activities' doesn't exist"

- [ ] **Step 3: Create migration**

Create `database/migrations/2026_04_08_000001_create_user_activities_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 16);
            $table->enum('activity_type', ['status_changed', 'database_created', 'credential_created']);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
```

- [ ] **Step 4: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 5: Create UserActivity model**

Create `app/Models/UserActivity.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    use HasFactory, HasKsuid;

    protected $fillable = [
        'user_id',
        'activity_type',
        'from_status',
        'to_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeStatusChanged($query)
    {
        return $query->where('activity_type', 'status_changed');
    }

    public function scopeDatabaseCreated($query)
    {
        return $query->where('activity_type', 'database_created');
    }

    public function scopeCredentialCreated($query)
    {
        return $query->where('activity_type', 'credential_created');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
```

- [ ] **Step 6: Create UserActivity factory**

Create `database/factories/UserActivityFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserActivityFactory extends Factory
{
    protected $model = UserActivity::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_type' => 'status_changed',
            'from_status' => null,
            'to_status' => null,
            'metadata' => null,
        ];
    }

    public function statusChanged(): self
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'status_changed',
            'from_status' => 'online',
            'to_status' => 'away',
        ]);
    }

    public function databaseCreated(): self
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'database_created',
            'metadata' => ['database_name' => $this->faker->word()],
        ]);
    }

    public function credentialCreated(): self
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'credential_created',
            'metadata' => ['credential_name' => $this->faker->words(2, true)],
        ]);
    }
}
```

- [ ] **Step 7: Add relationship to User model**

Modify `app/Models/User.php` - add after the `credentials()` relationship:

```php
/**
 * Activities logged for the user.
 */
public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(UserActivity::class)->latest();
}
```

Add import at top:
```php
use App\Models\UserActivity;
```

- [ ] **Step 8: Run test to verify it passes**

Run: `php artisan test tests/Unit/Models/UserActivityTest.php`
Expected: PASS (5 tests)

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_04_08_000001_create_user_activities_table.php
git add app/Models/UserActivity.php database/factories/UserActivityFactory.php
git add app/Models/User.php tests/Unit/Models/UserActivityTest.php
git commit -m "feat: add user_activities table and UserActivity model"
```

---

## Task 3: Create UserStatusService (Redis Operations)

**Files:**
- Create: `app/Services/UserStatusService.php`
- Test: `tests/Unit/Services/UserStatusServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/UserStatusServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class UserStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserStatusService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserStatusService();
        $this->user = User::factory()->create();

        // Clear Redis before each test
        Redis::flushall();
    }

    public function test_set_online_saves_to_redis(): void
    {
        $this->service->setOnline($this->user);

        $key = "user:{$this->user->id}:status";
        $data = json_decode(Redis::get($key), true);

        $this->assertNotEmpty($data);
        $this->assertEquals('online', $data['status']);
        $this->assertArrayHasKey('updated_at', $data);
    }

    public function test_set_online_sets_expiration(): void
    {
        $this->service->setOnline($this->user);

        $key = "user:{$this->user->id}:status";
        $ttl = Redis::ttl($key);

        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(300, $ttl); // 5 minutes max
    }

    public function test_set_offline_removes_from_redis(): void
    {
        $this->service->setOnline($this->user);
        $this->assertTrue(Redis::exists("user:{$this->user->id}:status"));

        $this->service->setOffline($this->user);
        $this->assertFalse(Redis::exists("user:{$this->user->id}:status"));
    }

    public function test_set_status_saves_custom_status(): void
    {
        $result = $this->service->setStatus($this->user, UserStatusEnum::AWAY);

        $key = "user:{$this->user->id}:status";
        $data = json_decode(Redis::get($key), true);

        $this->assertEquals('away', $data['status']);
        $this->assertEquals(['from' => null, 'to' => 'away'], $result);
    }

    public function test_set_status_returns_previous_status(): void
    {
        $this->service->setOnline($this->user);
        $result = $this->service->setStatus($this->user, UserStatusEnum::BUSY);

        $this->assertEquals(['from' => 'online', 'to' => 'busy'], $result);
    }

    public function test_get_status_returns_current_status(): void
    {
        $this->service->setOnline($this->user);
        $status = $this->service->getStatus($this->user);

        $this->assertEquals(UserStatusEnum::ONLINE, $status);
    }

    public function test_get_status_returns_offline_when_not_set(): void
    {
        $status = $this->service->getStatus($this->user);

        $this->assertEquals(UserStatusEnum::OFFLINE, $status);
    }

    public function test_refresh_heartbeat_updates_timestamp(): void
    {
        $this->service->setOnline($this->user);
        sleep(1);
        $this->service->refreshHeartbeat($this->user);

        $key = "user:{$this->user->id}:heartbeat";
        $heartbeat = Redis::get($key);

        $this->assertNotEmpty($heartbeat);
    }

    public function test_is_online_returns_true_when_online(): void
    {
        $this->service->setOnline($this->user);

        $this->assertTrue($this->service->isOnline($this->user));
    }

    public function test_is_online_returns_false_when_offline(): void
    {
        $this->assertFalse($this->service->isOnline($this->user));
    }

    public function test_get_multiple_statuses_for_users(): void
    {
        $user2 = User::factory()->create();

        $this->service->setOnline($this->user);
        $this->service->setStatus($user2, UserStatusEnum::BUSY);

        $statuses = $this->service->getMultipleStatuses([$this->user->id, $user2->id]);

        $this->assertEquals('online', $statuses[$this->user->id]);
        $this->assertEquals('busy', $statuses[$user2->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/UserStatusServiceTest.php`
Expected: FAIL with "Class App\Services\UserStatusService not found"

- [ ] **Step 3: Create UserStatusService**

Create `app/Services/UserStatusService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

class UserStatusService
{
    private const int STATUS_TTL = 300; // 5 minutes
    private const int HEARTBEAT_TTL = 120; // 2 minutes

    public function setOnline(User $user): void
    {
        $this->setStatus($user, UserStatusEnum::ONLINE);
    }

    public function setOffline(User $user): void
    {
        $this->clearUserKeys($user->id);
    }

    public function setStatus(User $user, UserStatusEnum $status): array
    {
        $currentStatus = $this->getStatus($user);

        $data = [
            'status' => $status->value,
            'updated_at' => now()->toIso8601String(),
        ];

        $key = "user:{$user->id}:status";
        Redis::setex($key, self::STATUS_TTL, json_encode($data));

        return [
            'from' => $currentStatus->value,
            'to' => $status->value,
        ];
    }

    public function getStatus(User $user): UserStatusEnum
    {
        $key = "user:{$user->id}:status";
        $data = Redis::get($key);

        if (!$data) {
            return UserStatusEnum::OFFLINE;
        }

        $decoded = json_decode($data, true);
        return UserStatusEnum::from($decoded['status']);
    }

    public function refreshHeartbeat(User $user): void
    {
        $key = "user:{$user->id}:heartbeat";
        Redis::setex($key, self::HEARTBEAT_TTL, now()->toIso8601String());

        // If status was offline (expired), bring back to online
        $statusKey = "user:{$user->id}:status";
        if (!Redis::exists($statusKey)) {
            $this->setOnline($user);
        }
    }

    public function isOnline(User $user): bool
    {
        return $this->getStatus($user) !== UserStatusEnum::OFFLINE;
    }

    public function getMultipleStatuses(array $userIds): array
    {
        $statuses = [];

        foreach ($userIds as $userId) {
            $key = "user:{$userId}:status";
            $data = Redis::get($key);
            $statuses[$userId] = $data ? json_decode($data, true)['status'] : 'offline';
        }

        return $statuses;
    }

    private function clearUserKeys(string $userId): void
    {
        $pattern = "user:{$userId}:*";
        $keys = Redis::keys($pattern);

        if (!empty($keys)) {
            Redis::del($keys);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/UserStatusServiceTest.php`
Expected: PASS (11 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/UserStatusService.php tests/Unit/Services/UserStatusServiceTest.php
git commit -m "feat: add UserStatusService for Redis status management"
```

---

## Task 4: Create UserActivityService (PostgreSQL Operations)

**Files:**
- Create: `app/Services/UserActivityService.php`
- Test: `tests/Unit/Services/UserActivityServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/UserActivityServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\UserActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserActivityService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserActivityService();
        $this->user = User::factory()->create();
    }

    public function test_log_status_change_creates_activity(): void
    {
        $this->service->logStatusChange($this->user, UserStatusEnum::ONLINE, UserStatusEnum::AWAY);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $this->user->id,
            'activity_type' => 'status_changed',
            'from_status' => 'online',
            'to_status' => 'away',
        ]);
    }

    public function test_log_status_change_returns_activity(): void
    {
        $activity = $this->service->logStatusChange($this->user, UserStatusEnum::ONLINE, UserStatusEnum::BUSY);

        $this->assertInstanceOf(UserActivity::class, $activity);
        $this->assertEquals('status_changed', $activity->activity_type);
        $this->assertEquals('online', $activity->from_status);
        $this->assertEquals('busy', $activity->to_status);
    }

    public function test_log_database_created_creates_activity(): void
    {
        $database = \App\Models\Database::factory()->create();

        $this->service->logDatabaseCreated($this->user, $database);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $this->user->id,
            'activity_type' => 'database_created',
        ]);
    }

    public function test_log_database_created_stores_metadata(): void
    {
        $database = \App\Models\Database::factory()->create([
            'name' => 'production',
        ]);

        $activity = $this->service->logDatabaseCreated($this->user, $database);

        $this->assertEquals('production', $activity->metadata['database_name']);
        $this->assertArrayHasKey('permission', $activity->metadata);
    }

    public function test_log_credential_created_creates_activity(): void
    {
        $credential = \App\Models\Credential::factory()->create();

        $this->service->logCredentialCreated($this->user, $credential);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $this->user->id,
            'activity_type' => 'credential_created',
        ]);
    }

    public function test_log_credential_created_stores_metadata(): void
    {
        $credential = \App\Models\Credential::factory()->create([
            'name' => 'Dev Team',
        ]);

        $activity = $this->service->logCredentialCreated($this->user, $credential);

        $this->assertEquals('Dev Team', $activity->metadata['credential_name']);
    }

    public function test_get_user_activities_returns_paginated(): void
    {
        UserActivity::factory()->count(25)->create([
            'user_id' => $this->user->id,
        ]);

        $activities = $this->service->getUserActivities($this->user);

        $this->assertCount(20, $activities->items());
    }

    public function test_get_user_activities_orders_by_recent(): void
    {
        $old = UserActivity::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);

        $new = UserActivity::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        $activities = $this->service->getUserActivities($this->user);

        $this->assertEquals($new->id, $activities->first()->id);
        $this->assertEquals($old->id, $activities->last()->id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/UserActivityServiceTest.php`
Expected: FAIL with "Class App\Services\UserActivityService not found"

- [ ] **Step 3: Create UserActivityService**

Create `app/Services/UserActivityService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatusEnum;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Pagination\LengthAwarePaginator;

class UserActivityService
{
    public function logStatusChange(User $user, UserStatusEnum $from, UserStatusEnum $to): UserActivity
    {
        return UserActivity::create([
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
            'from_status' => $from->value,
            'to_status' => $to->value,
            'metadata' => null,
        ]);
    }

    public function logDatabaseCreated(User $user, Database $database): UserActivity
    {
        return UserActivity::create([
            'user_id' => $user->id,
            'activity_type' => 'database_created',
            'from_status' => null,
            'to_status' => null,
            'metadata' => [
                'database_name' => $database->name,
                'permission' => $database->permission?->value ?? 'unknown',
            ],
        ]);
    }

    public function logCredentialCreated(User $user, Credential $credential): UserActivity
    {
        return UserActivity::create([
            'user_id' => $user->id,
            'activity_type' => 'credential_created',
            'from_status' => null,
            'to_status' => null,
            'metadata' => [
                'credential_name' => $credential->name,
                'permission' => $credential->permission->value,
            ],
        ]);
    }

    public function getUserActivities(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->activities()
            ->recent()
            ->paginate($perPage);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/UserActivityServiceTest.php`
Expected: PASS (8 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/UserActivityService.php tests/Unit/Services/UserActivityServiceTest.php
git commit -m "feat: add UserActivityService for PostgreSQL activity logging"
```

---

## Task 5: Create Echo Events

**Files:**
- Create: `app/Events/UserStatusUpdated.php`
- Create: `app/Events/UserActivityLogged.php`
- Test: `tests/Unit/Events/UserStatusUpdatedTest.php`
- Test: `tests/Unit/Events/UserActivityLoggedTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/Events/UserStatusUpdatedTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\UserStatusUpdated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusUpdatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_contains_user_id(): void
    {
        $user = User::factory()->create();
        $event = new UserStatusUpdated($user->id, 'online', 'away');

        $this->assertEquals($user->id, $event->userId);
    }

    public function test_event_contains_status(): void
    {
        $user = User::factory()->create();
        $event = new UserStatusUpdated($user->id, 'online', 'away');

        $this->assertEquals('away', $event->status);
        $this->assertEquals('online', $event->previousStatus);
    }

    public function test_event_broadcasts_on_private_channel(): void
    {
        $user = User::factory()->create();
        $event = new UserStatusUpdated($user->id, 'online', 'away');

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('users.' . $user->id, $channels[0]->name);
    }

    public function test_event_broadcasts_with_correct_data(): void
    {
        $user = User::factory()->create();
        $event = new UserStatusUpdated($user->id, 'online', 'away');

        $data = $event->broadcastWith();

        $this->assertEquals($user->id, $data['user_id']);
        $this->assertEquals('away', $data['status']);
        $this->assertEquals('online', $data['previous_status']);
    }

    public function test_event_broadcasts_as_correct_name(): void
    {
        $user = User::factory()->create();
        $event = new UserStatusUpdated($user->id, 'online', 'away');

        $this->assertEquals('UserStatusUpdated', $event->broadcastAs());
    }
}
```

Create `tests/Unit/Events/UserActivityLoggedTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\UserActivityLogged;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityLoggedTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_contains_activity(): void
    {
        $activity = UserActivity::factory()->create();
        $event = new UserActivityLogged($activity);

        $this->assertEquals($activity->id, $event->activity->id);
    }

    public function test_event_broadcasts_on_private_channel(): void
    {
        $activity = UserActivity::factory()->create();
        $event = new UserActivityLogged($activity);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('users.' . $activity->user_id, $channels[0]->name);
    }

    public function test_event_broadcasts_with_correct_data(): void
    {
        $activity = UserActivity::factory()->statusChanged()->create();
        $event = new UserActivityLogged($activity);

        $data = $event->broadcastWith();

        $this->assertEquals($activity->id, $data['id']);
        $this->assertEquals($activity->activity_type, $data['activity_type']);
    }

    public function test_event_broadcasts_as_correct_name(): void
    {
        $activity = UserActivity::factory()->create();
        $event = new UserActivityLogged($activity);

        $this->assertEquals('UserActivityLogged', $event->broadcastAs());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Events/UserStatusUpdatedTest.php tests/Unit/Events/UserActivityLoggedTest.php`
Expected: FAIL with "Class App\Events\UserStatusUpdated not found"

- [ ] **Step 3: Create UserStatusUpdated event**

Create `app/Events/UserStatusUpdated.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $status,
        public readonly string $previousStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->userId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status,
            'previous_status' => $this->previousStatus,
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserStatusUpdated';
    }
}
```

- [ ] **Step 4: Create UserActivityLogged event**

Create `app/Events/UserActivityLogged.php`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\UserActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActivityLogged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public UserActivity $activity,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->activity->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->activity->id,
            'user_id' => $this->activity->user_id,
            'activity_type' => $this->activity->activity_type,
            'from_status' => $this->activity->from_status,
            'to_status' => $this->activity->to_status,
            'metadata' => $this->activity->metadata,
            'created_at' => $this->activity->created_at->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserActivityLogged';
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Events/UserStatusUpdatedTest.php tests/Unit/Events/UserActivityLoggedTest.php`
Expected: PASS (9 tests total)

- [ ] **Step 6: Commit**

```bash
git add app/Events/UserStatusUpdated.php app/Events/UserActivityLogged.php
git add tests/Unit/Events/UserStatusUpdatedTest.php tests/Unit/Events/UserActivityLoggedTest.php
git commit -m "feat: add Echo events for status and activity broadcasting"
```

---

## Task 6: Create Listeners (Logout, Database, Credential)

**Files:**
- Create: `app/Listeners/HandleUserLogout.php`
- Create: `app/Listeners/LogDatabaseCreated.php`
- Create: `app/Listeners/LogCredentialCreated.php`
- Modify: `app/Providers/EventServiceProvider.php`
- Test: `tests/Unit/Listeners/HandleUserLogoutTest.php`
- Test: `tests/Unit/Listeners/LogDatabaseCreatedTest.php`
- Test: `tests/Unit/Listeners/LogCredentialCreatedTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/Listeners/HandleUserLogoutTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\HandleUserLogout;
use App\Services\UserStatusService;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HandleUserLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_clears_user_status_from_redis(): void
    {
        $user = User::factory()->create();
        $statusService = $this->app->make(UserStatusService::class);

        // Set user as online
        $statusService->setOnline($user);
        $this->assertTrue($statusService->isOnline($user));

        // Fire logout event
        $listener = new HandleUserLogout($statusService);
        $listener->handle(new Logout('web', $user));

        // Status should be cleared (offline)
        $this->assertFalse($statusService->isOnline($user));
    }
}
```

Create `tests/Unit/Listeners/LogDatabaseCreatedTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\DatabaseCreated;
use App\Listeners\LogDatabaseCreated;
use App\Services\UserActivityService;
use App\Models\Database;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogDatabaseCreatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_created_logs_activity(): void
    {
        $user = User::factory()->create();
        $database = Database::factory()->create(['user_id' => $user->id]);
        $activityService = $this->app->make(UserActivityService::class);

        $listener = new LogDatabaseCreated($activityService);
        $listener->handle(new DatabaseCreated($database));

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'activity_type' => 'database_created',
        ]);
    }
}
```

Create `tests/Unit/Listeners/LogCredentialCreatedTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\LogCredentialCreated;
use App\Services\UserActivityService;
use App\Models\Credential;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogCredentialCreatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_credential_created_logs_activity(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create();
        $credential->users()->attach($user);
        $activityService = $this->app->make(UserActivityService::class);

        $listener = new LogCredentialCreated($activityService);
        $listener->handle(new \App\Events\CredentialCreated($credential, $user->id));

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'activity_type' => 'credential_created',
        ]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Listeners/`
Expected: FAIL with "Class App\Listeners\HandleUserLogout not found"

- [ ] **Step 3: Create HandleUserLogout listener**

Create `app/Listeners/HandleUserLogout.php`:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\UserStatusService;
use Illuminate\Auth\Events\Logout;

class HandleUserLogout
{
    public function __construct(
        private UserStatusService $statusService,
    ) {}

    public function handle(Logout $event): void
    {
        $this->statusService->setOffline($event->user);
    }
}
```

- [ ] **Step 4: Create LogDatabaseCreated listener**

Create `app/Listeners/LogDatabaseCreated.php`:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DatabaseCreated;
use App\Services\UserActivityService;

class LogDatabaseCreated
{
    public function __construct(
        private UserActivityService $activityService,
    ) {}

    public function handle(DatabaseCreated $event): void
    {
        $this->activityService->logDatabaseCreated(
            $event->database->user,
            $event->database
        );
    }
}
```

- [ ] **Step 5: Create CredentialCreated event if not exists**

First, check if event exists: `app/Events/CredentialCreated.php`

If not, create it:
```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Credential;
use Illuminate\Foundation\Events\Dispatchable;

class CredentialCreated
{
    use Dispatchable;

    public function __construct(
        public Credential $credential,
        public string $userId,
    ) {}
}
```

- [ ] **Step 6: Create LogCredentialCreated listener**

Create `app/Listeners/LogCredentialCreated.php`:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CredentialCreated;
use App\Services\UserActivityService;

class LogCredentialCreated
{
    public function __construct(
        private UserActivityService $activityService,
    ) {}

    public function handle(CredentialCreated $event): void
    {
        $user = \App\Models\User::find($event->userId);

        if ($user) {
            $this->activityService->logCredentialCreated($user, $event->credential);
        }
    }
}
```

- [ ] **Step 7: Register listeners in EventServiceProvider**

Modify `app/Providers/EventServiceProvider.php`:

Update the `$listen` array:

```php
protected $listen = [
    Registered::class => [
        SendEmailVerificationNotification::class,
    ],
    \Illuminate\Auth\Events\Logout::class => [
        \App\Listeners\HandleUserLogout::class,
    ],
    \App\Events\DatabaseCreated::class => [
        \App\Listeners\LogDatabaseCreated::class,
    ],
    \App\Events\CredentialCreated::class => [
        \App\Listeners\LogCredentialCreated::class,
    ],
];
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Listeners/`
Expected: PASS (3 tests)

- [ ] **Step 9: Commit**

```bash
git add app/Listeners/ app/Events/CredentialCreated.php app/Providers/EventServiceProvider.php
git add tests/Unit/Listeners/
git commit -m "feat: add listeners for logout, database and credential logging"
```

---

## Task 7: Create TrackUserStatus Middleware

**Files:**
- Create: `app/Http/Middleware/TrackUserStatus.php`
- Modify: `app/Http/Kernel.php` or bootstrap configuration
- Test: `tests/Unit/Middleware/TrackUserStatusTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Middleware/TrackUserStatusTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TrackUserStatus;
use App\Services\UserStatusService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrackUserStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_gets_online_on_first_request(): void
    {
        $user = User::factory()->create();
        $statusService = $this->app->make(UserStatusService::class);
        $middleware = new TrackUserStatus($statusService);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertTrue($statusService->isOnline($user));
    }

    public function test_middleware_refreshes_heartbeat(): void
    {
        $user = User::factory()->create();
        $statusService = $this->app->make(UserStatusService::class);
        $middleware = new TrackUserStatus($statusService);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        // First request - set online
        $middleware->handle($request, fn ($req) => response('OK'));
        $this->assertTrue($statusService->isOnline($user));

        // Second request - refresh heartbeat
        sleep(1);
        $middleware->handle($request, fn ($req) => response('OK'));
        $this->assertTrue($statusService->isOnline($user));
    }

    public function test_guest_user_does_not_get_tracked(): void
    {
        $statusService = $this->app->make(UserStatusService::statusService);
        $middleware = new TrackUserStatus($statusService);

        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Middleware/TrackUserStatusTest.php`
Expected: FAIL with "Class App\Http\Middleware\TrackUserStatus not found"

- [ ] **Step 3: Create TrackUserStatus middleware**

Create `app/Http/Middleware/TrackUserStatus.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\UserStatusService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserStatus
{
    public function __construct(
        private UserStatusService $statusService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            $this->statusService->refreshHeartbeat($user);
        }

        return $response;
    }
}
```

- [ ] **Step 4: Register middleware**

Add to `app/Http/Kernel.php` in the web middleware group or in bootstrap/app.php for Laravel 11+:

For Laravel 13 (check bootstrap/app.php):

Add to the `withMiddleware` section:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\TrackUserStatus::class,
    ]);
})
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Unit/Middleware/TrackUserStatusTest.php`
Expected: PASS (3 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/TrackUserStatus.php app/Http/Kernel.php
git add tests/Unit/Middleware/TrackUserStatusTest.php
git commit -m "feat: add TrackUserStatus middleware for auto online and heartbeat"
```

---

## Task 8: Create UserStatusController (API Endpoints)

**Files:**
- Create: `app/Http/Controllers/UserStatusController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/UserStatusTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/UserStatusTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_get_status(): void
    {
        $response = $this->getJson('/api/user/status');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_their_status(): void
    {
        $statusService = $this->app->make(UserStatusService::class);
        $statusService->setOnline($this->user);

        $response = $this->actingAs($this->user)
            ->getJson('/api/user/status');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'online',
            ]);
    }

    public function test_get_status_returns_offline_when_not_set(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/status');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'offline',
            ]);
    }

    public function test_user_can_set_their_status(): void
    {
        Event::fake();

        $response = $this->actingAs($this->user)
            ->putJson('/api/user/status', [
                'status' => 'away',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'away',
            ]);

        $statusService = $this->app->make(UserStatusService::class);
        $this->assertEquals(UserStatusEnum::AWAY, $statusService->getStatus($this->user));
    }

    public function test_set_status_broadcasts_event(): void
    {
        Event::fake();

        $this->actingAs($this->user)
            ->putJson('/api/user/status', [
                'status' => 'busy',
            ]);

        Event::assertDispatched(\App\Events\UserStatusUpdated::class);
    }

    public function test_set_status_validates_status_value(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/status', [
                'status' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_admin_can_get_any_user_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $statusService = $this->app->make(UserStatusService::class);
        $statusService->setStatus($this->user, UserStatusEnum::BUSY);

        $response = $this->actingAs($admin)
            ->getJson("/api/user/{$this->user->id}/status");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'busy',
            ]);
    }

    public function test_non_admin_cannot_get_other_user_status(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/user/{$otherUser->id}/status");

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/UserStatusTest.php`
Expected: FAIL with "Route not found" for /api/user/status

- [ ] **Step 3: Create UserStatusController**

Create `app/Http/Controllers/UserStatusController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserStatusEnum;
use App\Events\UserStatusUpdated;
use App\Models\User;
use App\Services\UserActivityService;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserStatusController extends Controller
{
    public function __construct(
        private UserStatusService $statusService,
        private UserActivityService $activityService,
    ) {}

    public function getCurrentUserStatus(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $status = $this->statusService->getStatus($user);

        return response()->json([
            'status' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
        ]);
    }

    public function setUserStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:online,away,busy,offline'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $newStatus = UserStatusEnum::from($request->input('status'));
        $previousStatus = $this->statusService->getStatus($user);

        // Update Redis
        $result = $this->statusService->setStatus($user, $newStatus);

        // Log to PostgreSQL only if it's a manual change (not auto online/offline)
        if ($previousStatus !== UserStatusEnum::OFFLINE && $newStatus !== UserStatusEnum::ONLINE) {
            $this->activityService->logStatusChange($user, $previousStatus, $newStatus);
        }

        // Broadcast
        broadcast(new UserStatusUpdated(
            $user->id,
            $newStatus->value,
            $previousStatus->value,
        ));

        return response()->json([
            'status' => $newStatus->value,
            'label' => $newStatus->label(),
            'color' => $newStatus->color(),
        ]);
    }

    public function getUserStatus(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $status = $this->statusService->getStatus($user);

        return response()->json([
            'status' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
        ]);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/api.php`:

```php
use App\Http\Controllers\UserStatusController;

// Status routes (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/user/status', [UserStatusController::class, 'getCurrentUserStatus']);
    Route::put('/user/status', [UserStatusController::class, 'setUserStatus']);
    Route::get('/user/{id}/status', [UserStatusController::class, 'getUserStatus']);
});
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/UserStatusTest.php`
Expected: PASS (9 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/UserStatusController.php routes/api.php
git add tests/Feature/UserStatusTest.php
git commit -m "feat: add user status API endpoints"
```

---

## Task 9: Create UserActivityController

**Files:**
- Create: `app/Http/Controllers/UserActivityController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/UserActivityTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/UserActivityTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserActivity;
use App\Services\UserActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create();
    }

    public function test_guest_cannot_get_activities(): void
    {
        $response = $this->getJson("/api/user/{$this->regularUser->id}/activities");

        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_get_activities(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson("/api/user/{$this->regularUser->id}/activities");

        $response->assertStatus(403);
    }

    public function test_admin_can_get_user_activities(): void
    {
        UserActivity::factory()->count(5)->create([
            'user_id' => $this->regularUser->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/user/{$this->regularUser->id}/activities");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_activities_are_paginated(): void
    {
        UserActivity::factory()->count(25)->create([
            'user_id' => $this->regularUser->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/user/{$this->regularUser->id}/activities");

        $response->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_activities_include_metadata(): void
    {
        $activity = UserActivity::factory()->statusChanged()->create([
            'user_id' => $this->regularUser->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/user/{$this->regularUser->id}/activities");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'activity_type',
                        'from_status',
                        'to_status',
                        'metadata',
                        'created_at',
                    ],
                ],
            ]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/UserActivityTest.php`
Expected: FAIL with "Route not found"

- [ ] **Step 3: Create UserActivityController**

Create `app/Http/Controllers/UserActivityController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserActivityService;
use Illuminate\Http\JsonResponse;

class UserActivityController extends Controller
{
    public function __construct(
        private UserActivityService $activityService,
    ) {}

    public function index(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $activities = $this->activityService->getUserActivities($user);

        return response()->json($activities);
    }
}
```

- [ ] **Step 4: Add route**

Add to `routes/api.php` inside the authenticated group:

```php
use App\Http\Controllers\UserActivityController;

Route::get('/user/{id}/activities', [UserActivityController::class, 'index']);
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/UserActivityTest.php`
Expected: PASS (5 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/UserActivityController.php routes/api.php
git add tests/Feature/UserActivityTest.php
git commit -m "feat: add user activities API endpoint"
```

---

## Task 10: Configure Echo Channel Authorization

**Files:**
- Modify: `routes/channels.php`
- Test: `tests/Feature/BroadcastChannelTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BroadcastChannelTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_subscribe_to_own_channel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->assertTrue(
                \Illuminate\Support\Facades\Broadcast::channel('users.' . $user->id)
            );
    }

    public function test_admin_can_subscribe_to_any_user_channel(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->assertTrue(
                \Illuminate\Support\Facades\Broadcast::channel('users.' . $user->id)
            );
    }

    public function test_non_admin_cannot_subscribe_to_other_user_channel(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1)
            ->assertFalse(
                \Illuminate\Support\Facades\Broadcast::channel('users.' . $user2->id)
            );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/BroadcastChannelTest.php`
Expected: FAIL with "Channel not defined"

- [ ] **Step 3: Add channel authorization**

Modify `routes/channels.php`:

```php
<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('users.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id || $user->is_admin === true;
});
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/BroadcastChannelTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add routes/channels.php tests/Feature/BroadcastChannelTest.php
git commit -m "feat: add user status Echo channel authorization"
```

---

## Task 11: Add Translations (PT, EN, ES)

**Files:**
- Modify: `lang/pt.json`
- Modify: `lang/en.json`
- Modify: `lang/es.json`

- [ ] **Step 1: Add Portuguese translations**

Add to `lang/pt.json` (maintain alphabetical order):

```json
"Ausente": "Ausente",
"Activity Timeline": "Linha do tempo de atividades",
"Changed from": "Alterado de",
"Credential created": "Credencial criada",
"Database created": "Database criado",
"En línea": "Online",
"Estado": "Status",
"Estado actual": "Status atual",
"Failed to update status": "Falha ao atualizar status",
"Must Authenticate": "Você precisa estar autenticado",
"No recent activity": "Sem atividade recente",
"Ocupado": "Ocupado",
"Offline": "Offline",
"Online": "Online",
"Set your status": "Definir seu status",
"Status": "Status",
"Status changed": "Status alterado",
"Status updated successfully": "Status atualizado com sucesso",
"Status not available": "Status não disponível",
"to": "para",
"Updates": "Atualizações",
"User is": "O usuário está",
"View activity": "Ver atividade",
"You are currently": "Você está atualmente",
"Your status is now": "Seu status agora é",
"current status": "status atual"
```

- [ ] **Step 2: Add English translations**

Add to `lang/en.json`:

```json
"Ausente": "Away",
"Activity Timeline": "Activity Timeline",
"Changed from": "Changed from",
"Credential created": "Credential created",
"Database created": "Database created",
"En línea": "Online",
"Estado": "Status",
"Estado actual": "Current status",
"Failed to update status": "Failed to update status",
"Must Authenticate": "You must be authenticated",
"No recent activity": "No recent activity",
"Ocupado": "Busy",
"Offline": "Offline",
"Online": "Online",
"Set your status": "Set your status",
"Status": "Status",
"Status changed": "Status changed",
"Status updated successfully": "Status updated successfully",
"Status not available": "Status not available",
"to": "to",
"Updates": "Updates",
"User is": "User is",
"View activity": "View activity",
"You are currently": "You are currently",
"Your status is now": "Your status is now",
"current status": "current status"
```

- [ ] **Step 3: Add Spanish translations**

Add to `lang/es.json`:

```json
"Ausente": "Ausente",
"Activity Timeline": "Línea de tiempo de actividad",
"Changed from": "Cambiado de",
"Credential created": "Credencial creada",
"Database created": "Base de datos creada",
"En línea": "En línea",
"Estado": "Estado",
"Estado actual": "Estado actual",
"Failed to update status": "Error al actualizar estado",
"Must Authenticate": "Debes estar autenticado",
"No recent activity": "Sin actividad reciente",
"Ocupado": "Ocupado",
"Offline": "Desconectado",
"Online": "En línea",
"Set your status": "Define tu estado",
"Status": "Estado",
"Status changed": "Estado alterado",
"Status updated successfully": "Estado actualizado correctamente",
"Status not available": "Estado no disponible",
"to": "a",
"Updates": "Actualizaciones",
"User is": "El usuario está",
"View activity": "Ver actividad",
"You are currently": "Actualmente estás",
"Your status is now": "Tu estado ahora es",
"current status": "estado actual"
```

- [ ] **Step 4: Run translation validation test**

Run: `php artisan test tests/Feature/Lang/TranslationKeysTest.php`
Expected: PASS (all keys exist in all languages)

- [ ] **Step 5: Commit**

```bash
git add lang/pt.json lang/en.json lang/es.json
git commit -m "feat: add translations for user status feature (PT, EN, ES)"
```

---

## Task 12: Create TypeScript Types

**Files:**
- Create: `resources/js/types/status.ts`

- [ ] **Step 1: Create TypeScript types**

Create `resources/js/types/status.ts`:

```typescript
export enum UserStatus {
  ONLINE = 'online',
  AWAY = 'away',
  BUSY = 'busy',
  OFFLINE = 'offline',
}

export interface UserStatusData {
  status: UserStatus;
  label: string;
  color: string;
}

export interface UserStatusUpdatedEvent {
  user_id: string;
  status: UserStatus;
  previous_status: UserStatus;
}

export interface UserActivity {
  id: string;
  user_id: string;
  activity_type: 'status_changed' | 'database_created' | 'credential_created';
  from_status: string | null;
  to_status: string | null;
  metadata: {
    database_name?: string;
    credential_name?: string;
    permission?: string;
  } | null;
  created_at: string;
}

export interface UserActivityLoggedEvent {
  id: string;
  user_id: string;
  activity_type: string;
  from_status: string | null;
  to_status: string | null;
  metadata: Record<string, unknown> | null;
  created_at: string;
}

export interface StatusMenuItem {
  value: UserStatus;
  label: string;
  color: string;
  icon: string;
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/types/status.ts
git commit -m "feat: add TypeScript types for user status system"
```

---

## Task 13: Create useUserStatus Composable

**Files:**
- Create: `resources/js/composables/useUserStatus.ts`

- [ ] **Step 1: Create composable**

Create `resources/js/composables/useUserStatus.ts`:

```typescript
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { UserStatus, type UserStatusData, type StatusMenuItem } from '@/types/status'
import { __ } from '@/composables/useLang'

const currentStatus = ref<UserStatusData | null>(null)
const isLoading = ref(false)

const statusMenuItems: StatusMenuItem[] = [
  {
    value: UserStatus.ONLINE,
    label: __('Online'),
    color: '#22c55e',
    icon: '●',
  },
  {
    value: UserStatus.AWAY,
    label: __('Ausente'),
    color: '#eab308',
    icon: '●',
  },
  {
    value: UserStatus.BUSY,
    label: __('Ocupado'),
    color: '#ef4444',
    icon: '●',
  },
  {
    value: UserStatus.OFFLINE,
    label: __('Offline'),
    color: '#6b7280',
    icon: '●',
  },
]

export function useUserStatus() {
  const fetchStatus = async (): Promise<void> => {
    try {
      isLoading.value = true
      const response = await axios.get<UserStatusData>('/api/user/status')
      currentStatus.value = response.data
    } catch (error) {
      console.error('Failed to fetch user status:', error)
    } finally {
      isLoading.value = false
    }
  }

  const setStatus = async (status: UserStatus): Promise<boolean> => {
    try {
      isLoading.value = true
      await axios.put('/api/user/status', { status })
      await fetchStatus()
      return true
    } catch (error) {
      console.error('Failed to set user status:', error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const getStatusColor = (status: UserStatus): string => {
    return statusMenuItems.find(item => item.value === status)?.color || '#6b7280'
  }

  const getStatusLabel = (status: UserStatus): string => {
    return statusMenuItems.find(item => item.value === status)?.label || status
  }

  const isOnline = computed(() => currentStatus.value?.status === UserStatus.ONLINE)
  const isAway = computed(() => currentStatus.value?.status === UserStatus.AWAY)
  const isBusy = computed(() => currentStatus.value?.status === UserStatus.BUSY)
  const isOffline = computed(() => currentStatus.value?.status === UserStatus.OFFLINE)

  return {
    currentStatus,
    isLoading,
    statusMenuItems,
    fetchStatus,
    setStatus,
    getStatusColor,
    getStatusLabel,
    isOnline,
    isAway,
    isBusy,
    isOffline,
  }
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/composables/useUserStatus.ts
git commit -m "feat: add useUserStatus composable for status management"
```

---

## Task 14: Create useEchoChannels Composable

**Files:**
- Create: `resources/js/composables/useEchoChannels.ts`

- [ ] **Step 1: Create composable**

Create `resources/js/composables/useEchoChannels.ts`:

```typescript
import { onMounted, onUnmounted } from 'vue'
import Echo from '@/composables/echo'
import type { UserStatusUpdatedEvent, UserActivityLoggedEvent } from '@/types/status'

export function useUserStatusChannel(userId: string, callbacks: {
  onStatusUpdated?: (event: UserStatusUpdatedEvent) => void
  onActivityLogged?: (event: UserActivityLoggedEvent) => void
}) {
  let channel: ReturnType<typeof Echo.private> | null = null

  const subscribe = () => {
    channel = Echo.private(`users.${userId}`)
      .listen('UserStatusUpdated', (event: UserStatusUpdatedEvent) => {
        callbacks.onStatusUpdated?.(event)
      })
      .listen('UserActivityLogged', (event: UserActivityLoggedEvent) => {
        callbacks.onActivityLogged?.(event)
      })
  }

  const unsubscribe = () => {
    if (channel) {
      channel.stopListening('UserStatusUpdated')
      channel.stopListening('UserActivityLogged')
    }
  }

  onMounted(() => {
    subscribe()
  })

  onUnmounted(() => {
    unsubscribe()
  })

  return {
    subscribe,
    unsubscribe,
  }
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/composables/useEchoChannels.ts
git commit -m "feat: add useEchoChannels composable for status listeners"
```

---

## Task 15: Create StatusPickerDropdown Component

**Files:**
- Create: `resources/js/components/StatusPickerDropdown.vue`

- [ ] **Step 1: Create component**

Create `resources/js/components/StatusPickerDropdown.vue`:

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useUserStatus } from '@/composables/useUserStatus'
import { useToast } from 'vue-toastification'
import { __ } from '@/composables/useLang'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const { currentStatus, isLoading, statusMenuItems, fetchStatus, setStatus } = useUserStatus()
const toast = useToast()

const isOpen = ref(false)

onMounted(() => {
  fetchStatus()
})

const handleSetStatus = async (status: string) => {
  const success = await setStatus(status as any)

  if (success) {
    toast.success(__('Status updated successfully'))
  } else {
    toast.error(__('Failed to update status'))
  }

  isOpen.value = false
}

const currentStatusColor = computed(() => {
  return currentStatus.value?.color || '#6b7280'
})

const currentStatusLabel = computed(() => {
  return currentStatus.value?.label || __('Offline')
})
</script>

<template>
  <DropdownMenu v-model:open="isOpen">
    <DropdownMenuTrigger class="flex items-center gap-2 px-3 py-2 rounded-md hover:bg-accent transition-colors">
      <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: currentStatusColor }" />
      <span class="text-sm font-medium">{{ currentStatusLabel }}</span>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" class="w-48">
      <DropdownMenuItem
        v-for="item in statusMenuItems"
        :key="item.value"
        @click="handleSetStatus(item.value)"
        :disabled="isLoading"
        class="flex items-center gap-2 cursor-pointer"
      >
        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: item.color }" />
        <span>{{ item.label }}</span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/StatusPickerDropdown.vue
git commit -m "feat: add StatusPickerDropdown component"
```

---

## Task 16: Create UserAvatarWithStatus Component

**Files:**
- Create: `resources/js/components/UserAvatarWithStatus.vue`

- [ ] **Step 1: Create component**

Create `resources/js/components/UserAvatarWithStatus.vue`:

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import type { UserStatus } from '@/types/status'
import { getStatusColor } from '@/composables/useUserStatus'

interface Props {
  src?: string | null
  name: string
  status?: UserStatus | null
  size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  status: null,
  size: 'md',
})

const initials = computed(() => {
  return props.name
    .split(' ')
    .map((n: string) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const statusColor = computed(() => {
  if (!props.status) return 'transparent'
  return getStatusColor(props.status)
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'h-8 w-8',
    md: 'h-10 w-10',
    lg: 'h-12 w-12',
  }
  return sizes[props.size]
})

const borderThickness = computed(() => {
  const thickness = {
    sm: '1px',
    md: '2px',
    lg: '2px',
  }
  return thickness[props.size]
})
</script>

<template>
  <div class="relative inline-block">
    <Avatar :class="sizeClasses" :style="{ border: `${borderThickness} solid ${statusColor}` }">
      <AvatarImage :src="src || undefined" :alt="name" />
      <AvatarFallback class="bg-primary text-primary-foreground">
        {{ initials }}
      </AvatarFallback>
    </Avatar>
  </div>
</template>
```

- [ ] **Step 2: Update useUserStatus to export getStatusColor**

Modify `resources/js/composables/useUserStatus.ts` - add export:

```typescript
export function getStatusColor(status: UserStatus): string {
  return statusMenuItems.find(item => item.value === status)?.color || '#6b7280'
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/UserAvatarWithStatus.vue resources/js/composables/useUserStatus.ts
git commit -m "feat: add UserAvatarWithStatus component with colored border"
```

---

## Task 17: Create UserActivityTimeline Component

**Files:**
- Create: `resources/js/components/UserActivityTimeline.vue`

- [ ] **Step 1: Create component**

Create `resources/js/components/UserActivityTimeline.vue`:

```vue
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { __ } from '@/composables/useLang'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Loader2 } from 'lucide-vue-next'
import type { UserActivity, PaginatedResponse } from '@/types/status'
import { UserStatus } from '@/types/status'

interface Props {
  userId: string
}

const props = defineProps<Props>()

const activities = ref<UserActivity[]>([])
const isLoading = ref(true)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})

const loadActivities = async (page: number = 1) => {
  try {
    isLoading.value = true
    const response = await axios.get<PaginatedResponse<UserActivity>>(
      `/api/user/${props.userId}/activities?page=${page}`
    )

    activities.value = response.data.data
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      per_page: response.data.per_page,
      total: response.data.total,
    }
  } catch (error) {
    console.error('Failed to load activities:', error)
  } finally {
    isLoading.value = false
  }
}

const getActivityIcon = (activity: UserActivity): string => {
  switch (activity.activity_type) {
    case 'status_changed':
      return '●'
    case 'database_created':
      return '🗄️'
    case 'credential_created':
      return '🔑'
    default:
      return '•'
  }
}

const getActivityColor = (activity: UserActivity): string => {
  if (activity.activity_type === 'status_changed' && activity.to_status) {
    switch (activity.to_status) {
      case UserStatus.ONLINE:
        return 'text-green-500'
      case UserStatus.AWAY:
        return 'text-yellow-500'
      case UserStatus.BUSY:
        return 'text-red-500'
      case UserStatus.OFFLINE:
        return 'text-gray-500'
    }
  }
  return 'text-muted-foreground'
}

const formatActivityText = (activity: UserActivity): string => {
  switch (activity.activity_type) {
    case 'status_changed':
      return __('Status changed: :from to :to', {
        from: activity.from_status?.toUpperCase() || 'OFFLINE',
        to: activity.to_status?.toUpperCase() || 'ONLINE',
      })
    case 'database_created':
      return __('Database created: :name', {
        name: activity.metadata?.database_name || 'Unknown',
      })
    case 'credential_created':
      return __('Credential created: :name', {
        name: activity.metadata?.credential_name || 'Unknown',
      })
    default:
      return __('Unknown activity')
  }
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return __('Just now')
  if (diffMins < 60) return __(':min minutes ago', { min: diffMins })
  if (diffHours < 24) return __(':hours hours ago', { hours: diffHours })
  if (diffDays === 1) return __('Yesterday')
  if (diffDays < 7) return __(':days days ago', { days: diffDays })

  return date.toLocaleDateString()
}

const hasActivities = computed(() => activities.value.length > 0)

onMounted(() => {
  loadActivities()
})
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ __('Activity Timeline') }}</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="isLoading" class="flex justify-center py-8">
        <Loader2 class="w-6 h-6 animate-spin text-muted-foreground" />
      </div>

      <div v-else-if="!hasActivities" class="text-center py-8 text-muted-foreground">
        {{ __('No recent activity') }}
      </div>

      <div v-else class="space-y-6">
        <div
          v-for="activity in activities"
          :key="activity.id"
          class="flex gap-4"
        >
          <div class="flex flex-col items-center">
            <span class="text-2xl" :class="getActivityColor(activity)">
              {{ getActivityIcon(activity) }}
            </span>
            <div class="w-px h-full bg-border min-h-[2rem]" />
          </div>

          <div class="flex-1 pb-6">
            <p class="text-sm font-medium">{{ formatActivityText(activity) }}</p>
            <p class="text-xs text-muted-foreground mt-1">
              {{ formatDate(activity.created_at) }}
            </p>

            <Badge
              v-if="activity.activity_type === 'status_changed' && activity.to_status"
              variant="outline"
              class="mt-2"
              :style="{ borderColor: getActivityColor(activity).replace('text-', '') }"
            >
              {{ activity.to_status.toUpperCase() }}
            </Badge>

            <div
              v-if="activity.metadata && activity.activity_type !== 'status_changed'"
              class="mt-2 p-2 rounded bg-muted/50 text-xs"
            >
              <span v-if="activity.metadata.permission" class="text-muted-foreground">
                {{ activity.metadata.permission }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination could be added here -->
    </CardContent>
  </Card>
</template>
```

- [ ] **Step 2: Update types to include PaginatedResponse**

Modify `resources/js/types/status.ts`:

```typescript
export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/UserActivityTimeline.vue resources/js/types/status.ts
git commit -m "feat: add UserActivityTimeline component"
```

---

## Task 18: Update Users/Index.vue with Status Avatar

**Files:**
- Modify: `resources/js/Pages/System/Users/Index.vue`

- [ ] **Step 1: Update component**

Modify `resources/js/Pages/System/Users/Index.vue`:

Add import:
```typescript
import UserAvatarWithStatus from '@/components/UserAvatarWithStatus.vue';
import { useUserStatus } from '@/composables/useUserStatus';
import { onMounted, ref } from 'vue';
```

Add status state:
```typescript
const userStatuses = ref<Record<string, string>>({})
const { fetchMultipleStatuses } = useUserStatus()

onMounted(async () => {
  // Fetch all users' statuses
  const userIds = (props.users ?? []).map(u => u.id)
  const statuses = await fetchMultipleStatuses(userIds)
  userStatuses.value = statuses
})
```

Update table to use avatar component:
```vue
<TableCell class="font-medium">
  <Link :href="route('system.users.show', user.id)" class="flex items-center gap-3 hover:underline">
    <UserAvatarWithStatus
      :src="user.avatar"
      :name="user.name"
      :status="userStatuses[user.id] as any"
      size="sm"
    />
    {{ user.name }}
  </Link>
</TableCell>
```

Add Echo listener for real-time updates:
```typescript
import Echo from '@/composables/echo'

onMounted(() => {
  // Listen for status updates
  Echo.channel('users')
    .listen('UserStatusUpdated', (event: any) => {
      userStatuses.value[event.user_id] = event.status
    })
})
```

- [ ] **Step 2: Update useUserStatus to support fetchMultipleStatuses**

Modify `resources/js/composables/useUserStatus.ts`:

```typescript
const fetchMultipleStatuses = async (userIds: string[]): Promise<Record<string, string>> => {
  try {
    const response = await axios.get<{ statuses: Record<string, string> }>('/api/statuses/batch', {
      params: { user_ids: userIds.join(',') }
    })
    return response.data.statuses
  } catch (error) {
    console.error('Failed to fetch user statuses:', error)
    return {}
  }
}
```

Add export:
```typescript
return {
  // ... existing exports
  fetchMultipleStatuses,
}
```

- [ ] **Step 3: Add batch status endpoint to backend**

Add to `app/Http/Controllers/UserStatusController.php`:

```php
public function getBatchStatuses(Request $request): JsonResponse
{
    $request->validate([
        'user_ids' => ['required', 'string'],
    ]);

    $userIds = explode(',', $request->input('user_ids'));
    $statuses = $this->statusService->getMultipleStatuses($userIds);

    return response()->json([
        'statuses' => $statuses,
    ]);
}
```

Add route to `routes/api.php`:

```php
Route::get('/statuses/batch', [UserStatusController::class, 'getBatchStatuses']);
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/System/Users/Index.vue resources/js/composables/useUserStatus.ts
git add app/Http/Controllers/UserStatusController.php routes/api.php
git commit -m "feat: add status avatar to users list with real-time updates"
```

---

## Task 19: Update Users/Show.vue with Updates Tab

**Files:**
- Modify: `resources/js/Pages/System/Users/Show.vue`

- [ ] **Step 1: Update component**

Modify `resources/js/Pages/System/Users/Show.vue`:

Add import:
```typescript
import UserActivityTimeline from '@/components/UserActivityTimeline.vue';
import { useEchoChannels } from '@/composables/useEchoChannels';
import { ref } from 'vue';
```

Add timeline refresh state:
```typescript
const timelineKey = ref(0)

const refreshTimeline = () => {
  timelineKey.value++
}
```

Add Echo listener:
```typescript
useUserStatusChannel(props.user.id, {
  onActivityLogged: (event) => {
    refreshTimeline()
  }
})
```

Update tabs array:
```typescript
const tabs = [
  { value: 'info', label: __('Information'), icon: 'User' },
  { value: 'updates', label: __('Updates'), icon: 'Activity' },
  { value: 'roles', label: __('Roles and Permissions'), icon: 'Shield' },
]
```

Add new tab content after Information tab:
```vue
<!-- Aba Atualizações -->
<PvTabsContent value="updates" :active-tab="activeTab">
  <UserActivityTimeline
    :key="timelineKey"
    :user-id="user.id"
  />
</PvTabsContent>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/System/Users/Show.vue
git commit -m "feat: add Updates tab to user profile with activity timeline"
```

---

## Task 20: Add StatusPicker to Sidebar

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.vue` (or sidebar component)

- [ ] **Step 1: Locate sidebar component**

Find the sidebar/settings button location in the authenticated layout.

- [ ] **Step 2: Add StatusPickerDropdown above settings**

Add import:
```vue
<script setup>
import StatusPickerDropdown from '@/components/StatusPickerDropdown.vue'
</script>
```

Add component above settings button:
```vue
<div class="flex items-center gap-2 px-3 py-2">
  <StatusPickerDropdown />
  <!-- Settings button here -->
</div>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.vue
git commit -m "feat: add status picker to sidebar"
```

---

## Summary

After completing all 20 tasks, the user status and presence system will be fully implemented with:

1. ✅ UserStatusEnum with labels and colors
2. ✅ UserActivity model and migration
3. ✅ UserStatusService (Redis operations)
4. ✅ UserActivityService (PostgreSQL operations)
5. ✅ Echo events for broadcasting
6. ✅ Listeners for logout, database, credential events
7. ✅ TrackUserStatus middleware (auto online + heartbeat)
8. ✅ UserStatusController (API endpoints)
9. ✅ UserActivityController (API endpoints)
10. ✅ Echo channel authorization
11. ✅ Full translations (PT, EN, ES)
12. ✅ TypeScript types
13. ✅ useUserStatus composable
14. ✅ useEchoChannels composable
15. ✅ StatusPickerDropdown component
16. ✅ UserAvatarWithStatus component
17. ✅ UserActivityTimeline component
18. ✅ Users list with status avatars
19. ✅ User profile with Updates tab
20. ✅ Sidebar status picker

**Total Tests:**
- Unit: ~40 tests
- Feature: ~20 tests
- Total: ~60 tests with 70-80% coverage

---

**Next Step:** Run full test suite and manual testing checklist.
