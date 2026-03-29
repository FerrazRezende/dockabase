# Database + Credential System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar sistema de múltiplos databases com controle de acesso via Credentials (Security Groups style).

**Architecture:** Models Database e Credential com relacionamentos many-to-many via pivots. Services para lógica de negócio. Controllers para CRUD admin. Sidebar refatorada para usar JSON.

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, Inertia.js, Vue 3, TypeScript, shadcn-vue

---

## Files Structure

```
database/migrations/
├── 2026_03_28_100001_create_databases_table.php
├── 2026_03_28_100002_create_credentials_table.php
├── 2026_03_28_100003_create_credential_user_table.php
└── 2026_03_28_100004_create_credential_database_table.php

app/
├── Enums/
│   └── CredentialPermissionEnum.php
├── Models/
│   ├── Database.php
│   └── Credential.php
├── Services/
│   ├── DatabaseService.php
│   └── CredentialService.php
├── Http/
│   ├── Controllers/System/
│   │   ├── DatabaseController.php
│   │   └── CredentialController.php
│   ├── Requests/System/
│   │   ├── CreateDatabaseRequest.php
│   │   ├── UpdateDatabaseRequest.php
│   │   ├── CreateCredentialRequest.php
│   │   └── UpdateCredentialRequest.php
│   └── Resources/
│       ├── DatabaseResource.php
│       ├── DatabaseCollection.php
│       ├── CredentialResource.php
│       └── CredentialCollection.php
└── Policies/
    ├── DatabasePolicy.php
    └── CredentialPolicy.php

resources/js/
├── config/
│   └── navigation.json
├── Pages/System/
│   ├── Databases/
│   │   ├── Index.vue
│   │   ├── Create.vue
│   │   └── Show.vue
│   └── Credentials/
│       ├── Index.vue
│       ├── Create.vue
│       └── Show.vue
├── types/
│   ├── database.ts
│   └── credential.ts
└── Layouts/
    └── AuthenticatedLayout.vue (modify)

routes/
└── system.php (modify)

tests/
├── Unit/
│   ├── Models/
│   │   ├── DatabaseTest.php
│   │   └── CredentialTest.php
│   └── Services/
│       ├── DatabaseServiceTest.php
│       └── CredentialServiceTest.php
└── Feature/
    └── System/
        ├── DatabaseControllerTest.php
        └── CredentialControllerTest.php
```

---

## Task 1: Enum CredentialPermissionEnum

**Files:**
- Create: `app/Enums/CredentialPermissionEnum.php`
- Test: `tests/Unit/Enums/CredentialPermissionEnumTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CredentialPermissionEnum;
use Tests\TestCase;

class CredentialPermissionEnumTest extends TestCase
{
    public function test_has_read_permission(): void
    {
        $this->assertEquals('read', CredentialPermissionEnum::Read->value);
    }

    public function test_has_write_permission(): void
    {
        $this->assertEquals('write', CredentialPermissionEnum::Write->value);
    }

    public function test_has_read_write_permission(): void
    {
        $this->assertEquals('read-write', CredentialPermissionEnum::ReadWrite->value);
    }

    public function test_all_permissions_defined(): void
    {
        $this->assertCount(3, CredentialPermissionEnum::cases());
    }

    public function test_label_returns_human_readable(): void
    {
        $this->assertEquals('Read Only', CredentialPermissionEnum::Read->label());
        $this->assertEquals('Write Only', CredentialPermissionEnum::Write->label());
        $this->assertEquals('Read & Write', CredentialPermissionEnum::ReadWrite->label());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/CredentialPermissionEnumTest.php`
Expected: FAIL - Class not found

