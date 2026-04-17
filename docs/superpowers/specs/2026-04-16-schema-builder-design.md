# Schema Builder - Design Spec

## Metadata

| Field | Value |
|-------|-------|
| Status | Approved |
| Priority | P0 (Critical) |
| Phase | 3 |
| Feature Flag | `schema-builder` (existing in `app/Features/SchemaBuilder.php`) |
| Date | 2026-04-16 |
| Dependencies | Database Creator (Phase 2), Credentials Manager |

---

## Overview

Schema Builder provides three capabilities within the database detail page:

1. **Schema Visualization** — Tree browser showing schemas, tables, and columns with a data grid for viewing table rows.
2. **Create Tables** — Two-step wizard: define columns (name, type, nullable, default, FK) then add visual validation presets (no-code Laravel rules).
3. **Dynamic Migrations** — Schema alteration operations with auto-generated SQL, history tracking, and rollback support.

All three are delivered in a single implementation cycle with a single spec.

---

## Database Schema

### `database_table_metadata`

Stores column definitions and validation rules for managed tables.

```sql
CREATE TABLE database_table_metadata (
    id CHAR(27) PRIMARY KEY,  -- KSUID
    database_id CHAR(27) NOT NULL REFERENCES databases(id) ON DELETE CASCADE,
    schema_name VARCHAR(63) NOT NULL DEFAULT 'public',
    table_name VARCHAR(63) NOT NULL,
    columns JSONB NOT NULL,       -- [{name, type, nullable, defaultValue, foreignKey}]
    validations JSONB,            -- {"column_name": {"required": true, "min": 3, ...}}
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE(database_id, schema_name, table_name)
);

CREATE INDEX idx_table_metadata_database ON database_table_metadata(database_id);
CREATE INDEX idx_table_metadata_deleted_at ON database_table_metadata(deleted_at);
```

### `system_migrations`

Stores migration history with generated SQL.

```sql
CREATE TABLE system_migrations (
    id CHAR(27) PRIMARY KEY,  -- KSUID
    database_id CHAR(27) NOT NULL REFERENCES databases(id) ON DELETE CASCADE,
    batch INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    operation VARCHAR(50) NOT NULL,    -- add_column, drop_column, etc.
    table_name VARCHAR(63) NOT NULL,
    schema_name VARCHAR(63) NOT NULL DEFAULT 'public',
    sql_up TEXT NOT NULL,
    sql_down TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- pending, executed, failed, rolled_back
    error_message TEXT NULL,
    executed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE(database_id, name)
);

CREATE INDEX idx_system_migrations_database ON system_migrations(database_id);
CREATE INDEX idx_system_migrations_status ON system_migrations(status);
```

---

## Backend Architecture

### Enums

#### `PostgresTypeEnum` (backed: string)

Categories and types:

| Category | Types |
|----------|-------|
| Numeric | `integer`, `bigint`, `decimal`, `real` |
| Text | `varchar`, `text`, `char` |
| Boolean | `boolean` |
| Datetime | `timestamp`, `date`, `time` |
| UUID | `uuid` |
| JSON | `jsonb`, `json` |
| Array | `text_array`, `integer_array`, `uuid_array` |
| Network | `inet`, `cidr` |

Methods: `label(): string`, `category(): string`, `hasLength(): bool`, `toSqlDefinition(?int $length): string`

#### `ValidationPresetEnum` (backed: string)

Maps UI presets to Laravel rules:

| Preset | Laravel Rule | Applicable Types |
|--------|-------------|-----------------|
| `required` | `required` | All |
| `min_length` | `min:x` | String, Text |
| `max_length` | `max:x` | String, Text |
| `min_value` | `min:x` | Numeric |
| `max_value` | `max:x` | Numeric |
| `integer` | `integer` | Numeric |
| `numeric` | `numeric` | All |
| `regex` | `regex:pattern` | String, Text |
| `unique` | `unique:table,column` | All |
| `exists` | `exists:table,column` | All |
| `email` | `email` | String |
| `url` | `url` | String |
| `uuid` | `uuid` | String |
| `date` | `date` | Datetime |
| `boolean` | `boolean` | Boolean |
| `in_list` | `in:a,b,c` | String |
| `alpha` | `alpha` | String |
| `alpha_num` | `alpha_num` | String |
| `alpha_dash` | `alpha_dash` | String |

Methods: `toLaravelRule(mixed $value = null): string`, `applicableTypes(): array`

#### `MigrationOperationEnum` (backed: string)

