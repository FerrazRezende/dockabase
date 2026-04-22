# Schema Builder Implementation Plan (Visualization Only)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a visual schema browser for DockaBase databases (pgAdmin-style folder navigation).

**Scope:** Schema visualization only. Table creation/editing deferred to future phase.

**Architecture:** Single service (Introspection) with Vue 3 frontend using Inertia.js. Queries run on tenant databases via dynamic connections.

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, Vue 3, TypeScript, shadcn-vue, Laravel Pennant

---

## File Structure

```
Backend:
├── app/Services/
│   └── SchemaIntrospectionService.php
├── app/Http/Controllers/App/
│   └── SchemaController.php
├── app/Http/Requests/SchemaBuilder/
│   └── TableDataRequest.php
├── app/Http/Resources/App/
│   ├── SchemaResource.php
│   └── TableDataResource.php
├── app/Policies/
│   └── DatabasePolicy.php (extend - add viewSchema method)
└── routes/
    └── web.php (add routes)

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
│   └── DataView.vue
└── resources/js/Pages/App/Databases/
    └── Show.vue (modify - add Schema tab)

Tests:
├── tests/Unit/Services/
│   └── SchemaIntrospectionServiceTest.php
└── tests/Feature/
    └── SchemaControllerTest.php
```

---

## Track 1: SchemaIntrospectionService

### Task 1.1: Create Service

**File:** `app/Services/SchemaIntrospectionService.php`

- [ ] **Step 1: Create service class** with `declare(strict_types=1);`
  ```php
  namespace App\Services;

  use App\Models\Database;
  use Illuminate\Database\ConnectionInterface;

  class SchemaIntrospectionService
  {
      protected function getConnection(Database $database): ConnectionInterface
      {
          // Create dynamic connection to tenant database
      }

      public function getSchemas(Database $database): array
      {
          // Query information_schema.schemata
          // Filter out: pg_catalog, information_schema, pg_toast
      }

      public function getTables(Database $database, string $schema): array
      {
          // Query information_schema.tables
          // Return table names with row counts
      }

      public function getColumns(Database $database, string $schema, string $table): array
      {
          // Query information_schema.columns + key_column_usage
          // Return: name, type, nullable, default, isPrimaryKey, isForeignKey, isUnique
      }

      public function getTableData(
          Database $database,
          string $schema,
          string $table,
          int $page = 1,
          int $perPage = 50,
          ?string $search = null,
          ?string $sortBy = null,
          ?string $sortDir = 'asc'
      ): array {
          // Query actual table data with pagination
          // Apply search if provided
          // Apply sorting if provided
      }

      public function getTableRowCount(Database $database, string $schema, string $table): int
      {
          // Return COUNT(*) for table
      }
  }
  ```

- [ ] **Step 2: Implement `getConnection()` helper**
  ```php
  protected function getConnection(Database $database): ConnectionInterface
  {
      return \DB::connection("tenant_{$database->id}");
      // Note: May need to configure dynamic connection in config/database.php
  }
  ```

- [ ] **Step 3: Implement `getSchemas()`**
  - Query: `SELECT schema_name FROM information_schema.schemata WHERE schema_name NOT IN ('pg_catalog', 'information_schema', 'pg_toast') ORDER BY schema_name`
  - Return: `[['name' => 'public'], ['name' => 'auth'], ...]`

- [ ] **Step 4: Implement `getTables()`**
  - Query: `SELECT table_name, (SELECT COUNT(*) FROM "{schema}"."{table}") as row_count FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE'`
  - Return: `[['name' => 'users', 'rowCount' => 42], ...]`

- [ ] **Step 5: Implement `getColumns()`**
  - Query `information_schema.columns` for: column_name, data_type, is_nullable, column_default
  - Query `information_schema.key_column_usage` for: PK, FK info
  - Return: array with name, type, nullable, defaultValue, isPrimaryKey, isForeignKey, isUnique

- [ ] **Step 6: Implement `getTableData()`**
  - Build SELECT query with pagination (OFFSET/LIMIT)
  - Apply WHERE clause if search provided (ILIKE on all text columns)
  - Apply ORDER BY if sortBy provided
  - Return: `['rows' => [...], 'totalRows' => count, 'columns' => column_names]`

### Task 1.2: Write Tests for SchemaIntrospectionService

