# Schema Builder - Design Spec (Visualization Only)

## Metadata

| Field | Value |
|-------|-------|
| Status | Approved |
| Priority | P0 (Critical) |
| Phase | 3 |
| Feature Flag | `schema-builder` (existing in `app/Features/SchemaBuilder.php`) |
| Date | 2026-04-18 (Updated) |
| Dependencies | Database Creator (Phase 2), Credentials Manager |
| Scope | **Schema Visualization Only** — Table creation deferred to future phase |

---

## Overview

Schema Builder provides **schema visualization only** within the database detail page:

1. **Schema Browser** — Tree view showing schemas and tables (pgAdmin-style folders)
2. **Table Data View** — Paginated grid for viewing table rows

**Out of Scope (deferred to future phase):**
- Create tables
- Edit columns
- Dynamic migrations
- Validation presets

---

## Database Schema

### No New Tables Required

Since we're only visualizing existing schemas, no new metadata tables are needed in this phase.

---

## Backend Architecture

### Services (pure logic — no data fetching, events, or transactions)

#### `SchemaIntrospectionService`

Reads `information_schema` from tenant databases. Creates temporary connections using connection info from the `Database` model, queries system catalogs, and disconnects. Returns raw arrays.

**Methods:**
- `getSchemas(Database $database): array` — lists user schemas (excludes `pg_catalog`, `information_schema`, `pg_toast`)
- `getTables(Database $database, string $schema): array` — lists tables in a schema
- `getColumns(Database $database, string $schema, string $table): array` — columns with PK/FK/unique info
- `getTableData(Database $database, string $schema, string $table, int $page, int $perPage, ?string $search, ?string $sortBy, ?string $sortDir): array` — paginated rows
- `getTableRowCount(Database $database, string $schema, string $table): int`

Uses a shared helper `getConnection(Database $database): \Illuminate\Database\ConnectionInterface` that creates a temporary connection from the `Database` model's stored host/port/name credentials. All methods use this helper and share the same connection/disconnect pattern.

### DTOs

None required for visualization phase.

### Controllers

#### `SchemaController`

| Method | Action | Description |
|--------|--------|-------------|
| `index` | GET `/app/databases/{database}/schema` | Returns schema tree with tables and columns |
| `tableData` | GET `/app/databases/{database}/tables/data` | Returns paginated table data |

`index` flow:
1. Check permission via `DatabasePolicy`
2. `$this->schemaIntrospectionService->getSchemas()`
3. For each schema: `getTables()` and `getColumns()`
4. Return via `SchemaResource`

`tableData` flow:
1. Validate via `TableDataRequest`
2. `$this->schemaIntrospectionService->getTableData()`
3. Return via `TableDataResource`

### FormRequests

#### `TableDataRequest`

```php
'page' => ['nullable', 'integer', 'min:1'],
'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
'search' => ['nullable', 'string', 'max:255'],
'sort_by' => ['nullable', 'string', 'max:63'],
'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
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
            { "name": "id", "type": "uuid", "nullable": false, "isPrimaryKey": true, "isForeignKey": false, "isUnique": true, "defaultValue": null, "foreignKey": null }
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

### Policies

#### `DatabasePolicy` (extend existing)

Add method: `viewSchema()` — checks that the user has a credential attached to the database with at least read permission.

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
  page: Ref<number>
  perPage: Ref<number>
  search: Ref<string>
  sortBy: Ref<string | null>
  sortDir: Ref<'asc' | 'desc'>
}

export function useSchemaBrowser(databaseId: string) {
  // loadSchemas(), selectTable(), toggleSchemaExpand(),
  // toggleTableExpand(), loadTableData()
}
```

### Components

| Component | File | Description |
|-----------|------|-------------|
| SchemaBrowser | `schema/SchemaBrowser.vue` | Container: sidebar tree (schemas → tables) + Data View area |
| SchemaFolder | `schema/SchemaFolder.vue` | Expandable schema folder with table list |
| TableTreeItem | `schema/TableTreeItem.vue` | Table node: name + column count, expandable to show columns |
| ColumnBadge | `schema/ColumnBadge.vue` | Small badge showing PK, FK, UNIQUE, NOT NULL, or type |
| DataView | `schema/DataView.vue` | Grid with toolbar: search, sort, pagination |