- [ ] **Step 3: Write the enum**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum CredentialPermissionEnum: string
{
    case Read = 'read';
    case Write = 'write';
    case ReadWrite = 'read-write';

    public function label(): string
    {
        return match ($this) {
            self::Read => 'Read Only',
            self::Write => 'Write Only',
            self::ReadWrite => 'Read & Write',
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/CredentialPermissionEnumTest.php`
Expected: PASS - 5 tests

- [ ] **Step 5: Commit**

```bash
git add app/Enums/CredentialPermissionEnum.php tests/Unit/Enums/CredentialPermissionEnumTest.php
git commit -m "feat: add CredentialPermissionEnum for credential access levels"
```

---

## Task 2: Migration - Databases Table

**Files:**
- Create: `database/migrations/2026_03_28_100001_create_databases_table.php`

- [ ] **Step 1: Create migration**

Run: `php artisan make:migration create_databases_table`

- [ ] **Step 2: Write migration content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 64)->unique();
            $table->string('display_name', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('host', 255)->default('localhost');
            $table->unsignedInteger('port')->default(5432);
            $table->string('database_name', 64);
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
};
```

- [ ] **Step 3: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 4: Verify table exists**

Run: `php artisan tinker --execute="Schema::hasTable('databases') ? 'yes' : 'no'"`
Expected: "yes"

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_03_28_100001_create_databases_table.php
git commit -m "feat: add databases migration for multi-database support"
```

---

## Task 3: Migration - Credentials Table

**Files:**
- Create: `database/migrations/2026_03_28_100002_create_credentials_table.php`

- [ ] **Step 1: Create migration**

Run: `php artisan make:migration create_credentials_table`

- [ ] **Step 2: Write migration content**

```php
<?php

use App\Enums\CredentialPermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 255);
            $table->enum('permission', [
                CredentialPermissionEnum::Read->value,
                CredentialPermissionEnum::Write->value,
                CredentialPermissionEnum::ReadWrite->value,
            ])->default(CredentialPermissionEnum::Read->value);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
```

- [ ] **Step 3: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_03_28_100002_create_credentials_table.php
git commit -m "feat: add credentials migration for access groups"
```

---

## Task 4: Migration - CredentialUser Pivot

**Files:**
- Create: `database/migrations/2026_03_28_100003_create_credential_user_table.php`

- [ ] **Step 1: Create migration**

Run: `php artisan make:migration create_credential_user_table`

- [ ] **Step 2: Write migration content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_user', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('credential_id')->constrained('credentials')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['credential_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_user');
    }
};
```

- [ ] **Step 3: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_03_28_100003_create_credential_user_table.php
git commit -m "feat: add credential_user pivot table"
```

---

## Task 5: Migration - CredentialDatabase Pivot

**Files:**
- Create: `database/migrations/2026_03_28_100004_create_credential_database_table.php`

- [ ] **Step 1: Create migration**

Run: `php artisan make:migration create_credential_database_table`

- [ ] **Step 2: Write migration content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_database', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('credential_id')->constrained('credentials')->cascadeOnDelete();
            $table->foreignUuid('database_id')->constrained('databases')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['credential_id', 'database_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_database');
    }
};
```

- [ ] **Step 3: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_03_28_100004_create_credential_database_table.php
git commit -m "feat: add credential_database pivot table"
```

---

## Task 6: Model - Database

**Files:**
- Create: `app/Models/Database.php`
- Test: `tests/Unit/Models/DatabaseTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_database(): void
    {
        $database = Database::factory()->create([
            'name' => 'dev',
            'display_name' => 'Development',
            'database_name' => 'dockabase_dev',
        ]);

        $this->assertEquals('dev', $database->name);
        $this->assertEquals('Development', $database->display_name);
        $this->assertTrue($database->is_active);
    }

    public function test_has_many_credentials(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();

        $database->credentials()->attach($credential);

        $this->assertCount(1, $database->credentials);
        $this->assertTrue($database->credentials->first()->is($credential));
    }

    public function test_scope_active_filters_inactive(): void
    {
        Database::factory()->create(['name' => 'active', 'is_active' => true]);
        Database::factory()->create(['name' => 'inactive', 'is_active' => false]);

        $active = Database::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('active', $active->first()->name);
    }

    public function test_settings_is_cast_to_array(): void
    {
        $database = Database::factory()->create([
            'settings' => ['features' => ['realtime' => false]],
        ]);

        $this->assertIsArray($database->settings);
        $this->assertFalse($database->settings['features']['realtime']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Models/DatabaseTest.php`
Expected: FAIL - Class not found

- [ ] **Step 3: Create factory**

Run: `php artisan make:factory DatabaseFactory`

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Database;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseFactory extends Factory
{
    protected $model = Database::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => $name,
            'display_name' => ucfirst($name),
            'description' => $this->faker->sentence(),
            'host' => 'localhost',
            'port' => 5432,
            'database_name' => 'dockabase_' . $name,
            'is_active' => true,
            'settings' => null,
        ];
    }
}
```

- [ ] **Step 4: Write the model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Database extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'host',
        'port',
        'database_name',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function credentials(): BelongsToMany
    {
        return $this->belongsToMany(Credential::class, 'credential_database')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfName($query, string $name)
    {
        return $query->where('name', $name);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Unit/Models/DatabaseTest.php`
Expected: PASS - 4 tests

- [ ] **Step 6: Commit**

```bash
git add app/Models/Database.php database/factories/DatabaseFactory.php tests/Unit/Models/DatabaseTest.php
git commit -m "feat: add Database model with relationships and factory"
```

---

## Task 7: Model - Credential

