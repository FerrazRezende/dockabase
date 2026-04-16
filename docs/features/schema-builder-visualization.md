# Schema Builder - Visualização de Schema

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 3 |
| Feature Flag | `schema-builder` |
| Dependencies | Database Creator (Phase 2) |

---

## User Story

**As a** desenvolvedor usando o DockaBase
**I want to** visualizar o schema do meu database com uma interface similar ao pgAdmin
**So that** posso navegar entre schemas, tabelas e colunas de forma intuitiva e visualizar os dados de cada tabela

---

## Overview

A visualização de schema é uma **aba dentro da página de detail do database** (Show.vue). Funciona como um browser de schema similar ao pgAdmin: schemas são folders, tabelas são itens expansíveis com colunas, e ao clicar numa tabela abre uma view de dados ao lado.

## Layout

### State 1: Aba "Schema" fechada (default)

```
┌──────────────────────────────────────────────────────────────┐
│  [Informações]  [Schema]  [Console]           [Open Console] │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  (conteúdo da aba "Informações" — timeline, cards, etc.)     │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### State 2: Aba "Schema" aberta, schema expandido

```
┌──────────────────────────────────────────────────────────────────────┐
│  App Sidebar (colapsada)  │  Schema Browser   │   Data View        │
│                           │                   │                    │
│  [≡] Dashboard            │  ▼ public         │  Table: users      │
│  [⊆] Databases            │    ▼ users (3)    │                    │
│  [⚙] Settings             │      id    uuid   │  ┌──┬──────┬─────┐ │
│                           │      name  varchar │  │id│ name │email│ │
│                           │      email varchar │  ├──┼──────┼─────┤ │
│                           │    ▶ products      │  │1 │Ana   │a@..│ │
│                           │    ▶ orders        │  │2 │Bob   │b@..│ │
│                           │  ▶ analytics       │  └──┴──────┴─────┘ │
│                           │                   │                    │
│                           │  [+ New Table]    │  Pagination        │
└──────────────────────────────────────────────────────────────────────┘
```

### Fluxo de navegação

1. Usuário clica na aba **"Schema"** no database show
2. Sidebar principal do app **colapsa** (mostra só ícones)
3. Aparece o **Schema Browser** (sidebar interna) listando os PostgreSQL schemas como folders
4. Usuário clica num schema → folder expande mostrando as tabelas
5. Usuário clica numa tabela → **Data View** carrega ao lado com os dados da tabela
6. Usuário pode expandir uma tabela (ícone ▶) para ver colunas e tipos sem abrir o Data View

## Schema Browser (sidebar interna)

### Estrutura da árvore

```
▼ public                    ← PostgreSQL schema (folder)
  ▼ users (5)              ← tabela com count de colunas
    id       uuid PK       ← coluna com tipo
    name     varchar(255)
    email    varchar(255)
    created_at timestamp
  ▶ products (4)           ← tabela colapsada
  ▶ orders (6)
▶ analytics                ← schema colapsado
▶ auth                     ← schema colapsado
```

### Comportamento

| Ação | Resultado |
|------|-----------|
| Clicar no nome do schema (folder) | Expande/colapsa a lista de tabelas daquele schema |
| Clicar no nome da tabela | Abre Data View à direita com os dados da tabela |
| Clicar no expand (▶/▼) da tabela | Expande/colapsa as colunas sem abrir Data View |
| Clicar em [+ New Table] | Abre o wizard de criação de tabela (2 steps) |

### Info de cada coluna

Na expansão da tabela, cada coluna mostra:

```
├─ id          uuid       PK  NOT NULL
├─ name        varchar       NOT NULL
├─ email       varchar       NOT NULL  UNIQUE
├─ price       decimal       NULL
└─ category_id uuid         FK → categories.id
```

Tags visuais: `PK`, `FK`, `UNIQUE`, `NOT NULL`, `NULL`, `DEFAULT: now()`

## Data View

### Visualização de dados da tabela

Ao clicar numa tabela, o Data View mostra:

| Elemento | Descrição |
|----------|-----------|
| Header | Nome da tabela + count de registros |
| Toolbar | Filtros, ordenação, search, export CSV |
| Grid | Dados em formato tabela com paginação |
| Footer | Paginação + info de registros |

### Toolbar do Data View

```
[🔍 Search...]  [Sort ▼]  [Filter ▼]  [Export CSV]  [+ Add Row]
```

- **Search:** full-text search nos dados da tabela
- **Sort:** ordenar por coluna (ASC/DESC)
- **Filter:** filtros básicos (equals, contains, gt, lt, etc.)
- **Export CSV:** download dos dados filtrados
- **Add Row:** abre formulário para inserir registro (se credential tem permissão de write)

### Permissões no Data View

| Credential | O que pode fazer |
|------------|-----------------|
| `read` | Visualizar dados, exportar CSV, filtrar |
| `write` | Tudo de read + adicionar/editar/deletar registros |
| `read-write` | Tudo |

---

## Backend

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/app/databases/{database}/schema` | Lista schemas com tabelas e colunas |
| GET | `/app/databases/{database}/tables/{schema}/{table}` | Dados da tabela (paginado) |
| GET | `/app/databases/{database}/tables/{schema}/{table}/columns` | Colunas detalhadas com metadados |

