# Schema Builder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a visual schema browser, table creation wizard, and dynamic migration system for DockaBase databases.

**Architecture:** Three-tier backend (Introspection → Generator → Executor services) with Vue 3 frontend using Inertia.js. Schema metadata stored in system DB, queries run on tenant databases via dynamic connections.

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, Vue 3, TypeScript, shadcn-vue, Laravel Pennant

---

## File Structure

```
Backend:
├── database/migrations/
│   ├── 2026_04_16_000001_create_database_table_metadata_table.php
│   └── 2026_04_16_000002_create_system_migrations_table.php
├── app/Enums/
│   ├── PostgresTypeEnum.php
│   ├── ValidationPresetEnum.php
│   └── MigrationOperationEnum.php
├── app/Models/
│   ├── DatabaseTableMetadata.php
│   └── SystemMigration.php
├── app/DTOs/
│   └── MigrationDefinition.php
├── app/Services/
│   ├── SchemaIntrospectionService.php
│   ├── SchemaBuilderService.php
│   ├── MigrationGeneratorService.php
│   ├── MigrationExecutorService.php
│   └── ValidationRuleMapper.php
├── app/Http/Controllers/App/
│   └── SchemaBuilderController.php
├── app/Http/Controllers/System/
│   └── MigrationController.php
├── app/Http/Requests/SchemaBuilder/
│   ├── CreateTableRequest.php
│   └── TableDataRequest.php
├── app/Http/Requests/Migration/
│   └── CreateMigrationRequest.php
├── app/Http/Resources/App/
│   ├── SchemaResource.php
│   ├── TableDataResource.php
│   └── ColumnResource.php
├── app/Http/Resources/System/
│   └── MigrationResource.php
├── app/Policies/
│   └── DatabasePolicy.php (extend)
└── routes/
    ├── web.php (add routes)

Frontend:
├── resources/js/types/
│   └── schema.ts
├── resources/js/composables/
│   └── useSchemaBrowser.ts
├── resources/js/components/schema/
│   ├── SchemaBrowser.vue
│   ├── SchemaFolder.vue
│   ├── TableTreeItem.vue
│   ├── ColumnBadge.vue
│   ├── DataView.vue
│   ├── CreateTableWizard.vue
│   ├── StepColumns.vue
│   ├── StepValidations.vue
│   ├── ColumnEditor.vue
│   └── ValidationPresets.vue
└── resources/js/Pages/App/Databases/
    └── Show.vue (modify)

Tests:
├── tests/Unit/Enums/
│   ├── PostgresTypeEnumTest.php
│   ├── ValidationPresetEnumTest.php
│   └── MigrationOperationEnumTest.php
├── tests/Unit/Services/
│   ├── SchemaBuilderServiceTest.php
│   ├── MigrationGeneratorServiceTest.php
│   └── ValidationRuleMapperTest.php
├── tests/Unit/Models/
│   ├── DatabaseTableMetadataTest.php
│   └── SystemMigrationTest.php
└── tests/Feature/
    ├── SchemaBuilderControllerTest.php
    └── MigrationControllerTest.php
```

---

## Track 1: Database Migrations (Independent)

### Task 1.1: Create `database_table_metadata` Migration

**Files:**
- Create: `database/migrations/2026_04_16_000001_create_database_table_metadata_table.php`

- [ ] **Step 1: Create migration file**

```bash
php artisan make:migration create_database_table_metadata_table
```