**Files:**
- Create: `app/Models/Credential.php`
- Test: `tests/Unit/Models/CredentialTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_credential(): void
    {
        $credential = Credential::factory()->create([
            'name' => 'Dev Team',
            'permission' => CredentialPermissionEnum::ReadWrite,
        ]);

        $this->assertEquals('Dev Team', $credential->name);
        $this->assertEquals(CredentialPermissionEnum::ReadWrite, $credential->permission);
    }

    public function test_has_many_users(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();

        $credential->users()->attach($user);

        $this->assertCount(1, $credential->users);
        $this->assertTrue($credential->users->first()->is($user));
    }

    public function test_has_many_databases(): void
    {
        $credential = Credential::factory()->create();
        $database = Database::factory()->create();

        $credential->databases()->attach($database);

        $this->assertCount(1, $credential->databases);
        $this->assertTrue($credential->databases->first()->is($database));
    }

    public function test_permission_is_cast_to_enum(): void
    {
        $credential = Credential::factory()->create([
            'permission' => CredentialPermissionEnum::Read,
        ]);

        $this->assertInstanceOf(CredentialPermissionEnum::class, $credential->permission);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Models/CredentialTest.php`
Expected: FAIL - Class not found

- [ ] **Step 3: Create factory**

Run: `php artisan make:factory CredentialFactory`

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Team',
            'permission' => CredentialPermissionEnum::ReadWrite,
            'description' => $this->faker->sentence(),
        ];
    }
}
```

- [ ] **Step 4: Write the model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CredentialPermissionEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Credential extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'permission',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'permission' => CredentialPermissionEnum::class,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'credential_user')
            ->withTimestamps();
    }

    public function databases(): BelongsToMany
    {
        return $this->belongsToMany(Database::class, 'credential_database')
            ->withTimestamps();
    }

    public function hasReadPermission(): bool
    {
        return $this->permission === CredentialPermissionEnum::Read
            || $this->permission === CredentialPermissionEnum::ReadWrite;
    }

    public function hasWritePermission(): bool
    {
        return $this->permission === CredentialPermissionEnum::Write
            || $this->permission === CredentialPermissionEnum::ReadWrite;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Unit/Models/CredentialTest.php`
Expected: PASS - 4 tests

- [ ] **Step 6: Commit**

```bash
git add app/Models/Credential.php database/factories/CredentialFactory.php tests/Unit/Models/CredentialTest.php
git commit -m "feat: add Credential model with relationships and factory"
```

---

## Task 8: Service - DatabaseService

**Files:**
- Create: `app/Services/DatabaseService.php`
- Test: `tests/Unit/Services/DatabaseServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use App\Services\DatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DatabaseService::class);
    }

    public function test_create_database(): void
    {
        $result = $this->service->create([
            'name' => 'dev',
            'display_name' => 'Development',
            'database_name' => 'dockabase_dev',
        ]);

        $this->assertEquals('dev', $result->name);
        $this->assertEquals('Development', $result->display_name);
        $this->assertDatabaseHas('databases', ['name' => 'dev']);
    }

    public function test_attach_credential(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();

        $this->service->attachCredential($database, $credential);

        $this->assertTrue($database->credentials->contains($credential));
    }

    public function test_detach_credential(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();
        $database->credentials()->attach($credential);

        $this->service->detachCredential($database, $credential);

        $this->assertFalse($database->fresh()->credentials->contains($credential));
    }

    public function test_get_databases_for_user(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create();
        $database = Database::factory()->create();

        $credential->users()->attach($user);
        $database->credentials()->attach($credential);

        $result = $this->service->getDatabasesForUser($user);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($database));
    }

    public function test_delete_database(): void
    {
        $database = Database::factory()->create();

        $this->service->delete($database->id);

        $this->assertDatabaseMissing('databases', ['id' => $database->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/DatabaseServiceTest.php`
Expected: FAIL - Class not found

