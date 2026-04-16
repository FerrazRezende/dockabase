# Schema Builder - Criar Tabelas

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 3 |
| Feature Flag | `schema-builder` |
| Dependencies | Database Creator (Phase 2), Schema Visualization |

---

## User Story

**As a** desenvolvedor do projeto
**I want to** criar tabelas através de um wizard de duas etapas: definição de colunas e validações visuais
**So that** posso definir a estrutura e as regras de validação dos dados sem escrever SQL ou código manualmente

---

## Wizard de Criação (2 Steps)

### Step 1: Definição de Colunas

O usuário define a estrutura física da tabela — nome, tipo, nullable, FK, defaults.

```
┌─────────────────────────────────────────────────────────┐
│  Nova Tabela                                    [Step 1/2] │
│                                                          │
│  Nome da tabela: [products        ]  Schema: [public ▼]  │
│                                                          │
│  Colunas:                                                │
│  ┌──────┬──────────┬──────┬─────────┬──────┬──────────┐  │
│  │ Nome │ Tipo     │ Null │ Default │ FK   │ Actions  │  │
│  ├──────┼──────────┼──────┼─────────┼──────┼──────────┤  │
│  │ id   │ uuid     │  ✗   │ gen_r.. │  —   │ [↑][↓][✕]│  │
│  │ name │ varchar  │  ✗   │         │  —   │ [↑][↓][✕]│  │
│  │ price│ decimal  │  ✗   │  0.00   │  —   │ [↑][↓][✕]│  │
│  │ cat_id│ uuid    │  ✓   │         │cat.id│ [↑][↓][✕]│  │
│  └──────┴──────────┴──────┴─────────┴──────┴──────────┘  │
│  [+ Adicionar Coluna]                                    │
│                                                          │
│                                    [Cancelar] [Próximo →] │
└─────────────────────────────────────────────────────────┘
```

Campos por coluna:
| Campo | Descrição | Obrigatório |
|-------|-----------|-------------|
| Nome | Nome da coluna | Sim |
| Tipo | Tipo PostgreSQL (select com categorias) | Sim |
| Nullable | Permite NULL? | Sim (default: false) |
| Default | Valor padrão | Não |
| FK | Foreign key para tabela.coluna | Não |

### Step 2: Validações (No-Code / Low-Code)

O usuário define regras de validação para cada coluna usando presets visuais que traduzem Laravel FormRequest rules em UI.

```
┌─────────────────────────────────────────────────────────┐
│  Nova Tabela - Validações                       [Step 2/2] │
│                                                          │
│  Coluna: [name ▼]  varchar(255)                          │
│                                                          │
│  Validações:                                             │
│  ┌────────────────────────────────────────────────────┐  │
│  │ ☑ Required                                         │  │
│  │ ☑ Min Length    [3]                                │  │
│  │ ☑ Max Length    [255]                              │  │
│  │ ☐ Regex        [/^[a-zA-Z]+$/]                    │  │
│  │ ☐ Unique       (na tabela atual)                  │  │
│  │ ☑ Only Letters                                     │  │
│  └────────────────────────────────────────────────────┘  │
│                                                          │
│  Coluna: [price ▼]  decimal(10,2)                        │
│                                                          │
│  Validações:                                             │
│  ┌────────────────────────────────────────────────────┐  │
│  │ ☑ Required                                         │  │
│  │ ☑ Min Value     [0]                                │  │
│  │ ☑ Max Value     [999999.99]                        │  │
│  │ ☐ Must be integer                                 │  │
│  └────────────────────────────────────────────────────┘  │
│                                                          │
│                                [← Voltar] [Criar Tabela]  │
└─────────────────────────────────────────────────────────┘
```

### Presets de Validação (Laravel → UI)

Cada preset é uma tradução visual de uma rule do Laravel FormRequest:

| Preset (UI) | Laravel Rule | Tipos aplicáveis |
|-------------|-------------|-----------------|
| Required | `required` | Todos |
| Min Length | `min:x` | String, Text |
| Max Length | `max:x` | String, Text |
| Min Value | `min:x` | Numéricos |
| Max Value | `max:x` | Numéricos |
| Must be integer | `integer` | Numéricos |
| Must be numeric | `numeric` | Todos |
| Regex pattern | `regex:pattern` | String, Text |
| Unique in table | `unique:table,column` | Todos |
| Exists in table | `exists:table,column` | Todos |
| Must be email | `email` | String |
| Must be URL | `url` | String |
| Must be UUID | `uuid` | String |
| Must be date | `date` | Timestamp, Date |
| Must be boolean | `boolean` | Boolean |
| In list | `in:a,b,c` | String, Enum |
| Only letters | `alpha` | String |
| Letters + numbers | `alpha_num` | String |
| Letters + dash/underscore | `alpha_dash` | String |
| Confirmed (password) | `confirmed` | String |
| Different from | `different:field` | Todos |
| Same as | `same:field` | Todos |

### Como funciona por baixo

O backend armazena as validações como JSON na tabela de metadados:

```json
{
  "name": {
    "required": true,
    "min": 3,
    "max": 255,
    "alpha": true
  },
  "price": {
    "required": true,
    "min": 0,
    "max": 999999.99
  },
  "category_id": {
    "required": false,
    "exists": "categories,id"
  }
}
```

Quando a Dynamic REST API recebe um POST/PUT, ela reconstrói as rules em tempo de execução:

```php
// Tradução automática JSON → Laravel Rules
$rules = ValidationRuleMapper::toLaravelRules($columnValidations);
// Resultado: ['name' => ['required', 'min:3', 'max:255', 'alpha']]

$request->validate($rules);
```

### Futuro: Rules Customizadas

Na versão futura, o usuário poderá criar regras personalizadas através de um editor visual, equivalente a escrever uma `Rule` customizada do Laravel:

- Editor com blocos lógicos (if/then, comparadores, funções)
- Templates de rules comuns (CPF/CNPJ, telefone, etc.)
- Regras compostas (combinação de múltiplas validações)
- Salvar como preset reutilizável

Isso será especificado em uma spec separada quando chegar o momento.

---

## Acceptance Criteria

```gherkin
Scenario: Criar tabela com colunas básicas (Step 1)
  Given estou autenticado com credential de write no database
  And estou na aba Schema do database
  When clico em [+ New Table]
  And informo nome "products" no schema "public"
  And adiciono colunas:
    | Nome | Tipo | Nullable | Default |
    | id | uuid | false | gen_random_uuid() |
    | name | varchar(255) | false | |
    | price | decimal(10,2) | false | |
    | created_at | timestamp | true | now() |
  And clico em [Próximo →]
  Then sou levado ao Step 2 (Validações)
```

```gherkin
Scenario: Definir validações visuais (Step 2)
  Given completei o Step 1 com colunas definidas
  When seleciono a coluna "name"
  And marco: Required, Min Length 3, Max Length 255, Only Letters
  And seleciono a coluna "price"
  And marco: Required, Min Value 0, Max Value 999999.99
  And clico em [Criar Tabela]
  Then a tabela "products" é criada no banco
  And as validações ficam armazenadas nos metadados
  And vejo a tabela no Schema Browser
```

```gherkin
Scenario: Criar tabela com foreign key
  Given existe uma tabela "categories"
  When adiciono coluna "category_id" do tipo "uuid"
  And defino FK para "categories.id"
  Then a relação é criada com sucesso
```

```gherkin
Scenario: Pular validações (Step 2 opcional)
  Given completei o Step 1
  When clico em [Criar Tabela] sem definir nenhuma validação
  Then a tabela é criada sem validações (campos aceitam qualquer valor)
```

```gherkin
Scenario: Validação de nome duplicado
  Given existe uma tabela "products"
  When tento criar tabela com nome "products"
  Then vejo erro "Já existe uma tabela com este nome"
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
SchemaBuilderController
    │
    ├──► SchemaBuilderService (criação física da tabela)
    │         │
    │         └──► DynamicMigrationService ──► PostgreSQL
    │
    └──► ValidationRuleMapper (JSON ↔ Laravel Rules)
              │
              └──► Dynamic API usa para validar POST/PUT
```

### Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/app/databases/{database}/schema` | Lista schemas com tabelas e colunas |
| POST | `/app/databases/{database}/tables` | Criar tabela (colunas + validações) |
| GET | `/app/databases/{database}/tables/{schema}/{table}` | Dados da tabela |
| DELETE | `/app/databases/{database}/tables/{schema}/{table}` | Dropar tabela |

### Files to Create
```
app/
├── Http/Controllers/App/SchemaBuilderController.php
├── Http/Requests/CreateTableRequest.php
├── Services/SchemaBuilderService.php
├── Services/DynamicMigrationService.php
├── Services/ValidationRuleMapper.php
├── Enums/PostgresTypeEnum.php
└── Enums/ValidationPresetEnum.php

resources/js/components/schema/
├── CreateTableWizard.vue       -- Wizard wrapper (2 steps)
├── StepColumns.vue             -- Step 1: definição de colunas
├── StepValidations.vue         -- Step 2: validações visuais
├── ValidationPresets.vue       -- Checklist de presets por coluna
└── ColumnEditor.vue            -- Editor de coluna individual
```

### Database Schema (metadados)
```sql
-- Metadados das tabelas (no banco do DockaBase)
CREATE TABLE database_table_metadata (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    database_id UUID NOT NULL REFERENCES databases(id),
    schema_name VARCHAR(63) NOT NULL DEFAULT 'public',
    table_name VARCHAR(63) NOT NULL,
    columns JSONB NOT NULL,          -- definição física das colunas
    validations JSONB,               -- validações por coluna (Laravel rules format)
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(database_id, schema_name, table_name)
);
```

---

## Security Considerations

- [ ] Apenas usuários com credential de write no database podem criar tabelas
- [ ] Validar nome da tabela e colunas contra SQL injection
- [ ] Nomes reservados bloqueados (`pg_`, `system_`)
- [ ] Schema `public` é default, outros schemas requerem credential read-write