**File:** `tests/Unit/Services/SchemaIntrospectionServiceTest.php`

- [ ] **Step 1: Create test class**
  ```php
  namespace Tests\Unit\Services;

  use App\Services\SchemaIntrospectionService;
  use App\Models\Database;
  use Illuminate\Foundation\Testing\RefreshDatabase;

  class SchemaIntrospectionServiceTest extends TestCase
  {
      use RefreshDatabase;

      protected SchemaIntrospectionService $service;

      protected function setUp(): void
      {
          parent::setUp();
          $this->service = new SchemaIntrospectionService();
      }
  }
  ```

- [ ] **Step 2: Test `getSchemas()` filters system catalogs**
  - Create a test database
  - Call `getSchemas()`
  - Assert `pg_catalog`, `information_schema`, `pg_toast` NOT in results
  - Assert `public` IS in results

- [ ] **Step 3: Test `getTables()` returns tables with row counts**
  - Create test database with known tables
  - Call `getTables($database, 'public')`
  - Assert table names are correct
  - Assert rowCount matches COUNT(*)

- [ ] **Step 4: Test `getColumns()` returns column info**
  - Create test table with: PK column, FK column, unique column, nullable column
  - Call `getColumns($database, 'public', 'test_table')`
  - Assert isPrimaryKey is true for PK column
  - Assert isForeignKey is true for FK column
  - Assert nullable matches schema

- [ ] **Step 5: Test `getTableData()` handles pagination**
  - Create test table with 100 rows
  - Call `getTableData($database, 'public', 'test_table', page: 1, perPage: 50)`
  - Assert 50 rows returned
  - Assert totalRows is 100

- [ ] **Step 6: Test `getTableData()` handles search**
  - Create test table with searchable data
  - Call `getTableData($database, 'public', 'test_table', search: 'test')`
  - Assert only matching rows returned

---

## Track 2: FormRequest and Resources

### Task 2.1: Create TableDataRequest

**File:** `app/Http/Requests/SchemaBuilder/TableDataRequest.php`

- [ ] **Step 1: Create FormRequest**
  ```php
  namespace App\Http\Requests\SchemaBuilder;

  use Illuminate\Foundation\Http\FormRequest;

  class TableDataRequest extends FormRequest
  {
      public function authorize(): bool
      {
          return $this->user()->can('viewSchema', $this->database);
      }

      public function rules(): array
      {
          return [
              'schema' => ['required', 'string', 'max:63'],
              'table' => ['required', 'string', 'max:63'],
              'page' => ['nullable', 'integer', 'min:1'],
              'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
              'search' => ['nullable', 'string', 'max:255'],
              'sort_by' => ['nullable', 'string', 'max:63'],
              'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
          ];
      }
  }
  ```

### Task 2.2: Create SchemaResource

**File:** `app/Http/Resources/App/SchemaResource.php`

- [ ] **Step 1: Create resource**
  ```php
  namespace App\Http\Resources\App;

  use Illuminate\Http\Resources\Json\JsonResource;

  class SchemaResource extends JsonResource
  {
      public function toArray($request): array
      {
          return [
              'schemas' => $this->resource,
          ];
      }
  }
  ```

### Task 2.3: Create TableDataResource

**File:** `app/Http/Resources/App/TableDataResource.php`

- [ ] **Step 1: Create resource**
  ```php
  namespace App\Http\Resources\App;

  use Illuminate\Http\Resources\Json\JsonResource;

  class TableDataResource extends JsonResource
  {
      public function toArray($request): array
      {
          return [
              'table' => $this->table,
              'schema' => $this->schema,
              'totalRows' => $this->totalRows,
              'columns' => $this->columns,
              'rows' => $this->rows,
              'pagination' => [
                  'page' => $this->page,
                  'perPage' => $this->perPage,
                  'totalPages' => $this->totalPages,
                  'totalRows' => $this->totalRows,
              ],
          ];
      }
  }
  ```

---

## Track 3: Policy

### Task 3.1: Extend DatabasePolicy

**File:** `app/Policies/DatabasePolicy.php`

- [ ] **Step 1: Add `viewSchema()` method**
  ```php
  public function viewSchema(User $user, Database $database): bool
  {
      // User must have a credential attached to this database
      return $database->credentials()
          ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
          ->exists();
  }
  ```

---

## Track 4: Controller