- [ ] **Step 2: Write migration up/down**

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
        Schema::create('database_table_metadata', function (Blueprint $table) {
            $table->char('id', 27)->primary();
            $table->char('database_id', 27);
            $table->string('schema_name', 63)->default('public');
            $table->string('table_name', 63);
            $table->json('columns');
            $table->json('validations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('database_id')->references('id')->on('databases')->onDelete('cascade');
            $table->unique(['database_id', 'schema_name', 'table_name']);
        });

        Schema::table('database_table_metadata', function (Blueprint $table) {
            $table->index('database_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_table_metadata');
    }
};
```

- [ ] **Step 3: Run migration**

```bash
php artisan migrate
```

Expected: Table created successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_16_000001_create_database_table_metadata_table.php
git commit -m "feat(schema-builder): create database_table_metadata table"
```

---

### Task 1.2: Create `system_migrations` Migration

**Files:**
- Create: `database/migrations/2026_04_16_000002_create_system_migrations_table.php`

- [ ] **Step 1: Create migration file**

```bash
php artisan make:migration create_system_migrations_table
```

- [ ] **Step 2: Write migration up/down**

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
        Schema::create('system_migrations', function (Blueprint $table) {
            $table->char('id', 27)->primary();
            $table->char('database_id', 27);
            $table->integer('batch');
            $table->string('name');
            $table->string('operation', 50);
            $table->string('table_name', 63);
            $table->string('schema_name', 63)->default('public');
            $table->text('sql_up');
            $table->text('sql_down');
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->foreign('database_id')->references('id')->on('databases')->onDelete('cascade');
            $table->unique(['database_id', 'name']);
        });

        Schema::table('system_migrations', function (Blueprint $table) {
            $table->index('database_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_migrations');
    }
};
```

- [ ] **Step 3: Run migration**

```bash
php artisan migrate
```

Expected: Table created successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_16_000002_create_system_migrations_table.php
git commit -m "feat(schema-builder): create system_migrations table"
```

---

## Track 2: Enums (Independent, can run parallel with Track 1)

### Task 2.1: Create `PostgresTypeEnum` with Test

**Files:**
- Create: `app/Enums/PostgresTypeEnum.php`
- Create: `tests/Unit/Enums/PostgresTypeEnumTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PostgresTypeEnum;
use Tests\TestCase;

class PostgresTypeEnumTest extends TestCase
{
    public function test_has_all_types(): void
    {
        $cases = PostgresTypeEnum::cases();
        $this->assertCount(17, $cases);
    }

    public function test_label_returns_human_readable_string(): void
    {
        $this->assertEquals('Integer', PostgresTypeEnum::INTEGER->label());
        $this->assertEquals('Varchar', PostgresTypeEnum::VARCHAR->label());
        $this->assertEquals('JSONB', PostgresTypeEnum::JSONB->label());
    }

    public function test_category_returns_correct_category(): void
    {
        $this->assertEquals('numeric', PostgresTypeEnum::INTEGER->category());
        $this->assertEquals('text', PostgresTypeEnum::VARCHAR->category());
        $this->assertEquals('json', PostgresTypeEnum::JSONB->category());
        $this->assertEquals('array', PostgresTypeEnum::TEXT_ARRAY->category());
    }

    public function test_has_length_returns_true_for_varchar_and_char(): void
    {
        $this->assertTrue(PostgresTypeEnum::VARCHAR->hasLength());
        $this->assertTrue(PostgresTypeEnum::CHAR->hasLength());
        $this->assertFalse(PostgresTypeEnum::INTEGER->hasLength());
        $this->assertFalse(PostgresTypeEnum::TEXT->hasLength());
    }

    public function test_to_sql_definition_returns_correct_sql(): void
    {
        $this->assertEquals('integer', PostgresTypeEnum::INTEGER->toSqlDefinition());
        $this->assertEquals('varchar(255)', PostgresTypeEnum::VARCHAR->toSqlDefinition(255));
        $this->assertEquals('text', PostgresTypeEnum::TEXT->toSqlDefinition());
        $this->assertEquals('uuid', PostgresTypeEnum::UUID->toSqlDefinition());
        $this->assertEquals('jsonb', PostgresTypeEnum::JSONB->toSqlDefinition());
        $this->assertEquals('text[]', PostgresTypeEnum::TEXT_ARRAY->toSqlDefinition());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Enums/PostgresTypeEnumTest.php
```

Expected: FAIL with "Class PostgresTypeEnum not found"

- [ ] **Step 3: Write enum implementation**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum PostgresTypeEnum: string
{
    // Numeric
    case INTEGER = 'integer';
    case BIGINT = 'bigint';
    case DECIMAL = 'decimal';
    case REAL = 'real';

    // Text
    case VARCHAR = 'varchar';
    case TEXT = 'text';
    case CHAR = 'char';

    // Boolean
    case BOOLEAN = 'boolean';

    // Datetime
    case TIMESTAMP = 'timestamp';
    case DATE = 'date';
    case TIME = 'time';

    // UUID
    case UUID = 'uuid';

    // JSON
    case JSONB = 'jsonb';
    case JSON = 'json';

    // Array
    case TEXT_ARRAY = 'text_array';
    case INTEGER_ARRAY = 'integer_array';
    case UUID_ARRAY = 'uuid_array';

    // Network
    case INET = 'inet';
    case CIDR = 'cidr';

    public function label(): string
    {
        return match ($this) {
            self::INTEGER => 'Integer',
            self::BIGINT => 'Bigint',
            self::DECIMAL => 'Decimal',
            self::REAL => 'Real',
            self::VARCHAR => 'Varchar',
            self::TEXT => 'Text',
            self::CHAR => 'Char',
            self::BOOLEAN => 'Boolean',
            self::TIMESTAMP => 'Timestamp',
            self::DATE => 'Date',
            self::TIME => 'Time',
            self::UUID => 'UUID',
            self::JSONB => 'JSONB',
            self::JSON => 'JSON',
            self::TEXT_ARRAY => 'Text Array',
            self::INTEGER_ARRAY => 'Integer Array',
            self::UUID_ARRAY => 'UUID Array',
            self::INET => 'INET',
            self::CIDR => 'CIDR',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::INTEGER, self::BIGINT, self::DECIMAL, self::REAL => 'numeric',
            self::VARCHAR, self::TEXT, self::CHAR => 'text',
            self::BOOLEAN => 'boolean',
            self::TIMESTAMP, self::DATE, self::TIME => 'datetime',
            self::UUID => 'uuid',
            self::JSONB, self::JSON => 'json',
            self::TEXT_ARRAY, self::INTEGER_ARRAY, self::UUID_ARRAY => 'array',
            self::INET, self::CIDR => 'network',
        };
    }

    public function hasLength(): bool
    {
        return in_array($this, [self::VARCHAR, self::CHAR], true);
    }

    public function toSqlDefinition(?int $length = null): string
    {
        return match ($this) {
            self::VARCHAR => $length ? "varchar({$length})" : 'varchar(255)',
            self::CHAR => $length ? "char({$length})" : 'char(1)',
            self::TEXT_ARRAY => 'text[]',
            self::INTEGER_ARRAY => 'integer[]',
            self::UUID_ARRAY => 'uuid[]',
            default => $this->value,
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Unit/Enums/PostgresTypeEnumTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Enums/PostgresTypeEnum.php tests/Unit/Enums/PostgresTypeEnumTest.php
git commit -m "feat(schema-builder): add PostgresTypeEnum with tests"
```

---

### Task 2.2: Create `ValidationPresetEnum` with Test

**Files:**
- Create: `app/Enums/ValidationPresetEnum.php`
- Create: `tests/Unit/Enums/ValidationPresetEnumTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\{PostgresTypeEnum, ValidationPresetEnum};
use Tests\TestCase;

class ValidationPresetEnumTest extends TestCase
{
    public function test_to_laravel_rule_returns_correct_rule(): void
    {
        $this->assertEquals('required', ValidationPresetEnum::REQUIRED->toLaravelRule());
        $this->assertEquals('min:3', ValidationPresetEnum::MIN_LENGTH->toLaravelRule(3));
        $this->assertEquals('max:255', ValidationPresetEnum::MAX_LENGTH->toLaravelRule(255));
        $this->assertEquals('min:0', ValidationPresetEnum::MIN_VALUE->toLaravelRule(0));
        $this->assertEquals('email', ValidationPresetEnum::EMAIL->toLaravelRule());
        $this->assertEquals('regex:/^[a-Z]+$/', ValidationPresetEnum::REGEX->toLaravelRule('/^[a-Z]+$/'));
    }

    public function test_applicable_types_returns_correct_types(): void
    {
        $stringTypes = ValidationPresetEnum::REQUIRED->applicableTypes();
        $this->assertContains(PostgresTypeEnum::VARCHAR, $stringTypes);
        $this->assertContains(PostgresTypeEnum::INTEGER, $stringTypes);

        $minLengthTypes = ValidationPresetEnum::MIN_LENGTH->applicableTypes();
        $this->assertContains(PostgresTypeEnum::VARCHAR, $minLengthTypes);
        $this->assertContains(PostgresTypeEnum::TEXT, $minLengthTypes);
        $this->assertNotContains(PostgresTypeEnum::INTEGER, $minLengthTypes);
    }

    public function test_all_presets_have_labels(): void
    {
        foreach (ValidationPresetEnum::cases() as $case) {
            $this->assertIsString($case->label());
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Enums/ValidationPresetEnumTest.php
```

Expected: FAIL with "Class ValidationPresetEnum not found"

- [ ] **Step 3: Write enum implementation**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\PostgresTypeEnum;

enum ValidationPresetEnum: string
{
    case REQUIRED = 'required';
    case MIN_LENGTH = 'min_length';
    case MAX_LENGTH = 'max_length';
    case MIN_VALUE = 'min_value';
    case MAX_VALUE = 'max_value';
    case INTEGER = 'integer';
    case NUMERIC = 'numeric';
    case REGEX = 'regex';
    case UNIQUE = 'unique';
    case EXISTS = 'exists';
    case EMAIL = 'email';
    case URL = 'url';
    case UUID = 'uuid';
    case DATE = 'date';
    case BOOLEAN = 'boolean';
    case IN_LIST = 'in_list';
    case ALPHA = 'alpha';
    case ALPHA_NUM = 'alpha_num';
    case ALPHA_DASH = 'alpha_dash';

    public function label(): string
    {
        return match ($this) {
            self::REQUIRED => 'Required',
            self::MIN_LENGTH => 'Min Length',
            self::MAX_LENGTH => 'Max Length',
            self::MIN_VALUE => 'Min Value',
            self::MAX_VALUE => 'Max Value',
            self::INTEGER => 'Integer',
            self::NUMERIC => 'Numeric',
            self::REGEX => 'Regex Pattern',
            self::UNIQUE => 'Unique in Table',
            self::EXISTS => 'Exists in Table',
            self::EMAIL => 'Must be Email',
            self::URL => 'Must be URL',
            self::UUID => 'Must be UUID',
            self::DATE => 'Must be Date',
            self::BOOLEAN => 'Must be Boolean',
            self::IN_LIST => 'In List',
            self::ALPHA => 'Only Letters',
            self::ALPHA_NUM => 'Letters + Numbers',
            self::ALPHA_DASH => 'Letters, Numbers, Dash',
        };
    }

    public function toLaravelRule(mixed $value = null): string
    {
        return match ($this) {
            self::REQUIRED => 'required',
            self::MIN_LENGTH => $value !== null ? "min:{$value}" : 'min',
            self::MAX_LENGTH => $value !== null ? "max:{$value}" : 'max',
            self::MIN_VALUE => $value !== null ? "min:{$value}" : 'min',
            self::MAX_VALUE => $value !== null ? "max:{$value}" : 'max',
            self::INTEGER => 'integer',
            self::NUMERIC => 'numeric',
            self::REGEX => $value !== null ? "regex:{$value}" : 'regex',
            self::UNIQUE => $value !== null ? "unique:{$value}" : 'unique',
            self::EXISTS => $value !== null ? "exists:{$value}" : 'exists',
            self::EMAIL => 'email',
            self::URL => 'url',
            self::UUID => 'uuid',
            self::DATE => 'date',
            self::BOOLEAN => 'boolean',
            self::IN_LIST => $value !== null ? "in:{$value}" : 'in',
            self::ALPHA => 'alpha',
            self::ALPHA_NUM => 'alpha_num',
            self::ALPHA_DASH => 'alpha_dash',
        };
    }

    public function applicableTypes(): array
    {
        return match ($this) {
            self::REQUIRED, self::NUMERIC, self::UNIQUE, self::EXISTS, self::UUID, self::BOOLEAN => PostgresTypeEnum::cases(),
            self::MIN_LENGTH, self::MAX_LENGTH, self::REGEX, self::ALPHA, self::ALPHA_NUM, self::ALPHA_DASH, self::EMAIL, self::URL => [
                PostgresTypeEnum::VARCHAR, PostgresTypeEnum::TEXT, PostgresTypeEnum::CHAR,
            ],
            self::MIN_VALUE, self::MAX_VALUE, self::INTEGER => [
                PostgresTypeEnum::INTEGER, PostgresTypeEnum::BIGINT, PostgresTypeEnum::DECIMAL, PostgresTypeEnum::REAL,
            ],
            self::DATE => [
                PostgresTypeEnum::TIMESTAMP, PostgresTypeEnum::DATE, PostgresTypeEnum::TIME,
            ],
            self::IN_LIST => [
                PostgresTypeEnum::VARCHAR, PostgresTypeEnum::TEXT, PostgresTypeEnum::CHAR,
            ],
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Unit/Enums/ValidationPresetEnumTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Enums/ValidationPresetEnum.php tests/Unit/Enums/ValidationPresetEnumTest.php
git commit -m "feat(schema-builder): add ValidationPresetEnum with tests"
```

---

### Task 2.3: Create `MigrationOperationEnum` with Test

**Files:**
- Create: `app/Enums/MigrationOperationEnum.php`
- Create: `tests/Unit/Enums/MigrationOperationEnumTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\MigrationOperationEnum;
use Tests\TestCase;

class MigrationOperationEnumTest extends TestCase
{
    public function test_destructive_operations_correctly_identified(): void
    {
        $this->assertTrue(MigrationOperationEnum::DROP_TABLE->isDestructive());
        $this->assertTrue(MigrationOperationEnum::DROP_COLUMN->isDestructive());
        $this->assertFalse(MigrationOperationEnum::ADD_COLUMN->isDestructive());
        $this->assertFalse(MigrationOperationEnum::ADD_INDEX->isDestructive());
    }

    public function test_label_returns_human_readable(): void
    {
        $this->assertEquals('Add Column', MigrationOperationEnum::ADD_COLUMN->label());
        $this->assertEquals('Drop Table', MigrationOperationEnum::DROP_TABLE->label());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Enums/MigrationOperationEnumTest.php
```

Expected: FAIL with "Class MigrationOperationEnum not found"

- [ ] **Step 3: Write enum implementation**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum MigrationOperationEnum: string
{
    case ADD_COLUMN = 'add_column';
    case DROP_COLUMN = 'drop_column';
    case ALTER_COLUMN_TYPE = 'alter_column_type';
    case RENAME_COLUMN = 'rename_column';
    case ADD_CONSTRAINT = 'add_constraint';
    case DROP_CONSTRAINT = 'drop_constraint';
    case ADD_INDEX = 'add_index';
    case DROP_INDEX = 'drop_index';
    case RENAME_TABLE = 'rename_table';
    case DROP_TABLE = 'drop_table';

    public function isDestructive(): bool
    {
        return in_array($this, [self::DROP_COLUMN, self::DROP_TABLE, self::DROP_CONSTRAINT, self::DROP_INDEX], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::ADD_COLUMN => 'Add Column',
            self::DROP_COLUMN => 'Drop Column',
            self::ALTER_COLUMN_TYPE => 'Alter Column Type',
            self::RENAME_COLUMN => 'Rename Column',
            self::ADD_CONSTRAINT => 'Add Constraint',
            self::DROP_CONSTRAINT => 'Drop Constraint',
            self::ADD_INDEX => 'Add Index',
            self::DROP_INDEX => 'Drop Index',
            self::RENAME_TABLE => 'Rename Table',
            self::DROP_TABLE => 'Drop Table',
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Unit/Enums/MigrationOperationEnumTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Enums/MigrationOperationEnum.php tests/Unit/Enums/MigrationOperationEnumTest.php
git commit -m "feat(schema-builder): add MigrationOperationEnum with tests"
```

---

## Track 3: Models (Depends on Track 1)

### Task 3.1: Create `DatabaseTableMetadata` Model with Test

**Files:**
- Create: `app/Models/DatabaseTableMetadata.php`
- Create: `tests/Unit/Models/DatabaseTableMetadataTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\{Database, DatabaseTableMetadata};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTableMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_has_ksuid_trait(): void
    {
        $metadata = DatabaseTableMetadata::factory()->create();
        $this->assertEquals(27, strlen($metadata->id));
    }

    public function test_of_database_scope_filters_correctly(): void
    {
        $database1 = Database::factory()->create();
        $database2 = Database::factory()->create();

        DatabaseTableMetadata::factory()->for($database1)->create(['table_name' => 'users']);
        DatabaseTableMetadata::factory()->for($database2)->create(['table_name' => 'products']);

        $results = DatabaseTableMetadata::ofDatabase($database1->id)->get();
        $this->assertCount(1, $results);
        $this->assertEquals('users', $results->first()->table_name);
    }

    public function test_of_schema_scope_filters_correctly(): void
    {
        $database = Database::factory()->create();

        DatabaseTableMetadata::factory()->for($database)->create(['schema_name' => 'public', 'table_name' => 'users']);
        DatabaseTableMetadata::factory()->for($database)->create(['schema_name' => 'analytics', 'table_name' => 'events']);

        $results = DatabaseTableMetadata::ofSchema('public')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('users', $results->first()->table_name);
    }

    public function test_columns_and_validations_are_cast_to_array(): void
    {
        $database = Database::factory()->create();
        $metadata = DatabaseTableMetadata::factory()->for($database)->create([
            'columns' => [['name' => 'id', 'type' => 'uuid']],
            'validations' => ['id' => ['required' => true]],
        ]);

        $this->assertIsArray($metadata->columns);
        $this->assertIsArray($metadata->validations);
        $this->assertEquals('id', $metadata->columns[0]['name']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Models/DatabaseTableMetadataTest.php
```

Expected: FAIL with "Class DatabaseTableMetadata not found"

- [ ] **Step 3: Create factory**

```bash
php artisan make:factory DatabaseTableMetadataFactory --model=DatabaseTableMetadata
```

- [ ] **Step 4: Write factory definition**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Database;
use App\Models\DatabaseTableMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseTableMetadataFactory extends Factory
{
    protected $model = DatabaseTableMetadata::class;

    public function definition(): array
    {
        return [
            'database_id' => Database::factory(),
            'schema_name' => 'public',
            'table_name' => fake()->unique()->word(),
            'columns' => [
                ['name' => 'id', 'type' => 'uuid', 'nullable' => false],
                ['name' => 'name', 'type' => 'varchar', 'nullable' => false],
            ],
            'validations' => [
                'name' => ['required' => true, 'max' => 255],
            ],
        ];
    }
}
```

- [ ] **Step 5: Write model implementation**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DatabaseTableMetadata extends Model
{
    use HasFactory, HasKsuid, SoftDeletes;

    protected $fillable = [
        'database_id',
        'schema_name',
        'table_name',
        'columns',
        'validations',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'validations' => 'array',
        ];
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }

    public function scopeOfDatabase($query, string $databaseId)
    {
        return $query->where('database_id', $databaseId);
    }

    public function scopeOfSchema($query, string $schema)
    {
        return $query->where('schema_name', $schema);
    }

    public function scopeOfTable($query, string $table)
    {
        return $query->where('table_name', $table);
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

```bash
php artisan test tests/Unit/Models/DatabaseTableMetadataTest.php
```

Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Models/DatabaseTableMetadata.php database/factories/DatabaseTableMetadataFactory.php tests/Unit/Models/DatabaseTableMetadataTest.php
git commit -m "feat(schema-builder): add DatabaseTableMetadata model with tests"
```

---

### Task 3.2: Create `SystemMigration` Model with Test

**Files:**
- Create: `app/Models/SystemMigration.php`
- Create: `tests/Unit/Models/SystemMigrationTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\{Database, SystemMigration};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_executed_updates_status_and_executed_at(): void
    {
        $migration = SystemMigration::factory()->create(['status' => 'pending']);

        $migration->markExecuted();

        $this->assertEquals('executed', $migration->status);
        $this->assertNotNull($migration->executed_at);
    }

    public function test_mark_failed_updates_status_and_error_message(): void
    {
        $migration = SystemMigration::factory()->create(['status' => 'pending']);

        $migration->markFailed('Connection lost');

        $this->assertEquals('failed', $migration->status);
        $this->assertEquals('Connection lost', $migration->error_message);
    }

    public function test_mark_rolled_back_updates_status(): void
    {
        $migration = SystemMigration::factory()->create(['status' => 'executed']);

        $migration->markRolledBack();

        $this->assertEquals('rolled_back', $migration->status);
    }

    public function test_of_status_scope_filters_correctly(): void
    {
        SystemMigration::factory()->create(['status' => 'pending']);
        SystemMigration::factory()->create(['status' => 'executed']);

        $results = SystemMigration::ofStatus('executed')->get();
        $this->assertCount(1, $results);
    }

    public function test_executed_at_is_cast_to_datetime(): void
    {
        $migration = SystemMigration::factory()->create([
            'status' => 'executed',
            'executed_at' => '2024-01-01 12:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $migration->executed_at);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Models/SystemMigrationTest.php
```

Expected: FAIL with "Class SystemMigration not found"

- [ ] **Step 3: Create factory**

```bash
php artisan make:factory SystemMigrationFactory --model=SystemMigration
```

- [ ] **Step 4: Write factory definition**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Database;
use App\Models\SystemMigration;
use App\Enums\MigrationOperationEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemMigrationFactory extends Factory
{
    protected $model = SystemMigration::class;

    public function definition(): array
    {
        $operation = fake()->randomElement(MigrationOperationEnum::cases());

        return [
            'database_id' => Database::factory(),
            'batch' => fake()->numberBetween(1, 100),
            'name' => fake()->sentence(3),
            'operation' => $operation->value,
            'table_name' => fake()->word(),
            'schema_name' => 'public',
            'sql_up' => '-- SQL up',
            'sql_down' => '-- SQL down',
            'status' => 'pending',
            'error_message' => null,
            'executed_at' => null,
        ];
    }
}
```

- [ ] **Step 5: Write model implementation**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemMigration extends Model
{
    use HasFactory, HasKsuid;

    protected $fillable = [
        'database_id',
        'batch',
        'name',
        'operation',
        'table_name',
        'schema_name',
        'sql_up',
        'sql_down',
        'status',
        'error_message',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
        ];
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }

    public function scopeOfDatabase($query, string $databaseId)
    {
        return $query->where('database_id', $databaseId);
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOfBatch($query, int $batch)
    {
        return $query->where('batch', $batch);
    }

    public function markExecuted(): void
    {
        $this->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    public function markRolledBack(): void
    {
        $this->update(['status' => 'rolled_back']);
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

```bash
php artisan test tests/Unit/Models/SystemMigrationTest.php
```

Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Models/SystemMigration.php database/factories/SystemMigrationFactory.php tests/Unit/Models/SystemMigrationTest.php
git commit -m "feat(schema-builder): add SystemMigration model with tests"
```

---

## Track 4: Services (TDD - Depends on Enums and Models)

### Task 4.1: Create `MigrationDefinition` DTO

**Files:**
- Create: `app/DTOs/MigrationDefinition.php`

- [ ] **Step 1: Write readonly DTO**

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class MigrationDefinition
{
    public function __construct(
        public string $sqlUp,
        public string $sqlDown,
        public string $operation,
        public string $tableName,
        public string $schemaName = 'public',
    ) {}
}
```

- [ ] **Step 2: Commit**

```bash
git add app/DTOs/MigrationDefinition.php
git commit -m "feat(schema-builder): add MigrationDefinition DTO"
```

---

### Task 4.2: Create `ValidationRuleMapper` Service with Test

**Files:**
- Create: `app/Services/ValidationRuleMapper.php`
- Create: `tests/Unit/Services/ValidationRuleMapperTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ValidationPresetEnum;
use App\Services\ValidationRuleMapper;
use Tests\TestCase;

class ValidationRuleMapperTest extends TestCase
{
    private ValidationRuleMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = app(ValidationRuleMapper::class);
    }

    public function test_to_json_rules_converts_ui_input_to_json(): void
    {
        $input = [
            'name' => [
                ['preset' => 'required', 'enabled' => true],
                ['preset' => 'min_length', 'enabled' => true, 'value' => 3],
                ['preset' => 'max_length', 'enabled' => true, 'value' => 255],
            ],
            'price' => [
                ['preset' => 'required', 'enabled' => true],
                ['preset' => 'min_value', 'enabled' => true, 'value' => 0],
            ],
        ];

        $result = $this->mapper->toJsonRules($input);

        $this->assertEquals([
            'name' => ['required' => true, 'min' => 3, 'max' => 255],
            'price' => ['required' => true, 'min' => 0],
        ], $result);
    }

    public function test_to_laravel_rules_converts_json_to_laravel_rules(): void
    {
        $jsonRules = [
            'name' => ['required' => true, 'min' => 3, 'max' => 255, 'alpha' => true],
            'price' => ['required' => true, 'min' => 0, 'numeric' => true],
        ];

        $result = $this->mapper->toLaravelRules($jsonRules);

        $this->assertEquals([
            'name' => ['required', 'min:3', 'max:255', 'alpha'],
            'price' => ['required', 'min:0', 'numeric'],
        ], $result);
    }

    public function test_get_applicable_presets_filters_by_type(): void
    {
        $presets = $this->mapper->getApplicablePresets('varchar');

        $this->assertContains(ValidationPresetEnum::REQUIRED, $presets);
        $this->assertContains(ValidationPresetEnum::MIN_LENGTH, $presets);
        $this->assertContains(ValidationPresetEnum::EMAIL, $presets);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Services/ValidationRuleMapperTest.php
```

Expected: FAIL with "Class ValidationRuleMapper not found"

- [ ] **Step 3: Write service implementation**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostgresTypeEnum;
use App\Enums\ValidationPresetEnum;

class ValidationRuleMapper
{
    public function toJsonRules(array $uiInput): array
    {
        $result = [];

        foreach ($uiInput as $column => $presets) {
            foreach ($presets as $preset) {
                if (! ($preset['enabled'] ?? false)) {
                    continue;
                }

                $enum = ValidationPresetEnum::from($preset['preset']);

                $key = match ($enum) {
                    ValidationPresetEnum::MIN_LENGTH => 'min',
                    ValidationPresetEnum::MAX_LENGTH => 'max',
                    ValidationPresetEnum::MIN_VALUE => 'min',
                    ValidationPresetEnum::MAX_VALUE => 'max',
                    default => $enum->value,
                };

                $value = $preset['value'] ?? true;

                if ($enum === ValidationPresetEnum::REQUIRED || $enum === ValidationPresetEnum::INTEGER
                    || $enum === ValidationPresetEnum::NUMERIC || $enum === ValidationPresetEnum::ALPHA
                    || $enum === ValidationPresetEnum::ALPHA_NUM || $enum === ValidationPresetEnum::ALPHA_DASH
                    || $enum === ValidationPresetEnum::EMAIL || $enum === ValidationPresetEnum::URL
                    || $enum === ValidationPresetEnum::UUID || $enum === ValidationPresetEnum::DATE
                    || $enum === ValidationPresetEnum::BOOLEAN
                ) {
                    $value = true;
                }

                $result[$column][$key] = $value;
            }
        }

        return $result;
    }

    public function toLaravelRules(array $jsonRules): array
    {
        $result = [];

        foreach ($jsonRules as $column => $rules) {
            foreach ($rules as $key => $value) {
                $ruleString = is_bool($value) ? $key : "{$key}:{$value}";
                $result[$column][] = $ruleString;
            }
        }

        return $result;
    }

    public function getApplicablePresets(string $postgresType): array
    {
        try {
            $type = PostgresTypeEnum::from($postgresType);
        } catch (\ValueError $e) {
            return [];
        }

        return array_filter(
            ValidationPresetEnum::cases(),
            fn (ValidationPresetEnum $preset) => in_array($type, $preset->applicableTypes(), true)
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Unit/Services/ValidationRuleMapperTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/ValidationRuleMapper.php tests/Unit/Services/ValidationRuleMapperTest.php
git commit -m "feat(schema-builder): add ValidationRuleMapper service with tests"
```

---

### Task 4.3: Create `SchemaBuilderService` with Test

**Files:**
- Create: `app/Services/SchemaBuilderService.php`
- Create: `tests/Unit/Services/SchemaBuilderServiceTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SchemaBuilderService;
use Tests\TestCase;

class SchemaBuilderServiceTest extends TestCase
{
    private SchemaBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SchemaBuilderService::class);
    }

    public function test_validate_table_name_accepts_valid_names(): void
    {
        $this->expectNotToPerformAssertions();

        $this->service->validateTableName('users');
        $this->service->validateTableName('user_profiles');
        $this->service->validateTableName('orders123');
    }

    public function test_validate_table_name_rejects_reserved_prefixes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved prefix');

        $this->service->validateTableName('pg_temp_users');
    }

    public function test_validate_table_name_rejects_system_prefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved prefix');

        $this->service->validateTableName('system_config');
    }

    public function test_validate_table_name_rejects_invalid_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table name');

        $this->service->validateTableName('user-tables');
    }

    public function test_validate_column_name_rejects_invalid_names(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->validateColumnName('order-items');
    }

    public function test_build_column_definitions_normalizes_columns(): void
    {
        $input = [
            ['name' => 'id', 'type' => 'uuid', 'nullable' => false],
            ['name' => 'name', 'type' => 'varchar', 'nullable' => false, 'length' => 255],
            ['name' => 'price', 'type' => 'decimal', 'nullable' => true],
        ];

        $result = $this->service->buildColumnDefinitions($input);

        $this->assertCount(3, $result);
        $this->assertEquals('id', $result[0]['name']);
        $this->assertEquals('uuid', $result[0]['type']);
        $this->assertEquals('varchar(255)', $result[1]['type_definition']);
    }

    public function test_prepare_table_metadata_formats_metadata(): void
    {
        $columns = [
            ['name' => 'id', 'type' => 'uuid', 'nullable' => false],
            ['name' => 'name', 'type' => 'varchar', 'nullable' => false, 'length' => 255],
        ];

        $validations = [
            'name' => ['required' => true, 'max' => 255],
        ];

        $result = $this->service->prepareTableMetadata($columns, $validations);

        $this->assertIsArray($result['columns']);
        $this->assertEquals($validations, $result['validations']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Services/SchemaBuilderServiceTest.php
```

Expected: FAIL with "Class SchemaBuilderService not found"

- [ ] **Step 3: Write service implementation**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostgresTypeEnum;

class SchemaBuilderService
{
    private const RESERVED_PREFIXES = ['pg_', 'system_'];

    public function validateTableName(string $name): void
    {
        $this->validateIdentifier($name, 'table name');
    }

    public function validateColumnName(string $name): void
    {
        $this->validateIdentifier($name, 'column name');
    }

    public function buildColumnDefinitions(array $columns): array
    {
        $result = [];

        foreach ($columns as $column) {
            $this->validateColumnName($column['name']);

            $typeEnum = PostgresTypeEnum::from($column['type']);
            $typeDefinition = $typeEnum->toSqlDefinition($column['length'] ?? null);

            $result[] = [
                'name' => $column['name'],
                'type' => $column['type'],
                'type_definition' => $typeDefinition,
                'nullable' => (bool) ($column['nullable'] ?? false),
                'default_value' => $column['default_value'] ?? null,
                'is_primary_key' => (bool) ($column['is_primary_key'] ?? false),
                'foreign_key' => $column['foreign_key'] ?? null,
            ];
        }

        return $result;
    }

    public function prepareTableMetadata(array $columns, ?array $validations = null): array
    {
        return [
            'columns' => $this->buildColumnDefinitions($columns),
            'validations' => $validations ?? [],
        ];
    }

    private function validateIdentifier(string $name, string $label): void
    {
        if (strlen($name) > 63) {
            throw new \InvalidArgumentException("{$label} too long (max 63 characters)");
        }

        if (! preg_match('/^[a-z_][a-z0-9_]{0,62}$/', $name)) {
            throw new \InvalidArgumentException("Invalid {$label}: must start with letter or underscore, contain only lowercase letters, numbers, and underscores");
        }

        foreach (self::RESERVED_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                throw new \InvalidArgumentException("{$label} cannot start with reserved prefix '{$prefix}'");
            }
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Unit/Services/SchemaBuilderServiceTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/SchemaBuilderService.php tests/Unit/Services/SchemaBuilderServiceTest.php
git commit -m "feat(schema-builder): add SchemaBuilderService with tests"
```

---

### Task 4.4: Create `MigrationGeneratorService` with Test

**Files:**
- Create: `app/Services/MigrationGeneratorService.php`
- Create: `tests/Unit/Services/MigrationGeneratorServiceTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\MigrationDefinition;
use App\Services\MigrationGeneratorService;
use Tests\TestCase;

class MigrationGeneratorServiceTest extends TestCase
{
    private MigrationGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MigrationGeneratorService::class);
    }

    public function test_generate_create_table_produces_valid_sql(): void
    {
        $columns = [
            ['name' => 'id', 'type_definition' => 'uuid', 'nullable' => false, 'is_primary_key' => true],
            ['name' => 'name', 'type_definition' => 'varchar(255)', 'nullable' => false],
            ['name' => 'email', 'type_definition' => 'varchar(255)', 'nullable' => true],
        ];

        $result = $this->service->generateCreateTable('public', 'users', $columns);

        $this->assertInstanceOf(MigrationDefinition::class, $result);
        $this->assertStringContainsString('CREATE TABLE "public"."users"', $result->sqlUp);
        $this->assertStringContainsString('id uuid NOT NULL', $result->sqlUp);
        $this->assertStringContainsString('DROP TABLE IF EXISTS "public"."users"', $result->sqlDown);
        $this->assertEquals('add_column', $result->operation);
    }

    public function test_generate_drop_table_includes_all_columns_in_down(): void
    {
        $existingColumns = [
            ['name' => 'id', 'type' => 'uuid', 'nullable' => false],
            ['name' => 'name', 'type' => 'varchar', 'nullable' => false],
        ];

        $result = $this->service->generateDropTable('public', 'users', $existingColumns);

        $this->assertStringContainsString('DROP TABLE IF EXISTS "public"."users"', $result->sqlUp);
        $this->assertStringContainsString('CREATE TABLE "public"."users"', $result->sqlDown);
    }

    public function test_generate_add_column(): void
    {
        $column = ['name' => 'status', 'type_definition' => 'varchar(50)', 'nullable' => true];

        $result = $this->service->generateAddColumn('public', 'users', $column);

        $this->assertStringContainsString('ALTER TABLE "public"."users" ADD COLUMN', $result->sqlUp);
        $this->assertStringContainsString('status varchar(50)', $result->sqlUp);
        $this->assertStringContainsString('ALTER TABLE "public"."users" DROP COLUMN status', $result->sqlDown);
    }

    public function test_generate_drop_column(): void
    {
        $result = $this->service->generateDropColumn('public', 'users', 'old_column', 'varchar(255)');

        $this->assertStringContainsString('ALTER TABLE "public"."users" DROP COLUMN old_column', $result->sqlUp);
        $this->assertStringContainsString('ALTER TABLE "public"."users" ADD COLUMN old_column varchar(255)', $result->sqlDown);
    }

    public function test_generate_alter_column_type(): void
    {
        $result = $this->service->generateAlterColumnType('public', 'users', 'age', 'integer', 'bigint');

        $this->assertStringContainsString('ALTER TABLE "public"."users" ALTER COLUMN age TYPE bigint', $result->sqlUp);
        $this->assertStringContainsString('ALTER TABLE "public"."users" ALTER COLUMN age TYPE integer', $result->sqlDown);
    }

    public function test_generate_rename_column(): void
    {
        $result = $this->service->generateRenameColumn('public', 'users', 'old_name', 'new_name');

        $this->assertStringContainsString('ALTER TABLE "public"."users" RENAME COLUMN old_name TO new_name', $result->sqlUp);
        $this->assertStringContainsString('ALTER TABLE "public"."users" RENAME COLUMN new_name TO old_name', $result->sqlDown);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Services/MigrationGeneratorServiceTest.php
```

Expected: FAIL with "Class MigrationGeneratorService not found"

- [ ] **Step 3: Write service implementation**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\MigrationDefinition;

class MigrationGeneratorService
{
    public function generateCreateTable(string $schema, string $table, array $columns): MigrationDefinition
    {
        $columnDefs = [];

        foreach ($columns as $column) {
            $columnDef = "\"{$column['name']}\" {$column['type_definition']}";

            if (! ($column['nullable'] ?? false)) {
                $columnDef .= ' NOT NULL';
            }

            if ($column['default_value'] ?? null) {
                $columnDef .= ' DEFAULT ' . $column['default_value'];
            }

            if ($column['is_primary_key'] ?? false) {
                $columnDef .= ' PRIMARY KEY';
            }

            $columnDefs[] = $columnDef;
        }

        $sqlUp = 'CREATE TABLE "' . $schema . '"."' . $table . '" (' . implode(', ', $columnDefs) . ');';

        $sqlDown = 'DROP TABLE IF EXISTS "' . $schema . '"."' . $table . '";';

        return new MigrationDefinition(
            sqlUp: $sqlUp,
            sqlDown: $sqlDown,
            operation: 'add_column',
            tableName: $table,
            schemaName: $schema,
        );
    }

    public function generateDropTable(string $schema, string $table, array $existingColumns): MigrationDefinition
    {
        $sqlUp = 'DROP TABLE IF EXISTS "' . $schema . '"."' . $table . '";';

        $columnDefs = [];
        foreach ($existingColumns as $col) {
            $nullable = ($col['nullable'] ?? false) ? '' : ' NOT NULL';
            $columnDefs[] = "\"{$col['name']}\" {$col['type']}{$nullable}";
        }

        $sqlDown = 'CREATE TABLE "' . $schema . '"."' . $table . '" (' . implode(', ', $columnDefs) . ');';

        return new MigrationDefinition(
            sqlUp: $sqlUp,
            sqlDown: $sqlDown,
            operation: 'drop_table',
            tableName: $table,
            schemaName: $schema,
        );
    }

    public function generateAddColumn(string $schema, string $table, array $column): MigrationDefinition
    {
        $nullable = ($column['nullable'] ?? false) ? '' : ' NOT NULL';
        $default = ($column['default_value'] ?? null) ? ' DEFAULT ' . $column['default_value'] : '';

        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $table . '" ADD COLUMN "' . $column['name'] . '" ' . $column['type_definition'] . $nullable . $default . ';';

        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $table . '" DROP COLUMN ' . $column['name'] . ';';

        return new MigrationDefinition(
            sqlUp: $sqlUp,
            sqlDown: $sqlDown,
            operation: 'add_column',
            tableName: $table,
            schemaName: $schema,
        );
    }

    public function generateDropColumn(string $schema, string $table, string $column, string $type): MigrationDefinition
    {
        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $table . '" DROP COLUMN ' . $column . ';';

        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $table . '" ADD COLUMN "' . $column . '" ' . $type . ';';

        return new MigrationDefinition(
            sqlUp: $sqlUp,
            sqlDown: $sqlDown,
            operation: 'drop_column',
            tableName: $table,
            schemaName: $schema,
        );
    }

    public function generateAlterColumnType(string $schema, string $table, string $column, string $fromType, string $toType): MigrationDefinition
    {
        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $table . '" ALTER COLUMN "' . $column . '" TYPE ' . $toType . ';';

        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $table . '" ALTER COLUMN "' . $column . '" TYPE ' . $fromType . ';';

        return new MigrationDefinition(
            sqlUp: $sqlUp,
            sqlDown: $sqlDown,
            operation: 'alter_column_type',
            tableName: $table,
            schemaName: $schema,
        );
    }

    public function generateRenameColumn(string $schema, string $table, string $from, string $to): MigrationDefinition
    {
        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $table . '" RENAME COLUMN "' . $from . '" TO "' . $to . '";';

        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $table . '" RENAME COLUMN "' . $to . '" TO "' . $from . '";';

        return new MigrationDefinition(
            sqlUp: $sqlUp,
            sqlDown: $sqlDown,
            operation: 'rename_column',
            tableName: $table,
            schemaName: $schema,
        );
    }

    public function generateRenameTable(string $schema, string $fromTable, string $toTable): MigrationDefinition
    {
        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $fromTable . '" RENAME TO "' . $toTable . '";';

        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $toTable . '" RENAME TO "' . $fromTable . '";';

        return new MigrationDefinition(
            sqlUp: $sqlUp,
            sqlDown: $sqlDown,
            operation: 'rename_table',
            tableName: $fromTable,
            schemaName: $schema,
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Unit/Services/MigrationGeneratorServiceTest.php
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/MigrationGeneratorService.php tests/Unit/Services/MigrationGeneratorServiceTest.php
git commit -m "feat(schema-builder): add MigrationGeneratorService with tests"
```

---

### Task 4.5: Create `SchemaIntrospectionService` with Test

**Files:**
- Create: `app/Services/SchemaIntrospectionService.php`
- Create: `tests/Unit/Services/SchemaIntrospectionServiceTest.php`

- [ ] **Step 1: Write failing test (skip if PostgreSQL unavailable)**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Database;
use App\Services\SchemaIntrospectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaIntrospectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SchemaIntrospectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SchemaIntrospectionService::class);

        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }
    }

    public function test_get_schemas_excludes_system_schemas(): void
    {
        $database = Database::factory()->create();

        $schemas = $this->service->getSchemas($database);

        $this->assertIsArray($schemas);
        $this->assertNotContains('pg_catalog', $schemas);
        $this->assertNotContains('information_schema', $schemas);
        $this->assertNotContains('pg_toast', $schemas);
    }

    public function test_get_tables_returns_tables_for_schema(): void
    {
        $database = Database::factory()->create();

        $tables = $this->service->getTables($database, 'public');

        $this->assertIsArray($tables);
    }

    public function test_get_columns_returns_column_info(): void
    {
        $database = Database::factory()->create();

        $columns = $this->service->getColumns($database, 'public', 'users');

        $this->assertIsArray($columns);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Services/SchemaIntrospectionServiceTest.php
```

Expected: FAIL with "Class SchemaIntrospectionService not found" (or SKIP if no PostgreSQL)

- [ ] **Step 3: Write service implementation**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Database;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class SchemaIntrospectionService
{
    private const EXCLUDED_SCHEMAS = ['pg_catalog', 'information_schema', 'pg_toast'];

    public function getSchemas(Database $database): array
    {
        $connection = $this->getConnection($database);

        $rows = $connection->select(
            "SELECT schema_name FROM information_schema.schemata
             WHERE schema_name NOT IN ('" . implode("','", self::EXCLUDED_SCHEMAS) . "')
             ORDER BY schema_name"
        );

        return array_map(fn ($row) => $row->schema_name, $rows);
    }

    public function getTables(Database $database, string $schema): array
    {
        $connection = $this->getConnection($database);

        $rows = $connection->select(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = ? AND table_type = 'BASE TABLE'
             ORDER BY table_name",
            [$schema]
        );

        return array_map(fn ($row) => $row->table_name, $rows);
    }

    public function getColumns(Database $database, string $schema, string $table): array
    {
        $connection = $this->getConnection($database);

        $sql = "SELECT
                    c.column_name,
                    c.data_type,
                    c.is_nullable,
                    c.column_default,
                    COALESCE(pk.column_name IS NOT NULL, FALSE) as is_primary_key,
                    COALESCE(fk.foreign_table_name IS NOT NULL, FALSE) as is_foreign_key,
                    fk.foreign_table_name,
                    fk.foreign_column_name,
                    fk.foreign_schema_name
                FROM information_schema.columns c
                LEFT JOIN (
                    SELECT ku.column_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage ku ON tc.constraint_name = ku.constraint_name
                    WHERE tc.constraint_type = 'PRIMARY KEY' AND tc.table_schema = ? AND tc.table_name = ?
                ) pk ON c.column_name = pk.column_name
                LEFT JOIN (
                    SELECT
                        ku.column_name,
                        ccu.table_name AS foreign_table_name,
                        ccu.column_name AS foreign_column_name,
                        ccu.table_schema AS foreign_schema_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage ku ON tc.constraint_name = ku.constraint_name
                    JOIN information_schema.constraint_column_usage ccu ON tc.constraint_name = ccu.constraint_name
                    WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_schema = ? AND tc.table_name = ?
                ) fk ON c.column_name = fk.column_name
                WHERE c.table_schema = ? AND c.table_name = ?
                ORDER BY c.ordinal_position";

        $rows = $connection->select($sql, [$schema, $table, $schema, $table, $schema, $table]);

        return array_map(fn ($row) => [
            'name' => $row->column_name,
            'type' => $row->data_type,
            'nullable' => $row->is_nullable === 'YES',
            'defaultValue' => $row->column_default,
            'isPrimaryKey' => (bool) $row->is_primary_key,
            'isForeignKey' => (bool) $row->is_foreign_key,
            'foreignKey' => $row->is_foreign_key ? [
                'table' => $row->foreign_table_name,
                'column' => $row->foreign_column_name,
                'schema' => $row->foreign_schema_name,
            ] : null,
        ], $rows);
    }

    public function getTableData(
        Database $database,
        string $schema,
        string $table,
        int $page = 1,
        int $perPage = 50,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDir = 'ASC'
    ): array {
        $connection = $this->getConnection($database);
        $offset = ($page - 1) * $perPage;

        $totalQuery = 'SELECT COUNT(*) as total FROM "' . $schema . '"."' . $table . '"';
        if ($search) {
            $totalQuery .= " WHERE CAST(* AS TEXT) ILIKE ?";
        }

        $totalResult = $search
            ? $connection->select($totalQuery, ['%' . $search . '%'])
            : $connection->select($totalQuery);
        $totalRows = (int) $totalResult[0]->total;

        $sql = 'SELECT * FROM "' . $schema . '"."' . $table . '"';

        if ($search) {
            $sql .= " WHERE CAST(* AS TEXT) ILIKE ?";
        }

        if ($sortBy) {
            $sql .= ' ORDER BY "' . $sortBy . '" ' . ($sortDir === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $sql .= ' ORDER BY 1';
        }

        $sql .= ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

        $rows = $search
            ? $connection->select($sql, ['%' . $search . '%'])
            : $connection->select($sql);

        $columns = ! empty($rows) ? array_keys((array) $rows[0]) : [];

        return [
            'rows' => array_map(fn ($row) => (array) $row, $rows),
            'totalRows' => $totalRows,
            'columns' => $columns,
        ];
    }

    public function getTableRowCount(Database $database, string $schema, string $table): int
    {
        $connection = $this->getConnection($database);

        $result = $connection->select('SELECT COUNT(*) as count FROM "' . $schema . '"."' . $table . '"');

        return (int) $result[0]->count;
    }

    private function getConnection(Database $database): ConnectionInterface
    {
        return DB::connect([
            'driver' => 'pgsql',
            'host' => $database->host,
            'port' => $database->port,
            'database' => $database->database_name,
            'username' => config('database.connections.pgsql.username'),
            'password' => config('database.connections.pgsql.password'),
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Unit/Services/SchemaIntrospectionServiceTest.php
```

Expected: PASS (or SKIP if no PostgreSQL)

- [ ] **Step 5: Commit**

```bash
git add app/Services/SchemaIntrospectionService.php tests/Unit/Services/SchemaIntrospectionServiceTest.php
git commit -m "feat(schema-builder): add SchemaIntrospectionService with tests"
```

---

### Task 4.6: Create `MigrationExecutorService` with Test

**Files:**
- Create: `app/Services/MigrationExecutorService.php`
- Create: `tests/Unit/Services/MigrationExecutorServiceTest.php`

- [ ] **Step 1: Write failing test (skip if PostgreSQL unavailable)**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Database;
use App\Services\MigrationExecutorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrationExecutorServiceTest extends TestCase
{
    use RefreshDatabase;

    private MigrationExecutorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MigrationExecutorService::class);

        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }
    }

    public function test_execute_runs_sql_on_database(): void
    {
        $database = Database::factory()->create();

        $sql = 'SELECT 1';

        $this->expectNotToPerformAssertions();

        $this->service->execute($database, $sql);
    }

    public function test_execute_throws_on_invalid_sql(): void
    {
        $database = Database::factory()->create();

        $sql = 'INVALID SQL';

        $this->expectException(\Exception::class);

        $this->service->execute($database, $sql);
    }

    public function test_test_connection_returns_true_for_valid_database(): void
    {
        $database = Database::factory()->create();

        $result = $this->service->testConnection($database);

        $this->assertTrue($result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Services/MigrationExecutorServiceTest.php
```

Expected: FAIL with "Class MigrationExecutorService not found" (or SKIP if no PostgreSQL)

- [ ] **Step 3: Write service implementation**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Database;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class MigrationExecutorService
{
    public function execute(Database $database, string $sql): void
    {
        $connection = $this->getConnection($database);

        try {
            $connection->statement($sql);
        } finally {
            $connection->disconnect();
        }
    }

    public function testConnection(Database $database): bool
    {
        $connection = $this->getConnection($database);

        try {
            return $connection->getPdo() !== null;
        } catch (\Exception $e) {
            return false;
        } finally {
            $connection->disconnect();
        }
    }

    private function getConnection(Database $database): ConnectionInterface
    {
        return DB::connect([
            'driver' => 'pgsql',
            'host' => $database->host,
            'port' => $database->port,
            'database' => $database->database_name,
            'username' => config('database.connections.pgsql.username'),
            'password' => config('database.connections.pgsql.password'),
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test tests/Unit/Services/MigrationExecutorServiceTest.php
```

Expected: PASS (or SKIP if no PostgreSQL)

- [ ] **Step 5: Commit**

```bash
git add app/Services/MigrationExecutorService.php tests/Unit/Services/MigrationExecutorServiceTest.php
git commit -m "feat(schema-builder): add MigrationExecutorService with tests"
```

---

## Track 5: HTTP Layer (Depends on Services)

### Task 5.1: Create FormRequests

**Files:**
- Create: `app/Http/Requests/SchemaBuilder/CreateTableRequest.php`
- Create: `app/Http/Requests/SchemaBuilder/TableDataRequest.php`
- Create: `app/Http/Requests/Migration/CreateMigrationRequest.php`

- [ ] **Step 1: Create CreateTableRequest**

```bash
mkdir -p app/Http/Requests/SchemaBuilder
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\SchemaBuilder;

use App\Enums\PostgresTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Database::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'schema' => ['nullable', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*.name' => ['required', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'columns.*.type' => ['required', 'string', Rule::enum(PostgresTypeEnum::class)],
            'columns.*.nullable' => ['nullable', 'boolean'],
            'columns.*.default_value' => ['nullable', 'string'],
            'columns.*.length' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'columns.*.is_primary_key' => ['nullable', 'boolean'],
            'columns.*.foreign_key' => ['nullable', 'array'],
            'columns.*.foreign_key.table' => ['required_with:columns.*.foreign_key', 'string', 'max:63'],
            'columns.*.foreign_key.column' => ['required_with:columns.*.foreign_key', 'string', 'max:63'],
            'validations' => ['nullable', 'array'],
        ];
    }
}
```

- [ ] **Step 2: Create TableDataRequest**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\SchemaBuilder;

use Illuminate\Foundation\Http\FormRequest;

class TableDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'max:63'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
```

- [ ] **Step 3: Create CreateMigrationRequest**

```bash
mkdir -p app/Http/Requests/Migration
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Migration;

use App\Enums\MigrationOperationEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMigrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operation' => ['required', 'string', Rule::enum(MigrationOperationEnum::class)],
            'table_name' => ['required', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'schema_name' => ['nullable', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'column' => ['nullable', 'array'],
            'column.name' => ['nullable', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'column.type' => ['nullable', 'string'],
            'column.nullable' => ['nullable', 'boolean'],
            'column.default_value' => ['nullable', 'string'],
            'new_name' => ['nullable', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'confirmed' => ['nullable', 'boolean'],
        ];
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Requests/
git commit -m "feat(schema-builder): add FormRequests for schema builder"
```

---

### Task 5.2: Create Resources

**Files:**
- Create: `app/Http/Resources/App/SchemaResource.php`
- Create: `app/Http/Resources/App/TableDataResource.php`
- Create: `app/Http/Resources/App/ColumnResource.php`
- Create: `app/Http/Resources/System/MigrationResource.php`

- [ ] **Step 1: Create SchemaResource**

```bash
mkdir -p app/Http/Resources/App
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchemaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'schemas' => $this->resource,
        ];
    }
}
```

- [ ] **Step 2: Create TableDataResource**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'table' => $this['table'],
            'schema' => $this['schema'],
            'totalRows' => $this['totalRows'],
            'columns' => $this['columns'],
            'rows' => $this['rows'],
            'pagination' => [
                'page' => $request->integer('page', 1),
                'perPage' => $request->integer('per_page', 50),
                'totalPages' => (int) ceil($this['totalRows'] / max(1, $request->integer('per_page', 50))),
                'totalRows' => $this['totalRows'],
            ],
        ];
    }
}
```

- [ ] **Step 3: Create ColumnResource**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColumnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this['name'],
            'type' => $this['type'],
            'nullable' => $this['nullable'],
            'defaultValue' => $this['defaultValue'],
            'isPrimaryKey' => $this['isPrimaryKey'],
            'isForeignKey' => $this['isForeignKey'],
            'isUnique' => false, // TODO: implement unique detection
            'foreignKey' => $this['foreignKey'],
        ];
    }
}
```

- [ ] **Step 4: Create MigrationResource**

```bash
mkdir -p app/Http/Resources/System
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\System;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MigrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch' => $this->batch,
            'name' => $this->name,
            'operation' => $this->operation,
            'tableName' => $this->table_name,
            'schemaName' => $this->schema_name,
            'status' => $this->status,
            'errorMessage' => $this->error_message,
            'executedAt' => $this->executed_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Resources/
git commit -m "feat(schema-builder): add API resources"
```

---

### Task 5.3: Create Controllers with Tests

**Files:**
- Create: `app/Http/Controllers/App/SchemaBuilderController.php`
- Create: `app/Http/Controllers/System/MigrationController.php`
- Create: `tests/Feature/SchemaBuilderControllerTest.php`
- Create: `tests/Feature/MigrationControllerTest.php`

- [ ] **Step 1: Create SchemaBuilderController**

```bash
mkdir -p app/Http/Controllers/App
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Requests\SchemaBuilder\{CreateTableRequest, TableDataRequest};
use App\Http\Resources\App\{ColumnResource, SchemaResource, TableDataResource};
use App\Models\Database;
use App\Services\{MigrationExecutorService, MigrationGeneratorService, SchemaBuilderService, SchemaIntrospectionService};
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SchemaBuilderController extends Controller
{
    public function __construct(
        private SchemaIntrospectionService $introspectionService,
        private SchemaBuilderService $schemaBuilderService,
        private MigrationGeneratorService $migrationGeneratorService,
        private MigrationExecutorService $migrationExecutorService,
    ) {}

    public function index(Database $database): SchemaResource
    {
        $this->authorize('view', $database);

        $schemas = [];

        foreach ($this->introspectionService->getSchemas($database) as $schemaName) {
            $tables = [];

            foreach ($this->introspectionService->getTables($database, $schemaName) as $tableName) {
                $columns = $this->introspectionService->getColumns($database, $schemaName, $tableName);
                $rowCount = $this->introspectionService->getTableRowCount($database, $schemaName, $tableName);

                $tables[] = [
                    'name' => $tableName,
                    'schema' => $schemaName,
                    'rowCount' => $rowCount,
                    'columns' => $columns,
                ];
            }

            $schemas[] = [
                'name' => $schemaName,
                'tables' => $tables,
            ];
        }

        return new SchemaResource($schemas);
    }

    public function tableData(Database $database, string $schema, string $table, TableDataRequest $request): TableDataResource
    {
        $this->authorize('view', $database);

        $data = $this->introspectionService->getTableData(
            $database,
            $schema,
            $table,
            $request->integer('page', 1),
            $request->integer('per_page', 50),
            $request->input('search'),
            $request->input('sort_by'),
            $request->input('sort_dir', 'asc'),
        );

        return new TableDataResource(array_merge($data, [
            'table' => $table,
            'schema' => $schema,
        ]));
    }

    public function columns(Database $database, string $schema, string $table)
    {
        $this->authorize('view', $database);

        $columns = $this->introspectionService->getColumns($database, $schema, $table);

        return ColumnResource::collection($columns);
    }

    public function store(Database $database, CreateTableRequest $request): RedirectResponse
    {
        $this->authorize('create', $database);

        $this->schemaBuilderService->validateTableName($request->input('name'));

        $metadata = $this->schemaBuilderService->prepareTableMetadata(
            $request->input('columns'),
            $request->input('validations'),
        );

        $migrationDef = $this->migrationGeneratorService->generateCreateTable(
            $request->input('schema', 'public'),
            $request->input('name'),
            $metadata['columns'],
        );

        return DB::transaction(function () use ($database, $migrationDef, $metadata, $request) {
            $migration = $database->migrations()->create([
                'batch' => 1,
                'name' => 'Create table ' . $request->input('name'),
                'operation' => $migrationDef->operation,
                'table_name' => $request->input('name'),
                'schema_name' => $request->input('schema', 'public'),
                'sql_up' => $migrationDef->sqlUp,
                'sql_down' => $migrationDef->sqlDown,
                'status' => 'executed',
                'executed_at' => now(),
            ]);

            $this->migrationExecutorService->execute($database, $migrationDef->sqlUp);

            $database->tableMetadata()->create([
                'schema_name' => $request->input('schema', 'public'),
                'table_name' => $request->input('name'),
                'columns' => $metadata['columns'],
                'validations' => $metadata['validations'],
            ]);

            return redirect()->back()->with('toast', ['message' => __('Table created successfully')]);
        });
    }

    public function destroy(Database $database, string $schema, string $table)
    {
        $this->authorize('delete', $database);

        // TODO: Implement drop table
    }
}
```

- [ ] **Step 2: Create MigrationController**

```bash
mkdir -p app/Http/Controllers/System
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Requests\Migration\CreateMigrationRequest;
use App\Http\Resources\System\MigrationResource;
use App\Models\Database;
use App\Models\SystemMigration;
use App\Services\MigrationExecutorService;
use App\Services\MigrationGeneratorService;
use App\Services\SchemaIntrospectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class MigrationController extends Controller
{
    public function __construct(
        private SchemaIntrospectionService $introspectionService,
        private MigrationGeneratorService $migrationGeneratorService,
        private MigrationExecutorService $migrationExecutorService,
    ) {}

    public function index(Database $database)
    {
        $this->authorize('view', $database);

        $migrations = $database->migrations()->orderBy('batch')->orderBy('created_at')->get();

        return MigrationResource::collection($migrations);
    }

    public function store(Database $database, CreateMigrationRequest $request)
    {
        $this->authorize('update', $database);

        // TODO: Implement migration creation
    }

    public function rollback(Database $database, SystemMigration $migration)
    {
        $this->authorize('update', $database);

        // TODO: Implement rollback
    }

    public function showSql(Database $database, SystemMigration $migration): JsonResponse
    {
        $this->authorize('view', $database);

        return response()->json([
            'sql_up' => $migration->sql_up,
            'sql_down' => $migration->sql_down,
        ]);
    }
}
```

- [ ] **Step 3: Write feature test for SchemaBuilderController**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Database, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaBuilderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Database $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->database = Database::factory()->create(['created_by' => $this->user->id]);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('app.databases.schema', $this->database));

        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_schema_tree(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('app.databases.schema', $this->database));

        $response->assertStatus(200);
        $response->assertJsonStructure(['schemas']);
    }

    public function test_table_data_returns_paginated_data(): void
    {
        try {
            \DB::connection('pgsql')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('PostgreSQL connection not available');
        }

        $response = $this->actingAs($this->user)
            ->get(route('app.databases.tables.data', [
                'database' => $this->database,
                'schema' => 'public',
                'table' => 'users',
                'page' => 1,
                'per_page' => 50,
            ]));

        $response->assertStatus(200);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/ tests/Feature/SchemaBuilderControllerTest.php tests/Feature/MigrationControllerTest.php
git commit -m "feat(schema-builder): add controllers with feature tests"
```

---

### Task 5.4: Register Routes

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add schema builder routes**

Add to `routes/web.php`:

```php
// Schema Builder routes (app)
Route::middleware(['web', 'auth', 'feature:schema-builder'])->prefix('app')->name('app.')->group(function () {
    Route::prefix('databases')->group(function () {
        Route::get('{database}/schema', [App\SchemaBuilderController::class, 'index'])
            ->name('databases.schema');

        Route::get('{database}/tables/{schema}/{table}', [App\SchemaBuilderController::class, 'tableData'])
            ->name('databases.tables.data');

        Route::get('{database}/tables/{schema}/{table}/columns', [App\SchemaBuilderController::class, 'columns'])
            ->name('databases.tables.columns');

        Route::post('{database}/tables', [App\SchemaBuilderController::class, 'store'])
            ->name('databases.tables.store');

        Route::delete('{database}/tables/{schema}/{table}', [App\SchemaBuilderController::class, 'destroy'])
            ->name('databases.tables.destroy');
    });
});

// Migration routes (system)
Route::middleware(['web', 'auth', 'feature:schema-builder'])->prefix('system')->name('system.')->group(function () {
    Route::prefix('databases')->group(function () {
        Route::get('{database}/migrations', [System\MigrationController::class, 'index'])
            ->name('databases.migrations.index');

        Route::post('{database}/migrations', [System\MigrationController::class, 'store'])
            ->name('databases.migrations.store');

        Route::post('{database}/migrations/{migration}/rollback', [System\MigrationController::class, 'rollback'])
            ->name('databases.migrations.rollback');

        Route::get('{database}/migrations/{migration}/sql', [System\MigrationController::class, 'showSql'])
            ->name('databases.migrations.sql');
    });
});
```

- [ ] **Step 2: Commit**

```bash
git add routes/web.php
git commit -m "feat(schema-builder): add routes"
```

---

## Track 6: Frontend Types

### Task 6.1: Create Schema Types

**Files:**
- Create: `resources/js/types/schema.ts`

- [ ] **Step 1: Write types**

```typescript
export type PostgresType =
  | 'integer' | 'bigint' | 'decimal' | 'real'
  | 'varchar' | 'text' | 'char'
  | 'boolean'
  | 'timestamp' | 'date' | 'time'
  | 'uuid'
  | 'jsonb' | 'json'
  | 'text_array' | 'integer_array' | 'uuid_array'
  | 'inet' | 'cidr'

export type ValidationPresetType =
  | 'required' | 'min_length' | 'max_length' | 'min_value' | 'max_value'
  | 'integer' | 'numeric' | 'regex' | 'unique' | 'exists'
  | 'email' | 'url' | 'uuid' | 'date' | 'boolean'
  | 'in_list' | 'alpha' | 'alpha_num' | 'alpha_dash'

export interface SchemaInfo {
  name: string
  tables: TableInfo[]
}

export interface TableInfo {
  name: string
  schema: string
  rowCount: number
  columns: ColumnInfo[]
}

export interface ColumnInfo {
  name: string
  type: string
  nullable: boolean
  defaultValue: string | null
  isPrimaryKey: boolean
  isForeignKey: boolean
  isUnique: boolean
  foreignKey: { table: string; column: string; schema: string } | null
}

export interface TableDataResponse {
  table: string
  schema: string
  totalRows: number
  columns: string[]
  rows: Record<string, unknown>[]
  pagination: {
    page: number
    perPage: number
    totalPages: number
    totalRows: number
  }
}

export interface ColumnDefinition {
  name: string
  type: PostgresType
  length: number | null
  nullable: boolean
  defaultValue: string | null
  isPrimaryKey: boolean
  foreignKey: { table: string; column: string } | null
}

export interface ValidationConfig {
  preset: ValidationPresetType
  enabled: boolean
  value?: string | number | null
}

export interface ColumnValidations {
  columnName: string
  presets: ValidationConfig[]
}

export interface MigrationInfo {
  id: string
  batch: number
  name: string
  operation: string
  tableName: string
  schemaName: string
  status: 'pending' | 'executed' | 'failed' | 'rolled_back'
  sqlUp: string
  sqlDown: string
  errorMessage: string | null
  executedAt: string | null
  createdAt: string
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/types/schema.ts
git commit -m "feat(schema-builder): add TypeScript types"
```

---

## Track 7: Frontend Composables

### Task 7.1: Create `useSchemaBrowser` Composable

**Files:**
- Create: `resources/js/composables/useSchemaBrowser.ts`

- [ ] **Step 1: Write composable**

```typescript
import { ref, Ref } from 'vue'
import axios from 'axios'
import type { SchemaInfo, TableDataResponse, TableInfo, ColumnInfo } from '@/types/schema'
import { usePage } from '@inertiajs/vue3'

interface SchemaBrowserState {
  schemas: Ref<SchemaInfo[]>
  selectedSchema: Ref<string | null>
  selectedTable: Ref<string | null>
  expandedSchemas: Ref<Set<string>>
  expandedTables: Ref<Set<string>>
  loading: Ref<boolean>
  dataView: Ref<TableDataResponse | null>
  dataLoading: Ref<boolean>
  page: Ref<number>
  perPage: Ref<number>
  search: Ref<string>
  sortBy: Ref<string | null>
  sortDir: Ref<'asc' | 'desc'>
}

export function useSchemaBrowser(databaseId: string) {
  const page = usePage()
  const toast = useToast()

  const schemas = ref<SchemaInfo[]>([])
  const selectedSchema = ref<string | null>(null)
  const selectedTable = ref<string | null>(null)
  const expandedSchemas = ref<Set<string>>(new Set())
  const expandedTables = ref<Set<string>>(new Set())
  const loading = ref(false)
  const dataView = ref<TableDataResponse | null>(null)
  const dataLoading = ref(false)
  const page = ref(1)
  const perPage = ref(50)
  const search = ref('')
  const sortBy = ref<string | null>(null)
  const sortDir = ref<'asc' | 'desc'>('asc')

  const loadSchemas = async () => {
    loading.value = true
    try {
      const { data } = await axios.get(route('app.databases.schema', databaseId))
      schemas.value = data.schemas
    } catch (error) {
      toast.error('Failed to load schema')
    } finally {
      loading.value = false
    }
  }

  const toggleSchemaExpand = (schemaName: string) => {
    if (expandedSchemas.value.has(schemaName)) {
      expandedSchemas.value.delete(schemaName)
    } else {
      expandedSchemas.value.add(schemaName)
    }
    // Force reactivity
    expandedSchemas.value = new Set(expandedSchemas.value)
  }

  const toggleTableExpand = (tableKey: string) => {
    if (expandedTables.value.has(tableKey)) {
      expandedTables.value.delete(tableKey)
    } else {
      expandedTables.value.add(tableKey)
    }
    expandedTables.value = new Set(expandedTables.value)
  }

  const selectTable = async (schema: string, table: string) => {
    selectedSchema.value = schema
    selectedTable.value = table
    await loadTableData()
  }

  const loadTableData = async () => {
    if (!selectedSchema.value || !selectedTable.value) return

    dataLoading.value = true
    try {
      const { data } = await axios.get(route('app.databases.tables.data', {
        database: databaseId,
        schema: selectedSchema.value,
        table: selectedTable.value,
        page: page.value,
        per_page: perPage.value,
        search: search.value || undefined,
        sort_by: sortBy.value || undefined,
        sort_dir: sortDir.value,
      }))
      dataView.value = data
    } catch (error) {
      toast.error('Failed to load table data')
    } finally {
      dataLoading.value = false
    }
  }

  const exportCsv = async () => {
    if (!selectedSchema.value || !selectedTable.value) return

    try {
      const response = await axios.get(route('app.databases.tables.export', {
        database: databaseId,
        schema: selectedSchema.value,
        table: selectedTable.value,
      }), { responseType: 'blob' })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `${selectedTable.value}.csv`)
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (error) {
      toast.error('Failed to export CSV')
    }
  }

  return {
    schemas,
    selectedSchema,
    selectedTable,
    expandedSchemas,
    expandedTables,
    loading,
    dataView,
    dataLoading,
    page,
    perPage,
    search,
    sortBy,
    sortDir,
    loadSchemas,
    toggleSchemaExpand,
    toggleTableExpand,
    selectTable,
    loadTableData,
    exportCsv,
  }
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/composables/useSchemaBrowser.ts
git commit -m "feat(schema-builder): add useSchemaBrowser composable"
```

---

## Track 8: Frontend Components

### Task 8.1: Create Schema Components Folder

**Files:**
- Create: `resources/js/components/schema/SchemaBrowser.vue`
- Create: `resources/js/components/schema/SchemaFolder.vue`
- Create: `resources/js/components/schema/TableTreeItem.vue`
- Create: `resources/js/components/schema/ColumnBadge.vue`
- Create: `resources/js/components/schema/DataView.vue`

- [ ] **Step 1: Create SchemaBrowser component**

```bash
mkdir -p resources/js/components/schema
```

```vue
<script setup lang="ts">
import { onMounted } from 'vue'
import { useSchemaBrowser } from '@/composables/useSchemaBrowser'
import SchemaFolder from './SchemaFolder.vue'
import DataView from './DataView.vue'
import { Button } from '@/components/ui/button'
import { Plus } from 'lucide-vue-next'

interface Props {
  databaseId: string
}

const props = defineProps<Props>()

const {
  schemas,
  selectedSchema,
  selectedTable,
  expandedSchemas,
  loading,
  dataView,
  dataLoading,
  loadSchemas,
} = useSchemaBrowser(props.databaseId)

const createTableDialogOpen = ref(false)

onMounted(() => {
  loadSchemas()
})
</script>

<template>
  <div class="flex h-full">
    <!-- Schema Browser Sidebar -->
    <div class="w-64 border-r bg-card p-4 overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold">{{ __('Schema') }}</h3>
        <Button size="sm" @click="createTableDialogOpen = true">
          <Plus class="h-4 w-4 mr-1" />
          {{ __('New Table') }}
        </Button>
      </div>

      <div v-if="loading" class="text-sm text-muted-foreground">
        {{ __('Loading...') }}
      </div>

      <div v-else class="space-y-1">
        <SchemaFolder
          v-for="schema in schemas"
          :key="schema.name"
          :schema="schema"
          :expanded="expandedSchemas.has(schema.name)"
          :selected-schema="selectedSchema"
          :selected_table="selectedTable"
        />
      </div>
    </div>

    <!-- Data View -->
    <div class="flex-1">
      <DataView
        v-if="selectedTable"
        :database-id="databaseId"
        :loading="dataLoading"
        :data-view="dataView"
      />
      <div v-else class="flex items-center justify-center h-full text-muted-foreground">
        {{ __('Select a table to view data') }}
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Create SchemaFolder component**

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { ChevronRight, ChevronDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import TableTreeItem from './TableTreeItem.vue'
import type { SchemaInfo } from '@/types/schema'

interface Props {
  schema: SchemaInfo
  expanded: boolean
  selectedSchema: string | null
  selectedTable: string | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  toggle: [schemaName: string]
  selectTable: [schema: string, table: string]
}>()

const sortedTables = computed(() => {
  return [...props.schema.tables].sort((a, b) => a.name.localeCompare(b.name))
})
</script>

<template>
  <div>
    <Button
      variant="ghost"
      size="sm"
      class="w-full justify-start font-normal"
      @click="emit('toggle', schema.name)"
    >
      <component :is="expanded ? ChevronDown : ChevronRight" class="h-4 w-4 mr-1" />
      {{ schema.name }}
    </Button>

    <div v-if="expanded" class="ml-4 mt-1 space-y-1">
      <TableTreeItem
        v-for="table in sortedTables"
        :key="`${schema.name}.${table.name}`"
        :schema="schema.name"
        :table="table"
        :selected="selectedSchema === schema.name && selectedTable === table.name"
        @select="emit('selectTable', schema.name, $event)"
      />
    </div>
  </div>
</template>
```

- [ ] **Step 3: Create TableTreeItem component**

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { ChevronRight, ChevronDown, Table as TableIcon } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import ColumnBadge from './ColumnBadge.vue'
import type { TableInfo } from '@/types/schema'

interface Props {
  schema: string
  table: TableInfo
  selected: boolean
  expanded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  expanded: false,
})

const emit = defineEmits<{
  select: [tableName: string]
  toggle: []
}>()

const tableKey = computed(() => `${props.schema}.${props.table.name}`)
</script>

<template>
  <div>
    <div class="flex items-center">
      <Button
        variant="ghost"
        size="sm"
        class="flex-1 justify-start font-normal"
        :class="{ 'bg-accent': selected }"
        @click="emit('select', table.name)"
      >
        <TableIcon class="h-4 w-4 mr-2" />
        {{ table.name }}
        <span class="ml-auto text-xs text-muted-foreground">({{ table.columns.length }})</span>
      </Button>

      <Button
        variant="ghost"
        size="icon"
        class="h-6 w-6"
        @click="emit('toggle')"
      >
        <component :is="expanded ? ChevronDown : ChevronRight" class="h-3 w-3" />
      </Button>
    </div>

    <div v-if="expanded" class="ml-6 mt-1 space-y-1">
      <div v-for="column in table.columns" :key="column.name" class="flex items-center text-sm">
        <ColumnBadge :column="column" />
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 4: Create ColumnBadge component**

```vue
<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import type { ColumnInfo } from '@/types/schema'

interface Props {
  column: ColumnInfo
}

defineProps<Props>()

const getTypeColor = (type: string): string => {
  if (type.includes('int')) return 'bg-blue-500/10 text-blue-500'
  if (type.includes('char') || type.includes('text')) return 'bg-green-500/10 text-green-500'
  if (type.includes('bool')) return 'bg-purple-500/10 text-purple-500'
  if (type.includes('date') || type.includes('time')) return 'bg-orange-500/10 text-orange-500'
  return 'bg-gray-500/10 text-gray-500'
}
</script>

<template>
  <div class="flex items-center gap-1 text-xs">
    <span class="font-mono">{{ column.name }}</span>
    <Badge :class="getTypeColor(column.type)" variant="outline" class="text-[10px]">
      {{ column.type }}
    </Badge>
    <Badge v-if="column.isPrimaryKey" variant="default" class="text-[10px]">PK</Badge>
    <Badge v-if="column.isForeignKey" variant="secondary" class="text-[10px]">FK</Badge>
    <Badge v-if="column.nullable" variant="outline" class="text-[10px]">NULL</Badge>
    <Badge v-else variant="outline" class="text-[10px]">NOT NULL</Badge>
  </div>
</template>
```

- [ ] **Step 5: Create DataView component**

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from '@inertiajs/vue3'
import type { TableDataResponse } from '@/types/schema'
import { Search, ArrowUpDown, Download } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

interface Props {
  databaseId: string
  loading: boolean
  dataView: TableDataResponse | null
}

const props = defineProps<Props>()

const router = useRouter()
const searchInput = ref('')
const sortBy = ref<string | null>(null)
const sortDir = ref<'asc' | 'desc'>('asc')

const handleSort = (column: string) => {
  if (sortBy.value === column) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortDir.value = 'asc'
  }

  router.get(route('app.databases.tables.data', {
    database: props.databaseId,
    schema: props.dataView?.schema,
    table: props.dataView?.table,
    sort_by: sortBy.value,
    sort_dir: sortDir.value,
  }))
}

const handleSearch = () => {
  router.get(route('app.databases.tables.data', {
    database: props.databaseId,
    schema: props.dataView?.schema,
    table: props.dataView?.table,
    search: searchInput.value || undefined,
  }))
}
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- Toolbar -->
    <div class="border-b p-4 flex items-center gap-4">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          v-model="searchInput"
          type="text"
          placeholder="Search..."
          class="pl-9"
          @keyup.enter="handleSearch"
        />
      </div>
      <Button variant="outline" size="sm" @click="handleSearch">
        <Search class="h-4 w-4 mr-2" />
        {{ __('Search') }}
      </Button>
      <Button variant="outline" size="sm" @click="$emit('export')">
        <Download class="h-4 w-4 mr-2" />
        {{ __('Export CSV') }}
      </Button>
    </div>

    <!-- Table -->
    <div class="flex-1 overflow-auto p-4">
      <div v-if="loading" class="text-center text-muted-foreground">
        {{ __('Loading...') }}
      </div>

      <Table v-else-if="dataView && dataView.rows.length > 0">
        <TableHeader>
          <TableRow>
            <TableHead
              v-for="column in dataView.columns"
              :key="column"
              class="cursor-pointer hover:bg-accent"
              @click="handleSort(column)"
            >
              <div class="flex items-center">
                {{ column }}
                <ArrowUpDown
                  v-if="sortBy === column"
                  class="h-3 w-3 ml-1"
                  :class="{ 'rotate-180': sortDir === 'desc' }"
                />
              </div>
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="(row, idx) in dataView.rows" :key="idx">
            <TableCell v-for="column in dataView.columns" :key="column">
              {{ String(row[column] ?? '') }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <div v-else class="text-center text-muted-foreground">
        {{ __('No data available') }}
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="dataView" class="border-t p-4 flex items-center justify-between text-sm">
      <div>
        {{ __('Showing :from to :to of :total records', {
          from: (dataView.pagination.page - 1) * dataView.pagination.perPage + 1,
          to: Math.min(dataView.pagination.page * dataView.pagination.perPage, dataView.pagination.totalRows),
          total: dataView.pagination.totalRows,
        }) }}
      </div>
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="dataView.pagination.page <= 1"
          @click="router.get(route('app.databases.tables.data', {
            database: databaseId,
            schema: dataView.schema,
            table: dataView.table,
            page: dataView.pagination.page - 1,
          }))"
        >
          {{ __('Previous') }}
        </Button>
        <span>{{ dataView.pagination.page }} / {{ dataView.pagination.totalPages }}</span>
        <Button
          variant="outline"
          size="sm"
          :disabled="dataView.pagination.page >= dataView.pagination.totalPages"
          @click="router.get(route('app.databases.tables.data', {
            database: databaseId,
            schema: dataView.schema,
            table: dataView.table,
            page: dataView.pagination.page + 1,
          }))"
        >
          {{ __('Next') }}
        </Button>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/schema/
git commit -m "feat(schema-builder): add schema browser components"
```

---

## Track 9: Page Modifications

### Task 9.1: Modify Database Show Page to Add Schema Tab

**Files:**
- Modify: `resources/js/Pages/App/Databases/Show.vue`

- [ ] **Step 1: Add Schema tab**

Add to the template section (after the existing content), add:

```vue
<PvTabs default-value="info" class="w-full">
  <PvTabsList>
    <PvTabsTrigger value="info">{{ __('Information') }}</PvTabsTrigger>
    <PvTabsTrigger value="schema">{{ __('Schema') }}</PvTabsTrigger>
  </PvTabsList>

  <PvTabsContent value="info">
    <!-- Existing content here - the cards, timeline, etc. -->
  </PvTabsContent>

  <PvTabsContent value="schema" class="mt-4">
    <SchemaBrowser v-if="activeFeatures?.includes('schema-builder')" :database-id="database.id" />
    <div v-else class="text-center text-muted-foreground py-8">
      {{ __('Schema Builder feature is not enabled') }}
    </div>
  </PvTabsContent>
</PvTabs>
```

- [ ] **Step 2: Add imports to script section**

```typescript
import SchemaBrowser from '@/components/schema/SchemaBrowser.vue'
import { PvTabs, PvTabsList, PvTabsTrigger, PvTabsContent } from '@/components/ui/pv-tabs'
import { ref } from 'vue'

const activeTab = ref('info')
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/App/Databases/Show.vue
git commit -m "feat(schema-builder): add Schema tab to database show page"
```

---

## Track 10: Translations

### Task 10.1: Add Translation Keys

**Files:**
- Modify: `lang/pt.json`
- Modify: `lang/en.json`
- Modify: `lang/es.json`

- [ ] **Step 1: Add Portuguese translations**

```bash
# Add to lang/pt.json
```

```json
{
  "Schema": "Schema",
  "Schema Builder": "Construtor de Schema",
  "New Table": "Nova Tabela",
  "Select a table to view data": "Selecione uma tabela para ver os dados",
  "Loading...": "Carregando...",
  "Search": "Buscar",
  "Export CSV": "Exportar CSV",
  "Previous": "Anterior",
  "Next": "Próximo",
  "Showing :from to :to of :total records": "Mostrando :from a :to de :total registros",
  "No data available": "Nenhum dado disponível",
  "Table created successfully": "Tabela criada com sucesso",
  "Failed to load schema": "Falha ao carregar schema",
  "Failed to load table data": "Falha ao carregar dados da tabela",
  "Failed to export CSV": "Falha ao exportar CSV"
}
```

- [ ] **Step 2: Add English translations**

Add to `lang/en.json`:

```json
{
  "Schema": "Schema",
  "Schema Builder": "Schema Builder",
  "New Table": "New Table",
  "Select a table to view data": "Select a table to view data",
  "Loading...": "Loading...",
  "Search": "Search",
  "Export CSV": "Export CSV",
  "Previous": "Previous",
  "Next": "Next",
  "Showing :from to :to of :total records": "Showing :from to :to of :total records",
  "No data available": "No data available",
  "Table created successfully": "Table created successfully",
  "Failed to load schema": "Failed to load schema",
  "Failed to load table data": "Failed to load table data",
  "Failed to export CSV": "Failed to export CSV"
}
```

- [ ] **Step 3: Add Spanish translations**

Add to `lang/es.json`:

```json
{
  "Schema": "Esquema",
  "Schema Builder": "Constructor de Esquema",
  "New Table": "Nueva Tabla",
  "Select a table to view data": "Seleccione una tabla para ver los datos",
  "Loading...": "Cargando...",
  "Search": "Buscar",
  "Export CSV": "Exportar CSV",
  "Previous": "Anterior",
  "Next": "Siguiente",
  "Showing :from to :to of :total records": "Mostrando :from a :to de :total registros",
  "No data available": "Datos no disponibles",
  "Table created successfully": "Tabla creada con éxito",
  "Failed to load schema": "Error al cargar esquema",
  "Failed to load table data": "Error al cargar datos de la tabla",
  "Failed to export CSV": "Error al exportar CSV"
}
```

- [ ] **Step 4: Run validation test**

```bash
php artisan test tests/Feature/Lang/TranslationKeysTest.php
```

Expected: PASS (all keys present in all languages)

- [ ] **Step 5: Commit**

```bash
git add lang/
git commit -m "feat(schema-builder): add translations"
```

---

## Track 11: Feature Flag Update

### Task 11.1: Update `implemented_at` in Features Config

**Files:**
- Modify: `config/features.php`

- [ ] **Step 1: Update schema-builder feature**

Find the `schema-builder` entry and update `implemented_at`:

```php
'schema-builder' => [
    'name' => 'Schema Builder',
    'description' => 'Visual schema browser, table creation wizard, and dynamic migrations',
    'implemented_at' => now()->toIso8601String(),
    'phase' => 3,
],
```

- [ ] **Step 2: Commit**

```bash
git add config/features.php
git commit -m "feat(schema-builder): mark feature as implemented"
```

---

## Track 12: Policies Extension

### Task 12.1: Extend DatabasePolicy

**Files:**
- Modify: `app/Policies/DatabasePolicy.php`

- [ ] **Step 1: Add schema methods**

```php
public function viewSchema(User $user, Database $database): bool
{
    return $user->canAccessDatabase($database);
}

public function createTable(User $user, Database $database): bool
{
    return $user->canAccessDatabase($database) && $user->hasWriteAccess($database);
}

public function dropTable(User $user, Database $database): bool
{
    return $this->createTable($user, $database);
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Policies/DatabasePolicy.php
git commit -m "feat(schema-builder): extend DatabasePolicy with schema methods"
```

---

## Summary

**Total Tasks:** 45 tasks across 12 tracks

**Tracks:**
1. Database Migrations (2 tasks)
2. Enums (3 tasks with tests)
3. Models (2 tasks with tests)
4. Services (6 tasks with tests)
5. HTTP Layer (4 tasks)
6. Frontend Types (1 task)
7. Frontend Composables (1 task)
8. Frontend Components (1 task with 5 components)
9. Page Modifications (1 task)
10. Translations (1 task)
11. Feature Flag (1 task)
12. Policies Extension (1 task)

**Dependencies:**
- Track 1 → Track 3 (Migrations → Models)
- Track 2 → Track 4 (Enums → Services)
- Track 3 + 4 → Track 5 (Models + Services → HTTP)
- Track 5 → Track 8, 9 (HTTP → Components + Pages)
- All → Track 10, 11, 12 (All → Finalize)

**Parallel Execution Strategy:**
- **Phase 1:** Tracks 1, 2, 6 can run in parallel (Migrations, Enums, Types)
- **Phase 2:** Tracks 3, 4, 7 run after Phase 1 (Models, Services, Composables)
- **Phase 3:** Tracks 5, 8 run after Phase 2 (HTTP, Components)
- **Phase 4:** Tracks 9, 10, 11, 12 run after Phase 3 (Finalize)
