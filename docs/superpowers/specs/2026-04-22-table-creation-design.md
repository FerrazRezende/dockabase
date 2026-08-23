# Table Creation - Design Spec

## Metadata

| Field | Value |
|-------|-------|
| Status | Approved |
| Priority | P0 (Critical) |
| Phase | 3 |
| Feature Flag | `schema-builder` (existing) |
| Date | 2026-04-22 |
| Dependencies | Schema Visualization (implemented), Schema Creation (modal, implemented) |

---

## Overview

Table creation is a **full-width sub-view** within the Schema Browser. Users create schemas first (via existing modal), then create tables assigned to a schema. Tables are only visible inside their schema's panel.

**Flow:**
1. User opens Schema tab → sees schema folder cards
2. User creates schema via modal (already implemented)
3. User enters a schema OR clicks [+ New Table] → full-width creation view opens
4. Table wizard: Step 1 (columns) → Step 2 (validations) → submit
5. Schema selector is mandatory in the wizard header

---

## UI Layout

### Entry Points

| Trigger | Context | Pre-filled Schema |
|---------|---------|-------------------|
| [+ New Table] button in schema folder card | Folders view | Yes (that schema) |
| [+ New Table] button in sidebar header | Browser view | Yes (current schema) |
| [+ New Table] button in sidebar empty state | Browser view | Yes (current schema) |

### State Management

Add `'create-table'` to the existing `view` ref in `SchemaBrowser.vue`:

```typescript
const view = ref<'folders' | 'browser' | 'create-table'>('folders')
```

### Step 1: Column Definitions (full-width)

```
┌──────────────────────────────────────────────────────────────────┐
│  [← Voltar para schemas]          Nova Tabela           [1/2]   │
│                                                                  │
│  Nome: [________]    Schema: [public ▼]  (obrigatório)          │
│                                                                  │
│  Colunas:                                                        │
│  ┌──────────┬──────────────┬─────────┬───────────┬──────┬──────┐│
│  │ Nome     │ Tipo         │ Nullable│ Default   │ FK   │ Acts ││
│  ├──────────┼──────────────┼─────────┼───────────┼──────┼──────┤│
│  │ id       │ uuid      ▼ │  [✗]    │ gen_rand..│  —   │ [✕]  ││
│  │ name     │ varchar   ▼ │  [✗]    │           │  —   │ [✕]  ││
│  │ price    │ decimal   ▼ │  [✗]    │ 0.00      │  —   │ [✕]  ││
│  │ cat_id   │ uuid      ▼ │  [✓]    │           │cat.id│ [✕]  ││
│  └──────────┴──────────────┴─────────┴───────────┴──────┴──────┘│
│  [+ Adicionar Coluna]                                            │
│                                                                  │
│                                           [Cancelar] [Próximo →] │
└──────────────────────────────────────────────────────────────────┘
```

**Column editor fields:**

| Field | Component | Required | Notes |
|-------|-----------|----------|-------|
| Name | Input | Yes | Validates `^[a-z_][a-z0-9_]*$`, max 63 chars |
| Type | Select (grouped) | Yes | Grouped by category (numeric, text, boolean, datetime, uuid, json, array, network) |
| Nullable | Checkbox | Yes | Default: false |
| Default | Input | No | Raw SQL expression (e.g. `gen_random_uuid()`, `now()`) |
| FK | Select (search) | No | Searches tables in current schema for target table.column |

### Step 2: Validation Presets (full-width)

```
┌──────────────────────────────────────────────────────────────────┐
│  [← Voltar para schemas]   products - Validações        [2/2]   │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ Coluna: [name ▼]  varchar(255)                             │  │
│  │                                                            │  │
│  │ ☑ Required                                                 │  │
│  │ ☑ Min Length     [3      ]                                 │  │
│  │ ☑ Max Length     [255    ]                                 │  │
│  │ ☐ Regex          [/^[a-z]+$/]                              │  │
│  │ ☐ Unique in table                                          │  │
│  │ ☐ Only letters (alpha)                                     │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ Coluna: [price ▼]  decimal                                 │  │
│  │                                                            │  │
│  │ ☑ Required                                                 │  │
│  │ ☑ Min Value      [0         ]                              │  │
│  │ ☑ Max Value      [999999.99 ]                              │  │
│  │ ☐ Must be integer                                          │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│                                    [← Voltar] [Criar Tabela]     │
└──────────────────────────────────────────────────────────────────┘
```

Each column from Step 1 gets a collapsible section. Validation presets are filtered by column type category.

### Validation Presets → Laravel Rules Mapping

| Preset | Laravel Rule | Type Categories | Has Value |
|--------|-------------|-----------------|-----------|
| `required` | `required` | all | No |
| `min_length` | `min:X` | text | Yes (integer) |
| `max_length` | `max:X` | text | Yes (integer) |
| `min_value` | `min:X` | numeric | Yes (number) |
| `max_value` | `max:X` | numeric | Yes (number) |
| `integer` | `integer` | numeric | No |
| `numeric` | `numeric` | all | No |
| `regex` | `regex:pattern` | text | Yes (string) |
| `unique` | `unique:table,column` | all | No (auto-filled) |
| `exists` | `exists:table,column` | all | Yes (table.column) |
| `email` | `email` | text | No |
| `url` | `url` | text | No |
| `uuid` | `uuid` | text | No |
| `date` | `date` | datetime | No |
| `boolean` | `boolean` | boolean | No |
| `in_list` | `in:a,b,c` | text | Yes (comma-separated) |
| `alpha` | `alpha` | text | No |
| `alpha_num` | `alpha_num` | text | No |
| `alpha_dash` | `alpha_dash` | text | No |