### Task 4.1: Create SchemaController

**File:** `app/Http/Controllers/App/SchemaController.php`

- [ ] **Step 1: Create controller**
  ```php
  namespace App\Http\Controllers\App;

  use App\Models\Database;
  use App\Http\Resources\App\SchemaResource;
  use App\Http\Resources\App\TableDataResource;
  use App\Http\Requests\SchemaBuilder\TableDataRequest;
  use App\Services\SchemaIntrospectionService;
  use Illuminate\Http\JsonResponse;

  class SchemaController extends Controller
  {
      public function __construct(
          private SchemaIntrospectionService $introspection
      ) {}

      public function index(Database $database): JsonResponse
      {
          $this->authorize('viewSchema', $database);

          $schemas = [];
          $schemaNames = $this->introspection->getSchemas($database);

          foreach ($schemaNames as $schemaName) {
              $tables = $this->introspection->getTables($database, $schemaName['name']);

              $schema = [
                  'name' => $schemaName['name'],
                  'tables' => [],
              ];

              foreach ($tables as $table) {
                  $columns = $this->introspection->getColumns(
                      $database,
                      $schemaName['name'],
                      $table['name']
                  );

                  $schema['tables'][] = [
                      'name' => $table['name'],
                      'schema' => $schemaName['name'],
                      'rowCount' => $table['rowCount'],
                      'columns' => $columns,
                  ];
              }

              $schemas[] = $schema;
          }

          return new SchemaResource($schemas);
      }

      public function tableData(TableDataRequest $request, Database $database): JsonResponse
      {
          $result = $this->introspection->getTableData(
              $database,
              $request->schema,
              $request->table,
              $request->input('page', 1),
              $request->input('per_page', 50),
              $request->input('search'),
              $request->input('sort_by'),
              $request->input('sort_dir', 'asc')
          );

          return new TableDataResource([
              'table' => $request->table,
              'schema' => $request->schema,
              'totalRows' => $result['totalRows'],
              'columns' => $result['columns'],
              'rows' => $result['rows'],
              'page' => $request->input('page', 1),
              'perPage' => $request->input('per_page', 50),
              'totalPages' => (int) ceil($result['totalRows'] / $request->input('per_page', 50)),
          ]);
      }
  }
  ```

### Task 4.2: Write Tests for SchemaController

**File:** `tests/Feature/SchemaControllerTest.php`

- [ ] **Step 1: Create test class**
  ```php
  namespace Tests\Feature;

  use App\Models\User;
  use App\Models\Database;
  use App\Models\Credential;
  use Illuminate\Foundation\Testing\RefreshDatabase;

  class SchemaControllerTest extends TestCase
  {
      use RefreshDatabase;

      protected User $user;

      protected function setUp(): void
      {
          parent::setUp();
          $this->user = User::factory()->create();
      }
  }
  ```

- [ ] **Step 2: Test `index()` requires schema permission**
  - Create database WITHOUT attaching credential to user
  - Act as user
  - GET `/app/databases/{database}/schema`
  - Assert 403 status

- [ ] **Step 3: Test `index()` returns schema tree**
  - Create database WITH credential attached to user
  - Act as user
  - GET `/app/databases/{database}/schema`
  - Assert 200 status
  - Assert JSON structure has schemas → tables → columns

- [ ] **Step 4: Test `tableData()` handles pagination**
  - Create database with credential attached
  - Act as user
  - GET `/app/databases/{database}/tables/data?schema=public&table=users&page=1&per_page=10`
  - Assert 200 status
  - Assert JSON has pagination info

---

## Track 5: Routes

### Task 5.1: Register Routes

**File:** `routes/web.php`

- [ ] **Step 1: Add routes**
  ```php
  use App\Http\Controllers\App\SchemaController;

  // Schema Builder (visualization only)
  Route::middleware(['web', 'auth', 'feature:schema-builder'])->group(function () {
      Route::get('/app/databases/{database}/schema', [SchemaController::class, 'index'])->name('app.databases.schema');
      Route::get('/app/databases/{database}/tables/data', [SchemaController::class, 'tableData'])->name('app.databases.tables.data');
  });
  ```

---

## Track 6: Frontend Types

### Task 6.1: Create Schema Types

**File:** `resources/js/types/schema.ts`

- [ ] **Step 1: Create types**
  ```typescript
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
  ```