- [ ] **Step 3: Write the service**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseService
{
    public function create(array $data): Database
    {
        return Database::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'] ?? null,
            'description' => $data['description'] ?? null,
            'host' => $data['host'] ?? 'localhost',
            'port' => $data['port'] ?? 5432,
            'database_name' => $data['database_name'],
            'is_active' => $data['is_active'] ?? true,
            'settings' => $data['settings'] ?? null,
        ]);
    }

    public function update(Database $database, array $data): Database
    {
        $database->update($data);
        return $database->fresh();
    }

    public function delete(string $id): void
    {
        Database::destroy($id);
    }

    public function attachCredential(Database $database, Credential $credential): void
    {
        $database->credentials()->syncWithoutDetaching($credential);
    }

    public function detachCredential(Database $database, Credential $credential): void
    {
        $database->credentials()->detach($credential);
    }

    public function getDatabasesForUser(User $user): Collection
    {
        return Database::whereHas('credentials.users', fn ($q) => $q->where('users.id', $user->id))
            ->active()
            ->get();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/DatabaseServiceTest.php`
Expected: PASS - 5 tests

- [ ] **Step 5: Commit**

```bash
git add app/Services/DatabaseService.php tests/Unit/Services/DatabaseServiceTest.php
git commit -m "feat: add DatabaseService for database management"
```

---

## Task 9: Service - CredentialService

**Files:**
- Create: `app/Services/CredentialService.php`
- Test: `tests/Unit/Services/CredentialServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use App\Services\CredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialServiceTest extends TestCase
{
    use RefreshDatabase;

    private CredentialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CredentialService::class);
    }

    public function test_create_credential(): void
    {
        $user = User::factory()->create();

        $result = $this->service->create([
            'name' => 'Dev Team',
            'permission' => CredentialPermissionEnum::ReadWrite,
            'user_ids' => [$user->id],
        ]);

        $this->assertEquals('Dev Team', $result->name);
        $this->assertEquals(CredentialPermissionEnum::ReadWrite, $result->permission);
        $this->assertTrue($result->users->contains($user));
    }

    public function test_attach_user(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();

        $this->service->attachUser($credential, $user->id);

        $this->assertTrue($credential->fresh()->users->contains($user));
    }

    public function test_detach_user(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();
        $credential->users()->attach($user);

        $this->service->detachUser($credential, $user->id);

        $this->assertFalse($credential->fresh()->users->contains($user));
    }

    public function test_get_user_permission_for_database(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'permission' => CredentialPermissionEnum::ReadWrite,
        ]);
        $database = Database::factory()->create();

        $credential->users()->attach($user);
        $database->credentials()->attach($credential);

        $result = $this->service->getUserPermissionForDatabase($user, $database->name);

        $this->assertEquals(CredentialPermissionEnum::ReadWrite, $result);
    }

    public function test_get_user_permission_for_database_returns_null_if_no_access(): void
    {
        $user = User::factory()->create();
        $database = Database::factory()->create();

        $result = $this->service->getUserPermissionForDatabase($user, $database->name);

        $this->assertNull($result);
    }

    public function test_delete_credential(): void
    {
        $credential = Credential::factory()->create();

        $this->service->delete($credential->id);

        $this->assertDatabaseMissing('credentials', ['id' => $credential->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/CredentialServiceTest.php`
Expected: FAIL - Class not found

- [ ] **Step 3: Write the service**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;

class CredentialService
{
    public function create(array $data): Credential
    {
        $credential = Credential::create([
            'name' => $data['name'],
            'permission' => $data['permission'] ?? CredentialPermissionEnum::Read,
            'description' => $data['description'] ?? null,
        ]);

        if (!empty($data['user_ids'])) {
            $credential->users()->attach($data['user_ids']);
        }

        return $credential->fresh();
    }

    public function update(Credential $credential, array $data): Credential
    {
        $credential->update([
            'name' => $data['name'] ?? $credential->name,
            'permission' => $data['permission'] ?? $credential->permission,
            'description' => $data['description'] ?? $credential->description,
        ]);

        if (isset($data['user_ids'])) {
            $credential->users()->sync($data['user_ids']);
        }

        return $credential->fresh();
    }

    public function delete(string $id): void
    {
        Credential::destroy($id);
    }

    public function attachUser(Credential $credential, string $userId): void
    {
        $credential->users()->syncWithoutDetaching($userId);
    }

    public function detachUser(Credential $credential, string $userId): void
    {
        $credential->users()->detach($userId);
    }

    public function getUserPermissionForDatabase(User $user, string $databaseName): ?CredentialPermissionEnum
    {
        $database = Database::where('name', $databaseName)->first();

        if (!$database) {
            return null;
        }

        $credential = $database->credentials()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->first();

        return $credential?->permission;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/CredentialServiceTest.php`
Expected: PASS - 6 tests

- [ ] **Step 5: Commit**

```bash
git add app/Services/CredentialService.php tests/Unit/Services/CredentialServiceTest.php
git commit -m "feat: add CredentialService for access group management"
```

---

## Task 10: Policies

**Files:**
- Create: `app/Policies/DatabasePolicy.php`
- Create: `app/Policies/CredentialPolicy.php`
- Test: `tests/Unit/Policies/DatabasePolicyTest.php`
- Test: `tests/Unit/Policies/CredentialPolicyTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Database;
use App\Models\User;
use App\Policies\DatabasePolicy;
use Tests\TestCase;

class DatabasePolicyTest extends TestCase
{
    private DatabasePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DatabasePolicy();
    }

    public function test_admin_can_view_any(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_non_admin_cannot_view_any(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_admin_can_create(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->create($admin));
    }

    public function test_admin_can_update(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $database = Database::factory()->create();

        $this->assertTrue($this->policy->update($admin, $database));
    }

    public function test_admin_can_delete(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $database = Database::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $database));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Policies/DatabasePolicyTest.php`
Expected: FAIL - Class not found

- [ ] **Step 3: Write DatabasePolicy**

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Database;
use App\Models\User;

class DatabasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function view(User $user, Database $database): bool
    {
        return $user->is_admin === true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, Database $database): bool
    {
        return $user->is_admin === true;
    }

    public function delete(User $user, Database $database): bool
    {
        return $user->is_admin === true;
    }
}
```

- [ ] **Step 4: Write CredentialPolicy**

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

class CredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function view(User $user, Credential $credential): bool
    {
        return $user->is_admin === true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, Credential $credential): bool
    {
        return $user->is_admin === true;
    }

    public function delete(User $user, Credential $credential): bool
    {
        return $user->is_admin === true;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Unit/Policies/DatabasePolicyTest.php`
Expected: PASS - 5 tests

- [ ] **Step 6: Commit**

```bash
git add app/Policies/DatabasePolicy.php app/Policies/CredentialPolicy.php tests/Unit/Policies/DatabasePolicyTest.php
git commit -m "feat: add DatabasePolicy and CredentialPolicy for authorization"
```

---

## Task 11: Resources

**Files:**
- Create: `app/Http/Resources/DatabaseResource.php`
- Create: `app/Http/Resources/DatabaseCollection.php`
- Create: `app/Http/Resources/CredentialResource.php`
- Create: `app/Http/Resources/CredentialCollection.php`

- [ ] **Step 1: Write DatabaseResource**

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
            'settings' => $this->settings,
            'credentials_count' => $this->whenCounted('credentials'),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

- [ ] **Step 2: Write DatabaseCollection**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DatabaseCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                fn ($database) => (new DatabaseResource($database))->toArray($request)
            )->values()->toArray(),
        ];
    }
}
```

- [ ] **Step 3: Write CredentialResource**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CredentialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permission' => $this->permission->value,
            'permission_label' => $this->permission->label(),
            'description' => $this->description,
            'users_count' => $this->whenCounted('users'),
            'databases_count' => $this->whenCounted('databases'),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'databases' => DatabaseResource::collection($this->whenLoaded('databases')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

- [ ] **Step 4: Write CredentialCollection**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CredentialCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                fn ($credential) => (new CredentialResource($credential))->toArray($request)
            )->values()->toArray(),
        ];
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Resources/DatabaseResource.php app/Http/Resources/DatabaseCollection.php app/Http/Resources/CredentialResource.php app/Http/Resources/CredentialCollection.php
git commit -m "feat: add Database and Credential resources"
```