Operations: `add_column`, `drop_column`, `alter_column_type`, `rename_column`, `add_constraint`, `drop_constraint`, `add_index`, `drop_index`, `rename_table`, `drop_table`.

Methods: `isDestructive(): bool`, `label(): string`

### Models

#### `DatabaseTableMetadata`

- Traits: `HasKsuid`, `SoftDeletes`
- Fillable: `database_id`, `schema_name`, `table_name`, `columns`, `validations`
- Casts: `columns => 'array'`, `validations => 'array'`
- Relationships: `belongsTo(Database::class)`
- Scopes: `scopeOfDatabase($query, $databaseId)`, `scopeOfSchema($query, $schema)`, `scopeOfTable($query, $table)`

#### `SystemMigration`

- Traits: `HasKsuid`
- Fillable: `database_id`, `batch`, `name`, `operation`, `table_name`, `schema_name`, `sql_up`, `sql_down`, `status`, `error_message`, `executed_at`
- Casts: `executed_at => 'datetime'`
- Relationships: `belongsTo(Database::class)`
- Scopes: `scopeOfDatabase($query, $databaseId)`, `scopeOfStatus($query, $status)`, `scopeOfBatch($query, $batch)`
- Methods: `markExecuted(): void`, `markFailed(string $error): void`, `markRolledBack(): void`

### Services (pure logic — no data fetching, events, or transactions)

#### `SchemaIntrospectionService`

Reads `information_schema` from tenant databases. Creates temporary connections using connection info from the `Database` model, queries system catalogs, and disconnects. Returns raw arrays.

Methods:
- `getSchemas(Database $database): array` — lists user schemas (excludes `pg_catalog`, `information_schema`, `pg_toast`)
- `getTables(Database $database, string $schema): array` — lists tables in a schema
- `getColumns(Database $database, string $schema, string $table): array` — columns with PK/FK/unique info
- `getTableData(Database $database, string $schema, string $table, int $page, int $perPage, ?string $search, ?string $sortBy, ?string $sortDir): array` — paginated rows
- `getTableRowCount(Database $database, string $schema, string $table): int`

Uses a shared helper `getConnection(Database $database): \Illuminate\Database\ConnectionInterface` that creates a temporary connection from the `Database` model's stored host/port/name credentials. All methods use this helper and share the same connection/disconnect pattern.

#### `SchemaBuilderService`

Validates table and column names, builds column definitions.

Methods:
- `validateTableName(string $name): void` — throws on reserved prefixes or invalid chars
- `validateColumnName(string $name): void` — same validation
- `buildColumnDefinitions(array $columns): array` — normalizes and validates column definitions
- `prepareTableMetadata(array $columns, ?array $validations): array` — prepares data for storage

#### `MigrationGeneratorService`

Generates up/down SQL strings for each operation.

Methods:
- `generateCreateTable(string $schema, string $table, array $columns): MigrationDefinition`
- `generateDropTable(string $schema, string $table, array $existingColumns): MigrationDefinition`
- `generateAddColumn(string $schema, string $table, array $column): MigrationDefinition`
- `generateDropColumn(string $schema, string $table, string $column, string $type): MigrationDefinition`
- `generateAlterColumnType(string $schema, string $table, string $column, string $fromType, string $toType): MigrationDefinition`
- `generateRenameColumn(string $schema, string $table, string $from, string $to): MigrationDefinition`
- `generateRenameTable(string $schema, string $fromTable, string $toTable): MigrationDefinition`

#### `MigrationExecutorService`

Executes SQL against tenant databases using dynamic connections.

Methods:
- `execute(Database $database, string $sql): void`
- `testConnection(Database $database): bool`

Creates a temporary `pg_connect` to the tenant database using stored connection info, executes the SQL, and disconnects.

#### `ValidationRuleMapper`

Translates between JSON validation presets and Laravel rules arrays.

Methods:
- `toJsonRules(array $presets): array` — converts UI preset input to JSON storage format
- `toLaravelRules(array $jsonRules): array` — converts JSON storage to Laravel validation rules
- `getApplicablePresets(string $postgresType): array` — returns which presets apply to a type

### DTOs

#### `MigrationDefinition` (readonly)

```php
readonly class MigrationDefinition
{
    public function __construct(
        public string $sqlUp,
        public string $sqlDown,
        public string $operation,
        public string $tableName,
        public string $schemaName,
    ) {}
}
```

### Controllers

#### `SchemaBuilderController`