### Schema Response

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
            {
              "name": "id",
              "type": "uuid",
              "nullable": false,
              "defaultValue": "gen_random_uuid()",
              "isPrimaryKey": true,
              "isForeignKey": false,
              "isUnique": false,
              "foreignKey": null
            },
            {
              "name": "category_id",
              "type": "uuid",
              "nullable": true,
              "isForeignKey": true,
              "foreignKey": {
                "table": "categories",
                "column": "id",
                "schema": "public"
              }
            }
          ]
        }
      ]
    }
  ]
}
```

### Table Data Response

```json
{
  "table": "users",
  "schema": "public",
  "totalRows": 42,
  "columns": ["id", "name", "email", "created_at"],
  "rows": [
    {"id": "abc123", "name": "Ana", "email": "ana@example.com", "created_at": "..."},
    {"id": "def456", "name": "Bob", "email": "bob@example.com", "created_at": "..."}
  ],
  "pagination": {
    "page": 1,
    "perPage": 50,
    "totalPages": 1,
    "totalRows": 42
  }
}
```

### Query de introspecção (PostgreSQL)

O backend usa `information_schema` para montar a árvore:

```sql
-- Listar schemas
SELECT schema_name FROM information_schema.schemata
WHERE schema_name NOT IN ('pg_catalog', 'information_schema', 'pg_toast');

-- Listar tabelas de um schema
SELECT table_name FROM information_schema.tables
WHERE table_schema = 'public' AND table_type = 'BASE TABLE';

-- Listar colunas com metadados
SELECT
    c.column_name,
    c.data_type,
    c.udt_name,
    c.is_nullable,
    c.column_default,
    CASE WHEN pk.column_name IS NOT NULL THEN true ELSE false END as is_primary_key,
    CASE WHEN fk.column_name IS NOT NULL THEN true ELSE false END as is_foreign_key,
    fk.foreign_table_name,
    fk.foreign_column_name
FROM information_schema.columns c
LEFT JOIN (...) pk ON ...
LEFT JOIN (...) fk ON ...
WHERE c.table_schema = ? AND c.table_name = ?
ORDER BY c.ordinal_position;
```

---

## Frontend

### Files to Create/Modify

| File | Action |
|------|--------|
| `resources/js/Pages/App/Databases/Show.vue` | Add "Schema" tab via PvTabs |
| `resources/js/components/schema/SchemaBrowser.vue` | New — sidebar com árvore de schemas |
| `resources/js/components/schema/SchemaFolder.vue` | New — folder de schema expansível |
| `resources/js/components/schema/TableTreeItem.vue` | New — item de tabela com expand de colunas |
| `resources/js/components/schema/ColumnBadge.vue` | New — badge de coluna (PK, FK, type) |
| `resources/js/components/schema/DataView.vue` | New — grid de dados da tabela |
| `resources/js/composables/useSchemaBrowser.ts` | New — state management do browser |
| `resources/js/types/schema.ts` | New — tipos TypeScript |

### State Management

O composable `useSchemaBrowser` gerencia:

```typescript
interface SchemaBrowserState {
  schemas: Schema[]
  selectedSchema: string | null
  selectedTable: string | null
  expandedSchemas: Set<string>
  expandedTables: Set<string>
  sidebarCollapsed: boolean  // sidebar principal do app
}
```

Quando a aba "Schema" é ativada:
1. `sidebarCollapsed = true` → sidebar principal do app colapsa
2. Carrega lista de schemas via API
3. Quando sai da aba → `sidebarCollapsed = false` → sidebar principal volta ao normal

### Interação com layout do App

O layout principal do app (AppLayout.vue) precisa expor uma prop/ref para controlar se a sidebar está colapsada:

```vue
<!-- AppLayout.vue -->
<AppSidebar :collapsed="isSchemaBrowserActive" />
<slot /> <!-- aqui entra o SchemaBrowser + DataView -->
```

---

## Security Considerations

- [ ] Apenas usuários com credential atrelada ao database podem ver o schema
- [ ] Permissão de read para visualizar, write para criar tabelas/linhas
- [ ] Queries de introspecção rodam com role do usuário, não com superuser
- [ ] Schema `pg_catalog` e `information_schema` nunca aparecem no browser