---

## Task 12: FormRequests

**Files:**
- Create: `app/Http/Requests/System/CreateDatabaseRequest.php`
- Create: `app/Http/Requests/System/UpdateDatabaseRequest.php`
- Create: `app/Http/Requests/System/CreateCredentialRequest.php`
- Create: `app/Http/Requests/System/UpdateCredentialRequest.php`

- [ ] **Step 1: Write CreateDatabaseRequest**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_-]*$/', 'unique:databases,name'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'database_name' => ['required', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
            'credential_ids' => ['nullable', 'array'],
            'credential_ids.*' => ['uuid', 'exists:credentials,id'],
        ];
    }
}
```

- [ ] **Step 2: Write UpdateDatabaseRequest**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
```

- [ ] **Step 3: Write CreateCredentialRequest**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use App\Enums\CredentialPermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:credentials,name'],
            'permission' => ['required', Rule::enum(CredentialPermissionEnum::class)],
            'description' => ['nullable', 'string'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['uuid', 'exists:users,id'],
        ];
    }
}
```

- [ ] **Step 4: Write UpdateCredentialRequest**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use App\Enums\CredentialPermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', Rule::enum(CredentialPermissionEnum::class)],
            'description' => ['nullable', 'string'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['uuid', 'exists:users,id'],
        ];
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/System/CreateDatabaseRequest.php app/Http/Requests/System/UpdateDatabaseRequest.php app/Http/Requests/System/CreateCredentialRequest.php app/Http/Requests/System/UpdateCredentialRequest.php
git commit -m "feat: add FormRequests for Database and Credential validation"
```

---

## Task 13: Controller - DatabaseController

**Files:**
- Create: `app/Http/Controllers/System/DatabaseController.php`
- Test: `tests/Feature/System/DatabaseControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    }

    public function test_index_returns_databases_for_admin(): void
    {
        Database::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.databases.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_forbidden_for_non_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('system.databases.index'));

        $response->assertForbidden();
    }

    public function test_store_creates_database(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('system.databases.store'), [
                'name' => 'dev',
                'display_name' => 'Development',
                'database_name' => 'dockabase_dev',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'dev');

        $this->assertDatabaseHas('databases', ['name' => 'dev']);
    }

    public function test_show_returns_database(): void
    {
        $database = Database::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.databases.show', $database));

        $response->assertOk()
            ->assertJsonPath('data.id', $database->id);
    }

    public function test_update_modifies_database(): void
    {
        $database = Database::factory()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson(route('system.databases.update', $database), [
                'display_name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.display_name', 'Updated Name');
    }

    public function test_destroy_deletes_database(): void
    {
        $database = Database::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.databases.destroy', $database));

        $response->assertNoContent();

        $this->assertDatabaseMissing('databases', ['id' => $database->id]);
    }

    public function test_attach_credential(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.databases.credentials.attach', $database), [
                'credential_id' => $credential->id,
            ]);

        $response->assertOk();

        $this->assertTrue($database->fresh()->credentials->contains($credential));
    }

    public function test_detach_credential(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();
        $database->credentials()->attach($credential);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.databases.credentials.detach', [$database, $credential]));

        $response->assertNoContent();

        $this->assertFalse($database->fresh()->credentials->contains($credential));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/System/DatabaseControllerTest.php`