| Method | Action | Description |
|--------|--------|-------------|
| `index` | GET `/app/databases/{database}/schema` | Returns schema tree with tables and columns |
| `tableData` | GET `/app/databases/{database}/tables/{schema}/{table}` | Returns paginated table data |
| `columns` | GET `/app/databases/{database}/tables/{schema}/{table}/columns` | Returns detailed column info |
| `store` | POST `/app/databases/{database}/tables` | Creates table with columns + validations |
| `destroy` | DELETE `/app/databases/{database}/tables/{schema}/{table}` | Drops table with confirmation |

`store` flow:
1. Validate via `CreateTableRequest`
2. `$this->schemaBuilderService->validateTableName()`
3. `$this->schemaBuilderService->buildColumnDefinitions()`
4. `$migrationDef = $this->migrationGeneratorService->generateCreateTable()`
5. Create `SystemMigration` record (pending)
6. `$this->migrationExecutorService->execute()` the `sql_up`
7. Mark migration as executed
8. Create `DatabaseTableMetadata` record
9. Return redirect with toast

`destroy` flow:
1. Require `confirmed` parameter for destructive ops
2. Validate credential has write permission
3. Generate drop table migration
4. Execute + record in `system_migrations`
5. Soft-delete `DatabaseTableMetadata`
6. Return redirect with toast

#### `MigrationController`

| Method | Action | Description |
|--------|--------|-------------|
| `index` | GET `/system/databases/{database}/migrations` | Lists migration history |
| `store` | POST `/system/databases/{database}/migrations` | Creates and executes a migration |
| `rollback` | POST `/system/databases/{database}/migrations/{migration}/rollback` | Rolls back a migration |
| `showSql` | GET `/system/databases/{database}/migrations/{migration}/sql` | Returns generated SQL |

### FormRequests

#### `CreateTableRequest`

```php
'name' => ['required', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
'schema' => ['nullable', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
'columns' => ['required', 'array', 'min:1'],
'columns.*.name' => ['required', 'string', 'max:63', 'regex:/^[a-z_][a-z0-9_]*$/'],
'columns.*.type' => ['required', 'string', Rule::enum(PostgresTypeEnum::class)],
'columns.*.nullable' => ['nullable', 'boolean'],
'columns.*.default_value' => ['nullable', 'string'],
'columns.*.length' => ['nullable', 'integer', 'min:1', 'max:65535'],
'columns.*.foreign_key' => ['nullable', 'array'],
'columns.*.foreign_key.table' => ['required_with:columns.*.foreign_key', 'string'],
'columns.*.foreign_key.column' => ['required_with:columns.*.foreign_key', 'string'],
'validations' => ['nullable', 'array'],
```

#### `TableDataRequest`

```php
'page' => ['nullable', 'integer', 'min:1'],
'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
'search' => ['nullable', 'string', 'max:255'],
'sort_by' => ['nullable', 'string', 'max:63'],
'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
```

#### `CreateMigrationRequest`

```php
'operation' => ['required', 'string', Rule::enum(MigrationOperationEnum::class)],
'table_name' => ['required', 'string', 'max:63'],
'schema_name' => ['nullable', 'string', 'max:63'],
'column' => ['nullable', 'array'],          // for column operations
'new_name' => ['nullable', 'string', 'max:63'], // for rename operations
'confirmed' => ['nullable', 'boolean'],     // required for destructive ops
```

### Resources

#### `SchemaResource`

Returns schema tree:

```json
{
  "schemas": [
    {
      "name": "public",
      "tables": [
        {
          "name": "users",
          "rowCount": 42,
          "columns": [
            { "name": "id", "type": "uuid", "nullable": false, "isPrimaryKey": true, ... }
          ]
        }
      ]
    }
  ]
}
```

#### `TableDataResource`

Returns paginated data:

```json
{
  "table": "users",
  "schema": "public",
  "totalRows": 42,
  "columns": ["id", "name", "email"],
  "rows": [{ "id": "abc", "name": "Ana", "email": "ana@example.com" }],
  "pagination": { "page": 1, "perPage": 50, "totalPages": 1, "totalRows": 42 }
}
```

#### `MigrationResource`

Returns migration details.

### Policies

#### `DatabasePolicy` (extend existing)

Add methods: `viewSchema()`, `createTable()`, `dropTable()`. These check that the user has a credential attached to the database with appropriate permission level.

---

## Frontend Architecture

### Types (`types/schema.ts`)

