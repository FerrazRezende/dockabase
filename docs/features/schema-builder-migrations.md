# Schema Builder - Migrations Dinâmicas

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 2 |
| Feature Flag | `database-manager` |
| Dependencies | Schema Builder - Criar Tabelas |

---

## User Story

**As a** desenvolvedor do projeto
**I want to** modificar a estrutura de tabelas existentes através de migrations automáticas
**So that** posso evoluir o schema do banco sem perder dados existentes

---

## Acceptance Criteria

```gherkin
Scenario: Adicionar coluna a tabela existente
  Given existe uma tabela "products" com colunas [id, name, price]
  When adiciono a coluna "description" do tipo "text"
  Then a migration é gerada automaticamente
  And a coluna é adicionada sem perda de dados
  And o histórico de migrations é atualizado
```

```gherkin
Scenario: Remover coluna com confirmação
  Given existe uma tabela "users" com coluna "legacy_field"
  When solicito remover a coluna "legacy_field"
  Then vejo aviso "Esta ação pode causar perda de dados"
  And preciso confirmar a ação
  And a coluna é removida após confirmação
```

```gherkin
Scenario: Alterar tipo de coluna
  Given existe uma coluna "price" do tipo "integer"
  When altero o tipo para "decimal(10,2)"
  Then o sistema valida se a conversão é segura
  And os dados existentes são convertidos automaticamente
```

```gherkin
Scenario: Rollback de migration
  Given executei uma migration que adicionou a coluna "stock"
  When solicito rollback da última migration
  Then a coluna "stock" é removida
  And o estado anterior é restaurado
```

```gherkin
Scenario: Histórico de migrations
  Given existem 5 migrations executadas
  When acesso a página "Database > Migrations"
  Then vejo lista com todas as migrations
  And cada migration mostra: nome, status, data de execução
  And posso visualizar o SQL gerado
```

```gherkin
Scenario: Migration bloqueada por dependência
  Given existe uma tabela "orders" com foreign key para "users"
  When tento remover a tabela "users"
  Then vejo erro "Tabela possui dependências: orders"
  And a migration é bloqueada
```

---

## Technical Notes

### Tipos de Operações
| Operação | Suportado | Destructive |
|----------|-----------|-------------|
| ADD COLUMN | ✅ | Não |
| DROP COLUMN | ✅ | Sim |
| ALTER COLUMN TYPE | ✅ | Parcial |
| RENAME COLUMN | ✅ | Não |
| ADD CONSTRAINT | ✅ | Não |
| DROP CONSTRAINT | ✅ | Não |
| ADD INDEX | ✅ | Não |
| DROP INDEX | ✅ | Não |
| RENAME TABLE | ✅ | Não |
| DROP TABLE | ✅ | Sim |

### Arquitetura
```
Frontend (Vue 3)
    │
    ▼
MigrationController
    │
    ▼
MigrationService
    │
    ├──► MigrationGenerator (gera SQL)
    │
    └──► MigrationExecutor (executa no banco)
              │
              └──► PostgreSQL
```

### Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/system/projects/{project}/migrations` | Listar migrations |
| POST | `/system/projects/{project}/migrations` | Criar migration |
| POST | `/system/projects/{project}/migrations/{id}/rollback` | Rollback |
| GET | `/system/projects/{project}/migrations/{id}/sql` | Ver SQL gerado |

### Files to Create
```
app/
├── Http/Controllers/System/MigrationController.php
├── Http/Requests/CreateMigrationRequest.php
├── Services/MigrationService.php
├── Services/MigrationGeneratorService.php
├── Services/MigrationExecutorService.php
├── DTOs/MigrationDefinitionDTO.php
└── Enums/MigrationOperationEnum.php

resources/js/Pages/System/Migrations/
├── Index.vue
├── Create.vue
└── Show.vue
```

### Database Schema
```sql
-- Histórico de migrations (no banco do projeto)
CREATE TABLE system_migrations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES system_projects(id),
    batch INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    operation VARCHAR(50) NOT NULL, -- 'add_column', 'drop_column', etc
    table_name VARCHAR(63) NOT NULL,
    sql_up TEXT NOT NULL,
    sql_down TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending', -- pending, executed, failed, rolled_back
    executed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(project_id, name)
);
```

---

## Security Considerations

- [ ] Apenas admins do projeto podem executar migrations
- [ ] Operações destrutivas requerem confirmação extra
- [ ] Backup automático antes de migrations destrutivas
- [ ] Log de todas as operações para auditoria