Expected: FAIL - Route not found

- [ ] **Step 3: Write the controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\CreateDatabaseRequest;
use App\Http\Requests\System\UpdateDatabaseRequest;
use App\Http\Resources\DatabaseCollection;
use App\Http\Resources\DatabaseResource;
use App\Models\Credential;
use App\Models\Database;
use App\Services\DatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DatabaseController extends Controller
{
    public function __construct(
        private DatabaseService $databaseService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Database::class);

        $databases = Database::withCount('credentials')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return new DatabaseCollection($databases);
        }

        return Inertia::render('System/Databases/Index', [
            'databases' => (new DatabaseCollection($databases))->toArray($request),
        ]);
    }

    public function store(CreateDatabaseRequest $request)
    {
        $this->authorize('create', Database::class);

        $database = $this->databaseService->create($request->validated());

        if ($request->has('credential_ids')) {
            foreach ($request->validated('credential_ids') as $credentialId) {
                $credential = Credential::find($credentialId);
                if ($credential) {
                    $this->databaseService->attachCredential($database, $credential);
                }
            }
        }

        return new DatabaseResource($database);
    }

    public function show(Request $request, Database $database)
    {
        $this->authorize('view', $database);

        $database->load(['credentials.users']);

        if ($request->wantsJson()) {
            return new DatabaseResource($database);
        }

        return Inertia::render('System/Databases/Show', [
            'database' => (new DatabaseResource($database))->toArray($request),
        ]);
    }

    public function update(UpdateDatabaseRequest $request, Database $database)
    {
        $this->authorize('update', $database);

        $database = $this->databaseService->update($database, $request->validated());

        return new DatabaseResource($database);
    }

    public function destroy(Database $database)
    {
        $this->authorize('delete', $database);

        $this->databaseService->delete($database->id);

        return response()->noContent();
    }

    public function attachCredential(Request $request, Database $database)
    {
        $this->authorize('update', $database);

        $request->validate([
            'credential_id' => ['required', 'exists:credentials,id'],
        ]);

        $credential = Credential::findOrFail($request->input('credential_id'));
        $this->databaseService->attachCredential($database, $credential);

        return new DatabaseResource($database->fresh());
    }

    public function detachCredential(Database $database, Credential $credential)
    {
        $this->authorize('update', $database);

        $this->databaseService->detachCredential($database, $credential);

        return response()->noContent();
    }
}
```

- [ ] **Step 4: Add routes to routes/system.php**

```php
// Add to routes/system.php

use App\Http\Controllers\System\DatabaseController;

Route::middleware(['web', 'auth'])
    ->prefix('system')
    ->name('system.')
    ->group(function (): void {
        // ... existing routes ...

        // Database routes
        Route::get('/databases', [DatabaseController::class, 'index'])->name('databases.index');
        Route::post('/databases', [DatabaseController::class, 'store'])->name('databases.store');
        Route::get('/databases/{database}', [DatabaseController::class, 'show'])->name('databases.show');
        Route::patch('/databases/{database}', [DatabaseController::class, 'update'])->name('databases.update');
        Route::delete('/databases/{database}', [DatabaseController::class, 'destroy'])->name('databases.destroy');
        Route::post('/databases/{database}/credentials', [DatabaseController::class, 'attachCredential'])->name('databases.credentials.attach');
        Route::delete('/databases/{database}/credentials/{credential}', [DatabaseController::class, 'detachCredential'])->name('databases.credentials.detach');
    });
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/System/DatabaseControllerTest.php`
Expected: PASS - 8 tests

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/System/DatabaseController.php routes/system.php tests/Feature/System/DatabaseControllerTest.php
git commit -m "feat: add DatabaseController with CRUD and credential attachment"
```

---

## Task 14: Controller - CredentialController