```typescript
interface SchemaInfo {
  name: string
  tables: TableInfo[]
}

interface TableInfo {
  name: string
  schema: string
  rowCount: number
  columns: ColumnInfo[]
}

interface ColumnInfo {
  name: string
  type: string
  nullable: boolean
  defaultValue: string | null
  isPrimaryKey: boolean
  isForeignKey: boolean
  isUnique: boolean
  foreignKey: { table: string; column: string; schema: string } | null
}

interface TableDataResponse {
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

type PostgresType =
  | 'integer' | 'bigint' | 'decimal' | 'real'
  | 'varchar' | 'text' | 'char'
  | 'boolean'
  | 'timestamp' | 'date' | 'time'
  | 'uuid'
  | 'jsonb' | 'json'
  | 'text_array' | 'integer_array' | 'uuid_array'
  | 'inet' | 'cidr'

interface ColumnDefinition {
  name: string
  type: PostgresType
  length: number | null
  nullable: boolean
  defaultValue: string | null
  foreignKey: { table: string; column: string } | null
}

type ValidationPresetType =
  | 'required' | 'min_length' | 'max_length' | 'min_value' | 'max_value'
  | 'integer' | 'numeric' | 'regex' | 'unique' | 'exists'
  | 'email' | 'url' | 'uuid' | 'date' | 'boolean'
  | 'in_list' | 'alpha' | 'alpha_num' | 'alpha_dash'

interface ValidationConfig {
  preset: ValidationPresetType
  enabled: boolean
  value?: string | number | null
}

interface ColumnValidations {
  columnName: string
  presets: ValidationConfig[]
}

interface MigrationInfo {
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

### Composable (`composables/useSchemaBrowser.ts`)

```typescript
interface SchemaBrowserState {
  schemas: Ref<SchemaInfo[]>
  selectedSchema: Ref<string | null>
  selectedTable: Ref<string | null>
  expandedSchemas: Ref<Set<string>>
  expandedTables: Ref<Set<string>>
  loading: Ref<boolean>
  dataView: Ref<TableDataResponse | null>
  dataLoading: Ref<boolean>
}

export function useSchemaBrowser(databaseId: string) {
  // loadSchemas(), selectTable(), toggleSchemaExpand(),
  // toggleTableExpand(), loadTableData(), exportCsv()
}
```

### Components

| Component | File | Description |
|-----------|------|-------------|
| SchemaBrowser | `schema/SchemaBrowser.vue` | Container: sidebar tree + Data View area |
| SchemaFolder | `schema/SchemaFolder.vue` | Expandable schema folder with table list |
| TableTreeItem | `schema/TableTreeItem.vue` | Table node showing name + column count, expandable to show columns |
| ColumnBadge | `schema/ColumnBadge.vue` | Small tag showing PK, FK, UNIQUE, NOT NULL, or type |
| DataView | `schema/DataView.vue` | Grid with toolbar: search, sort, filter, export CSV, pagination |
| CreateTableWizard | `schema/CreateTableWizard.vue` | 2-step wizard dialog with Stepper |
| StepColumns | `schema/StepColumns.vue` | Step 1: table name, schema selector, column grid editor |
| StepValidations | `schema/StepValidations.vue` | Step 2: per-column validation preset checklist |
| ColumnEditor | `schema/ColumnEditor.vue` | Single column row in the editor grid |
| ValidationPresets | `schema/ValidationPresets.vue` | Checklist of applicable presets for a column type |

### Page Modifications

**`Pages/App/Databases/Show.vue`**: Add "Schema" tab to the existing PvTabs. When active, render the SchemaBrowser component instead of the default info view. Emit a state change that collapses the main app sidebar.

**Layout**: The main layout supports a `sidebarCollapsed` prop/ref. When the Schema tab is active, the sidebar collapses to icons-only, giving more horizontal space to the Schema Browser + Data View split layout.

### Data Flow

```
User clicks "Schema" tab
  → useSchemaBrowser.loadSchemas(databaseId)
  → GET /app/databases/{database}/schema
  → SchemaBuilderController@index
  → SchemaIntrospectionService.getSchemas() + getTables() + getColumns()
  → SchemaResource → response
  → SchemaBrowser renders tree

User clicks a table
  → useSchemaBrowser.selectTable(schema, table)
  → GET /app/databases/{database}/tables/{schema}/{table}?page=1&per_page=50
  → SchemaBuilderController@tableData
  → SchemaIntrospectionService.getTableData()
  → TableDataResource → response
  → DataView renders grid

User clicks "New Table"
  → CreateTableWizard opens as dialog
  → Step 1: define columns
  → Step 2: set validation presets
  → Submit → POST /app/databases/{database}/tables
  → SchemaBuilderController@store creates table + metadata
  → On success: close wizard, refresh schema tree