---

## Backend

### New Files

| File | Purpose |
|------|---------|
| `app/Enums/ValidationPresetEnum.php` | Enum with `toLaravelRule(?string $value): string` and `applicableCategories(): array` |
| `app/Services/ValidationRuleMapper.php` | Converts JSON validations → Laravel rules array and vice-versa |

### Existing Files (no changes needed)

- `SchemaBuilderController@store()` — already handles columns + validations
- `CreateTableRequest` — already validates the shape
- `SchemaBuilderService` — already prepares metadata
- `MigrationGeneratorService` — already generates SQL
- `MigrationExecutorService` — already executes SQL
- `DatabaseTableMetadata` — already stores columns + validations as JSON

### ValidationPresetEnum

```php
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

    public function label(): string;
    public function hasValue(): bool;
    public function applicableCategories(): array; // ['text', 'numeric', 'boolean', 'datetime', 'uuid', 'json', 'array', 'network', 'all']
    public function toLaravelRule(?string $value = null, ?string $table = null, ?string $column = null): string;
}
```

### ValidationRuleMapper

```php
class ValidationRuleMapper
{
    // JSON → Laravel rules: ['name' => ['required', 'min:3', 'max:255', 'alpha']]
    public static function toLaravelRules(array $columnValidations): array;

    // Laravel rules → JSON for storage
    public static function fromLaravelRules(array $rules): array;

    // Get presets applicable to a type category
    public static function getPresetsForCategory(string $category): array;
}
```

### Validation JSON Storage Format

```json
{
  "name": {
    "required": true,
    "min_length": 3,
    "max_length": 255,
    "alpha": true
  },
  "price": {
    "required": true,
    "min_value": 0,
    "max_value": 999999.99
  },
  "category_id": {
    "required": false,
    "exists": "categories,id"
  }
}
```

Keys are preset names, values are either `true` (no-param rules), a scalar (param rules), or `false` (disabled).

---

## Frontend

### New Components

| Component | File | Purpose |
|-----------|------|---------|
| CreateTableWizard | `schema/CreateTableWizard.vue` | Full-width sub-view container: step navigation, schema selector, submit |
| StepColumns | `schema/StepColumns.vue` | Step 1: editable column table |
| StepValidations | `schema/StepValidations.vue` | Step 2: per-column validation presets |
| ColumnEditor | `schema/ColumnEditor.vue` | Single column row (name, type, nullable, default, FK) |
| ValidationPresets | `schema/ValidationPresets.vue` | Checklist of presets for one column |

### Modified Components

| Component | Change |
|-----------|--------|
| `SchemaBrowser.vue` | Add `view === 'create-table'` branch, pass `schemas` list to wizard |

### Data Flow

```
SchemaBrowser: view = 'create-table'
  → CreateTableWizard receives: databaseId, schemas[], preSelectedSchema?
    → StepColumns: reactive columns[]
    → StepValidations: reactive validations: Record<string, Record<string, boolean|number|string>>
  → Submit: POST /app/databases/{database}/tables
    { name, schema, columns, validations }
  → On success: view = 'folders', loadSchemas()
```

### Entry Point Buttons

Three locations for [+ New Table]:

1. **Schema folder card** — button visible on hover/card
2. **Browser view sidebar header** — next to schema name
3. **Browser view empty tables** — "No tables yet" + button

All set `view = 'create-table'` and optionally pre-fill `selectedSchema`.

---

## Testing Strategy (TDD)

### Unit Tests

| Test | What |
|------|------|
| `ValidationPresetEnumTest` | `toLaravelRule()` produces correct rule strings, `applicableCategories()` returns correct categories, `hasValue()` correct per preset |
| `ValidationRuleMapperTest` | `toLaravelRules()` converts JSON to Laravel rules array, `fromLaravelRules()` reverse, `getPresetsForCategory()` filters correctly |

### Feature Tests

| Test | What |
|------|------|
| `CreateTableTest` | POST with columns + validations creates table in PostgreSQL, stores metadata, records migration |
| `CreateTableValidationTest` | POST with invalid data (bad name, no columns, invalid type) returns 422 |

---

## Acceptance Criteria

```gherkin
Scenario: Create table with columns and validations
  Given schema "store" exists
  When user clicks [+ New Table] on "store" card
  Then full-width creation view opens with schema pre-selected as "store"
  When user enters name "products"
  And adds columns: id (uuid, PK), name (varchar), price (decimal)
  And clicks [Próximo →]
  Then Step 2 shows validation presets for each column
  When user sets "name" as Required, Min Length 3, Max Length 255
  And clicks [Criar Tabela]
  Then table "products" is created in PostgreSQL schema "store"
  And metadata stored with columns + validations
  And user returns to schema browser with "store" showing "products"

Scenario: Schema is mandatory
  Given schemas "store" and "auth" exist
  When user opens table creation
  Then schema selector shows "store" and "auth"
  And [Criar Tabela] is disabled until schema is selected

Scenario: Skip validations
  Given schema "store" exists
  When user creates table with columns but no validations
  Then table is created with empty validations JSON
```

---

## Security

- Only users with `create` permission on the database can create tables
- Table/column names validated against SQL injection (regex `^[a-z_][a-z0-9_]*$`)
- Reserved prefixes blocked (`pg_`, `system_`)
- FK references validated against existing tables in the schema
- Default values passed as raw SQL — only allowlisted expressions (future enhancement)