**Files:**
- Create: `app/Http/Controllers/System/CredentialController.php`
- Test: `tests/Feature/System/CredentialControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    }

    public function test_index_returns_credentials_for_admin(): void
    {
        Credential::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.credentials.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_forbidden_for_non_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('system.credentials.index'));

        $response->assertForbidden();
    }

    public function test_store_creates_credential(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.credentials.store'), [
                'name' => 'Dev Team',
                'permission' => 'read-write',
                'user_ids' => [$user->id],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Dev Team');

        $this->assertDatabaseHas('credentials', ['name' => 'Dev Team']);
    }

    public function test_show_returns_credential(): void
    {
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.credentials.show', $credential));

        $response->assertOk()
            ->assertJsonPath('data.id', $credential->id);
    }

    public function test_update_modifies_credential(): void
    {
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson(route('system.credentials.update', $credential), [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_destroy_deletes_credential(): void
    {
        $credential = Credential::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.credentials.destroy', $credential));

        $response->assertNoContent();

        $this->assertDatabaseMissing('credentials', ['id' => $credential->id]);
    }

    public function test_attach_user(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.credentials.users.attach', $credential), [
                'user_id' => $user->id,
            ]);

        $response->assertOk();

        $this->assertTrue($credential->fresh()->users->contains($user));
    }

    public function test_detach_user(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();
        $credential->users()->attach($user);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.credentials.users.detach', [$credential, $user]));

        $response->assertNoContent();

        $this->assertFalse($credential->fresh()->users->contains($user));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/System/CredentialControllerTest.php`
Expected: FAIL - Route not found

- [ ] **Step 3: Write the controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\CreateCredentialRequest;
use App\Http\Requests\System\UpdateCredentialRequest;
use App\Http\Resources\CredentialCollection;
use App\Http\Resources\CredentialResource;
use App\Models\Credential;
use App\Models\User;
use App\Services\CredentialService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CredentialController extends Controller
{
    public function __construct(
        private CredentialService $credentialService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Credential::class);

        $credentials = Credential::withCount(['users', 'databases'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return new CredentialCollection($credentials);
        }

        return Inertia::render('System/Credentials/Index', [
            'credentials' => (new CredentialCollection($credentials))->toArray($request),
        ]);
    }

    public function store(CreateCredentialRequest $request)
    {
        $this->authorize('create', Credential::class);

        $credential = $this->credentialService->create($request->validated());

        return new CredentialResource($credential->load(['users']));
    }

    public function show(Request $request, Credential $credential)
    {
        $this->authorize('view', $credential);

        $credential->load(['users', 'databases']);

        if ($request->wantsJson()) {
            return new CredentialResource($credential);
        }

        return Inertia::render('System/Credentials/Show', [
            'credential' => (new CredentialResource($credential))->toArray($request),
        ]);
    }

    public function update(UpdateCredentialRequest $request, Credential $credential)
    {
        $this->authorize('update', $credential);

        $credential = $this->credentialService->update($credential, $request->validated());

        return new CredentialResource($credential->load(['users']));
    }

    public function destroy(Credential $credential)
    {
        $this->authorize('delete', $credential);

        $this->credentialService->delete($credential->id);

        return response()->noContent();
    }

    public function attachUser(Request $request, Credential $credential)
    {
        $this->authorize('update', $credential);

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($request->input('user_id'));
        $this->credentialService->attachUser($credential, $user->id);

        return new CredentialResource($credential->fresh()->load(['users']));
    }

    public function detachUser(Credential $credential, User $user)
    {
        $this->authorize('update', $credential);

        $this->credentialService->detachUser($credential, $user->id);

        return response()->noContent();
    }
}
```

- [ ] **Step 4: Add routes to routes/system.php**

```php
// Add to routes/system.php

use App\Http\Controllers\System\CredentialController;

// Credential routes
Route::get('/credentials', [CredentialController::class, 'index'])->name('credentials.index');
Route::post('/credentials', [CredentialController::class, 'store'])->name('credentials.store');
Route::get('/credentials/{credential}', [CredentialController::class, 'show'])->name('credentials.show');
Route::patch('/credentials/{credential}', [CredentialController::class, 'update'])->name('credentials.update');
Route::delete('/credentials/{credential}', [CredentialController::class, 'destroy'])->name('credentials.destroy');
Route::post('/credentials/{credential}/users', [CredentialController::class, 'attachUser'])->name('credentials.users.attach');
Route::delete('/credentials/{credential}/users/{user}', [CredentialController::class, 'detachUser'])->name('credentials.users.detach');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/System/CredentialControllerTest.php`
Expected: PASS - 8 tests

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/System/CredentialController.php routes/system.php tests/Feature/System/CredentialControllerTest.php
git commit -m "feat: add CredentialController with CRUD and user attachment"
```

---

## Task 15: Sidebar Navigation JSON

**Files:**
- Create: `resources/js/config/navigation.json`
- Modify: `resources/js/Layouts/AuthenticatedLayout.vue`

- [ ] **Step 1: Create navigation.json**