---

## Track 7: Frontend Composable

### Task 7.1: Create useSchemaBrowser

**File:** `resources/js/composables/useSchemaBrowser.ts`

- [ ] **Step 1: Create composable** (already exists - verify it matches)
  ```typescript
  import { ref, type Ref } from 'vue'
  import axios from 'axios'
  import type { SchemaInfo, TableDataResponse } from '@/types/schema'
  import { useToast } from '@/composables/useToast'

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
        toast.error(__('Failed to load schema'))
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
        toast.error(__('Failed to load table data'))
      } finally {
        dataLoading.value = false
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
    }
  }
  ```

---

## Track 8: Frontend Components

### Task 8.1: Verify SchemaBrowser.vue

**File:** `resources/js/components/schema/SchemaBrowser.vue`

- [ ] **Step 1: Verify component exists and matches structure**
  - Should have sidebar with schema folders
  - Should have data view area
  - Should use SchemaFolder and DataView components

### Task 8.2: Verify SchemaFolder.vue

**File:** `resources/js/components/schema/SchemaFolder.vue`

- [ ] **Step 1: Verify component exists**
  - Should render expandable folder
  - Should show table list when expanded
  - Should emit toggle/select events

### Task 8.3: Verify TableTreeItem.vue

**File:** `resources/js/components/schema/TableTreeItem.vue`

- [ ] **Step 1: Verify component exists**
  - Should show table name and column count
  - Should be expandable to show columns
  - Should emit select/toggle events

### Task 8.4: Verify ColumnBadge.vue

**File:** `resources/js/components/schema/ColumnBadge.vue`

- [ ] **Step 1: Verify component exists**
  - Should show PK badge for primary keys
  - Should show FK badge for foreign keys
  - Should show column type

### Task 8.5: Verify DataView.vue

**File:** `resources/js/components/schema/DataView.vue`

- [ ] **Step 1: Verify component exists**
  - Should render table with rows
  - Should have search functionality
  - Should have pagination controls
  - Should handle empty state

---

## Track 9: Page Integration

### Task 9.1: Add Schema Tab to Database Show

**File:** `resources/js/Pages/App/Databases/Show.vue`

- [ ] **Step 1: Verify Schema tab exists**
  - Check that PvTabs includes Schema tab
  - Check that tab is conditional on `schema-builder` feature flag
  - Check that SchemaBrowser renders when tab is active

---

## Track 10: Translations

### Task 10.1: Verify Translations

**Files:** `lang/pt.json`, `lang/en.json`, `lang/es.json`

- [ ] **Step 1: Run translation validation test**
  ```bash
  php artisan test tests/Feature/Lang/TranslationKeysTest.php
  ```
- [ ] **Step 2: Verify all Schema-related translations exist**
  - "Schema"
  - "Schema Builder"
  - "Failed to load schema"
  - "Failed to load table data"
  - "Search..."
  - "Export CSV"
  - "Showing :from to :to of :total records"
  - "No data available"
  - etc.

---

## Track 11: Feature Flag Update

### Task 11.1: Update Feature Flag

**File:** `config/features.php`

- [ ] **Step 1: Update `implemented_at` for `schema-builder`**
  ```php
  'schema-builder' => [
      'name' => 'Schema Builder',
      'description' => 'Visual schema browser for viewing database tables and columns',
      'implemented_at' => '2026-04-18', // Update to today's date
      'status' => 'available',
  ],
  ```

---

## Definition of Done

- [ ] All backend tests pass (Unit + Feature)
- [ ] Frontend loads without errors
- [ ] Schema tab appears on database show page
- [ ] Schema browser displays schemas → tables → columns
- [ ] Clicking a table loads data in DataView
- [ ] Pagination works in DataView
- [ ] Search works in DataView
- [ ] All translations validated
- [ ] Feature flag marked as implemented
- [ ] No console errors in browser
- [ ] Page loads within 2 seconds on standard connection

---

## Out of Scope (Future Phases)

The following are **NOT** part of this implementation:

- ❌ Create tables
- ❌ Edit columns (add, drop, alter)
- ❌ Dynamic migrations
- ❌ Validation presets
- ❌ Export schema JSON
- ❌ SQL preview panel
- ❌ Inline data editing

These will be considered for a future phase when table creation is implemented.