```

---

## Routing

| Method | Endpoint | Controller@Method | Auth | Permission |
|--------|----------|-------------------|------|------------|
| GET | `/app/databases/{database}/schema` | SchemaBuilderController@index | web | databases.view |
| GET | `/app/databases/{database}/tables/{schema}/{table}` | SchemaBuilderController@tableData | web | databases.view |
| GET | `/app/databases/{database}/tables/{schema}/{table}/columns` | SchemaBuilderController@columns | web | databases.view |
| POST | `/app/databases/{database}/tables` | SchemaBuilderController@store | web | databases.create |
| DELETE | `/app/databases/{database}/tables/{schema}/{table}` | SchemaBuilderController@destroy | web | databases.delete |
| GET | `/system/databases/{database}/migrations` | MigrationController@index | web | databases.view |
| POST | `/system/databases/{database}/migrations` | MigrationController@store | web | databases.create |
| POST | `/system/databases/{database}/migrations/{migration}/rollback` | MigrationController@rollback | web | databases.create |
| GET | `/system/databases/{database}/migrations/{migration}/sql` | MigrationController@showSql | web | databases.view |

All routes include middleware `['web', 'auth', 'feature:schema-builder']`.

---

## Security

### Name Validation

Table and column names must match `^[a-z_][a-z0-9_]{0,62}$`. Blocked prefixes: `pg_`, `system_`.

### Schema Filtering

`pg_catalog`, `information_schema`, `pg_toast` are excluded from all responses.

### Permission Mapping

| Credential Permission | Allowed Operations |
|----------------------|--------------------|
| `read` | View schema, view table data, export CSV |
| `write` | Create tables, add rows, execute migrations |
| `read-write` | All operations |

### Destructive Operations

DROP TABLE and DROP COLUMN require:
1. Frontend confirmation dialog
2. Backend `confirmed: true` parameter in the request
3. Generated rollback SQL stored in `system_migrations`

### Dynamic Connections

`MigrationExecutorService` creates temporary PostgreSQL connections to tenant databases using connection parameters stored in the `databases` table. Connections are created per-request and closed immediately after execution.

---

## Testing Strategy

### Unit Tests

| Test | What it tests |
|------|---------------|
| `PostgresTypeEnumTest` | Categories, hasLength, toSqlDefinition |
| `ValidationPresetEnumTest` | toLaravelRule, applicableTypes |
| `MigrationOperationEnumTest` | isDestructive, label |
| `SchemaBuilderServiceTest` | Name validation, column definition building, reserved name blocking |
| `MigrationGeneratorServiceTest` | SQL generation for all operation types (up + down) |
| `ValidationRuleMapperTest` | JSON-to-Laravel and Laravel-to-JSON conversion |
| `DatabaseTableMetadataTest` | Scopes, casts |
| `SystemMigrationTest` | Scopes, status transitions |

### Feature Tests

| Test | What it tests |
|------|---------------|
| `SchemaBuilderControllerTest` | All endpoints: auth, validation, responses |
| `MigrationControllerTest` | CRUD, execution, rollback, SQL preview |
| `SchemaAuthorizationTest` | Permission checks: read vs write access |
| `CreateTableValidationTest` | Invalid names, reserved prefixes, missing columns |

### Test Data

Use realistic names: `products`, `orders`, `users` (not `foo`, `bar`).

---

## Implementation Order

1. **Migrations** — Create `database_table_metadata` and `system_migrations` tables
2. **Enums** — `PostgresTypeEnum`, `ValidationPresetEnum`, `MigrationOperationEnum` (with tests)
3. **Models** — `DatabaseTableMetadata`, `SystemMigration` (with tests)
4. **Services** — `SchemaBuilderService`, `ValidationRuleMapper`, `MigrationGeneratorService`, `SchemaIntrospectionService`, `MigrationExecutorService` (TDD — tests first)
5. **FormRequests** — `CreateTableRequest`, `TableDataRequest`, `CreateMigrationRequest`
6. **Resources** — `SchemaResource`, `TableDataResource`, `MigrationResource`
7. **Policies** — Extend `DatabasePolicy` with schema methods
8. **Controllers** — `SchemaBuilderController`, `MigrationController` (with feature tests)
9. **Routes** — Register all endpoints
10. **Frontend Types** — `types/schema.ts`
11. **Frontend Composable** — `useSchemaBrowser.ts`
12. **Frontend Components** — SchemaBrowser, DataView, CreateTableWizard, etc.
13. **Page Modifications** — Show.vue schema tab, layout sidebar collapse
14. **Translations** — PT, EN, ES (run `TranslationKeysTest.php`)
15. **Feature Flag** — Update `implemented_at` in `config/features.php`