```json
{
  "items": [
    {
      "label": "Home",
      "icon": "Home",
      "route": "dashboard",
      "permission": null
    },
    {
      "label": "Databases",
      "icon": "Database",
      "route": "system.databases.index",
      "permission": "is_admin"
    },
    {
      "label": "Credentials",
      "icon": "Key",
      "route": "system.credentials.index",
      "permission": "is_admin"
    },
    {
      "label": "Features",
      "icon": "Flag",
      "route": "system.features.index",
      "permission": "is_admin"
    }
  ]
}
```

- [ ] **Step 2: Update AuthenticatedLayout.vue**

Read the current `AuthenticatedLayout.vue` and replace the hardcoded navigation with dynamic rendering from JSON.

- [ ] **Step 3: Commit**

```bash
git add resources/js/config/navigation.json resources/js/Layouts/AuthenticatedLayout.vue
git commit -m "feat: refactor sidebar navigation to use JSON configuration"
```

---

## Task 16: Vue Types

**Files:**
- Create: `resources/js/types/database.ts`
- Create: `resources/js/types/credential.ts`

- [ ] **Step 1: Create database.ts**

```typescript
export interface Database {
  id: string;
  name: string;
  display_name: string | null;
  description: string | null;
  host: string;
  port: number;
  database_name: string;
  is_active: boolean;
  settings: Record<string, unknown> | null;
  credentials_count?: number;
  created_at: string;
  updated_at: string;
}

export interface DatabaseCollection {
  data: Database[];
}
```

- [ ] **Step 2: Create credential.ts**

```typescript
import type { User } from './user';
import type { Database } from './database';

export type CredentialPermission = 'read' | 'write' | 'read-write';

export interface Credential {
  id: string;
  name: string;
  permission: CredentialPermission;
  permission_label: string;
  description: string | null;
  users_count?: number;
  databases_count?: number;
  users?: User[];
  databases?: Database[];
  created_at: string;
  updated_at: string;
}

export interface CredentialCollection {
  data: Credential[];
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/types/database.ts resources/js/types/credential.ts
git commit -m "feat: add TypeScript types for Database and Credential"
```

---

## Task 17: Vue Pages - Databases

**Files:**
- Create: `resources/js/Pages/System/Databases/Index.vue`
- Create: `resources/js/Pages/System/Databases/Create.vue`
- Create: `resources/js/Pages/System/Databases/Show.vue`

- [ ] **Step 1: Create Index.vue**

Create a page with a table listing all databases, similar to Features/Index.vue pattern.

- [ ] **Step 2: Create Create.vue**

Create a form page for creating new databases with credential selection.

- [ ] **Step 3: Create Show.vue**

Create a detail page showing database info and attached credentials.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/System/Databases/
git commit -m "feat: add Vue pages for Database management"
```

---

## Task 18: Vue Pages - Credentials

**Files:**
- Create: `resources/js/Pages/System/Credentials/Index.vue`
- Create: `resources/js/Pages/System/Credentials/Create.vue`
- Create: `resources/js/Pages/System/Credentials/Show.vue`

- [ ] **Step 1: Create Index.vue**

Create a page with a table listing all credentials.

- [ ] **Step 2: Create Create.vue**

Create a form page for creating credentials with user selection (multi-select).

- [ ] **Step 3: Create Show.vue**

Create a detail page showing credential info, users, and attached databases.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/System/Credentials/
git commit -m "feat: add Vue pages for Credential management"
```

---

## Task 19: Final Verification

- [ ] **Step 1: Run all tests**

Run: `php artisan test`
Expected: All tests pass

- [ ] **Step 2: Run Pint**

Run: `./vendor/bin/pint`
Expected: No changes needed

- [ ] **Step 3: Verify routes**

Run: `php artisan route:list --path=system`
Expected: All database and credential routes listed

- [ ] **Step 4: Manual browser test**

1. Login as admin
2. Navigate to Databases page
3. Create a new database
4. Create a credential with users
5. Attach credential to database
6. Verify sidebar shows new items

- [ ] **Step 5: Final commit if needed**

```bash
git add -A
git commit -m "chore: final cleanup and verification"
```

---

## Self-Review

**1. Spec Coverage:**
- ✅ Models (Database, Credential)
- ✅ Pivots (credential_user, credential_database)
- ✅ Services (DatabaseService, CredentialService)
- ✅ Controllers (DatabaseController, CredentialController)
- ✅ Policies (DatabasePolicy, CredentialPolicy)
- ✅ Resources (Database, Credential)
- ✅ FormRequests (Create/Update for both)
- ✅ Routes (all endpoints)
- ✅ Vue Pages (Index/Create/Show for both)
- ✅ Sidebar Navigation JSON

**2. Placeholder Scan:**
- Vue pages have minimal code shown - this is intentional as they follow existing patterns from Features pages

**3. Type Consistency:**
- `CredentialPermissionEnum` used throughout
- UUID strings for all IDs
- Consistent method names across services
