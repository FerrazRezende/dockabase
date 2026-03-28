# Schema Builder - Criar Tabelas

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 2 |
| Feature Flag | `database-manager` |
| Dependencies | Core Infrastructure (Phase 1) |

---

## User Story

**As a** desenvolvedor do projeto
**I want to** criar tabelas de banco de dados através de uma interface visual
**So that** posso definir a estrutura de dados da minha aplicação sem escrever SQL manualmente

---

## Acceptance Criteria

```gherkin
Scenario: Criar tabela com colunas básicas
  Given estou autenticado como admin do projeto
  And estou na página "Database > Tables"
  When clico em "Nova Tabela"
  And informo o nome "products"
  And adiciono colunas:
    | Nome | Tipo | Nullable | Default |
    | id | uuid | false | gen_random_uuid() |
    | name | varchar(255) | false | |
    | price | decimal(10,2) | false | |
    | created_at | timestamp | true | now() |
  And clico em "Criar Tabela"
  Then a tabela "products" é criada no banco do projeto
  And vejo a tabela listada com status "Ativa"
```

```gherkin
Scenario: Criar tabela com coluna JSONB
  Given estou autenticado como admin do projeto
  When crio uma tabela "users"
  And adiciono coluna "metadata" do tipo "jsonb"
  Then a tabela suporta armazenar dados JSON estruturados
```

```gherkin
Scenario: Criar tabela com coluna ARRAY
  Given estou autenticado como admin do projeto
  When crio uma tabela "posts"
  And adiciono coluna "tags" do tipo "text[]"
  Then a tabela suporta armazenar arrays de texto
```

```gherkin
Scenario: Validação de nome de tabela duplicado
  Given existe uma tabela chamada "products"
  When tento criar uma nova tabela com nome "products"
  Then vejo erro "Já existe uma tabela com este nome"
  And a tabela não é criada
```

```gherkin
Scenario: Adicionar foreign key
  Given existe uma tabela "categories"
  When crio uma tabela "products"
  And adiciono coluna "category_id" do tipo "uuid"
  And defino foreign key para "categories.id"
  Then a relação é criada com sucesso
```

---

## Technical Notes

### Tipos PostgreSQL Suportados
| Categoria | Tipos |
|-----------|-------|
| Numéricos | `integer`, `bigint`, `decimal`, `real` |
| Texto | `varchar(n)`, `text`, `char(n)` |
| Booleano | `boolean` |
| Data/Hora | `timestamp`, `date`, `time` |
| UUID | `uuid` |
| JSON | `jsonb`, `json` |
| Arrays | `text[]`, `integer[]`, `uuid[]` |
| Especiais | `inet`, `cidr`, `macaddr` |

### Arquitetura
```
Frontend (Vue 3)
    │
    ▼
TableBuilderController
    │
    ▼
SchemaBuilderService
    │
    ▼
DynamicMigrationService ──► PostgreSQL
```

### Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/system/projects/{project}/tables` | Listar tabelas |
| POST | `/system/projects/{project}/tables` | Criar tabela |
| GET | `/system/projects/{project}/tables/{table}` | Detalhes da tabela |
| DELETE | `/system/projects/{project}/tables/{table}` | Dropar tabela |

### Files to Create
```
app/
├── Http/Controllers/System/TableBuilderController.php
├── Http/Requests/CreateTableRequest.php
├── Services/SchemaBuilderService.php
├── Services/DynamicMigrationService.php
├── DTOs/TableDefinitionDTO.php
├── DTOs/ColumnDefinitionDTO.php
└── Enums/PostgresTypeEnum.php

resources/js/Pages/System/Tables/
├── Index.vue
├── Create.vue
└── Show.vue
```

### Database Schema
```sql
-- Metadados das tabelas (no banco system)
CREATE TABLE system_tables (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES system_projects(id),
    name VARCHAR(63) NOT NULL,
    schema JSONB NOT NULL, -- definição completa das colunas
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(project_id, name)
);
```

---

## Security Considerations

- [ ] Apenas admins do projeto podem criar tabelas
- [ ] Validar nome da tabela contra SQL injection
- [ ] Limite de tabelas por projeto (configurável)
- [ ] Nomes reservados bloqueados (`pg_`, `system_`)