**Removed (deferred):**
- CreateTableWizard
- StepColumns
- StepValidations
- ColumnEditor
- ValidationPresets

### Page Modifications

**`Pages/App/Databases/Show.vue`**: Add "Schema" tab to the existing PvTabs. When active, render the SchemaBrowser component.

**Layout**: The Schema Browser has its own internal split layout (sidebar tree + data view) that fills the available space.

### Data Flow

```
User clicks "Schema" tab
  → useSchemaBrowser.loadSchemas(databaseId)
  → GET /app/databases/{database}/schema
  → SchemaController@index
  → SchemaIntrospectionService.getSchemas() + getTables() + getColumns()
  → SchemaResource → response
  → SchemaBrowser renders tree

User clicks a table
  → useSchemaBrowser.selectTable(schema, table)
  → GET /app/databases/{database}/tables/data?schema={schema}&table={table}&page=1&per_page=50
  → SchemaController@tableData
  → SchemaIntrospectionService.getTableData()
  → TableDataResource → response
  → DataView renders grid
```

---

## Routing

| Method | Endpoint | Controller@Method | Auth | Permission |
|--------|----------|-------------------|------|------------|
| GET | `/app/databases/{database}/schema` | SchemaController@index | web | databases.view |
| GET | `/app/databases/{database}/tables/data` | SchemaController@tableData | web | databases.view |

All routes include middleware `['web', 'auth', 'feature:schema-builder']`.

---

## Security

### Schema Filtering

`pg_catalog`, `information_schema`, `pg_toast` are excluded from all responses.

### Permission Mapping

| Credential Permission | Allowed Operations |
|----------------------|--------------------|
| `read` | View schema, view table data |
| `write` | View schema, view table data |
| `read-write` | View schema, view table data |

**Note:** No write operations in this phase.

### Dynamic Connections

`SchemaIntrospectionService` creates temporary PostgreSQL connections to tenant databases using connection parameters stored in the `databases` table. Connections are created per-request and closed immediately after query execution.

---

## Testing Strategy

### Unit Tests

| Test | What it tests |
|------|---------------|
| `SchemaIntrospectionServiceTest` | getSchemas filters system catalogs, getTables returns correct structure, getColumns returns PK/FK info, getTableData handles pagination and search |

### Feature Tests

| Test | What it tests |
|------|---------------|
| `SchemaControllerTest` | Auth, response structure, pagination parameters |
| `SchemaAuthorizationTest` | Permission checks: read vs no access |

### Test Data

Use realistic names: `products`, `orders`, `users` (not `foo`, `bar`).

---

## Implementation Order

### Backend (in order):

1. **Service** — `SchemaIntrospectionService` (TDD — tests first)
2. **FormRequest** — `TableDataRequest`
3. **Resources** — `SchemaResource`, `TableDataResource`
4. **Policy** — Extend `DatabasePolicy` with `viewSchema()` method
5. **Controller** — `SchemaController` (with feature tests)
6. **Routes** — Register endpoints

### Frontend (in order):

1. **Types** — `types/schema.ts`
2. **Composable** — `useSchemaBrowser.ts`
3. **Components** — SchemaBrowser, SchemaFolder, TableTreeItem, ColumnBadge, DataView
4. **Page** — Show.vue schema tab
5. **Translations** — PT, EN, ES (run `TranslationKeysTest.php`)
6. **Feature Flag** — Update `implemented_at` in `config/features.php`

---

## Future Enhancements (Out of Scope)

The following features are **deferred** to a future phase:

- Create tables
- Edit columns (add, drop, alter)
- Dynamic migrations with history
- Validation presets for Laravel rules
- Export schema as JSON
- SQL preview panel
