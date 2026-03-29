# DockaBase - BaaS Platform (Backend as a Service)

## Visão Geral

DockaBase é um clone funcional e simplificado do Supabase, construído com Laravel 13. O objetivo é criar uma plataforma BaaS que fornece:

- **Database Manager** - Interface visual para gerenciar múltiplos databases PostgreSQL
- **Schema Builder** - Interface visual para criar tabelas e colunas
- **Auth Provider** - Autenticação multi-tenant para usuários finais (OTP-based)
- **Dynamic REST API** - API auto-gerada a partir do schema do banco
- **Realtime** - Websockets com LISTEN/NOTIFY do PostgreSQL
- **Storage** - Abstração S3/MinIO com políticas de acesso

## Estratégia de Distribuição

**Modelo: Single-Tenant Self-Hosted com Múltiplos Databases**

Cada instância do DockaBase pode gerenciar **múltiplos databases PostgreSQL** na mesma instalação (ex: `dev`, `staging`, `prod`).

### Opções de Deploy

| Modalidade | Descrição |
|------------|-----------|
| **Self-Hosted (Free)** | Cliente roda localmente via Docker |
| **Cloud Managed (Paid)** | Subimos em servidor dedicado para o cliente |

### Implicações Arquiteturais

- **Múltiplos databases por instância:** Uma instalação pode gerenciar N databases
- **Features híbridas:** Features globais com override por database
- **Rotas simplificadas:** `/system/features` ao invés de `/system/projects/{id}/features`
- **Isolamento por database:** Cada database tem suas tabelas, schemas e dados

### Comparação

| Aspecto | DockaBase | Supabase |
|---------|-----------|----------|
| Modelo | Single-tenant, múltiplos databases | Multi-tenant |
| Isolamento | Por database | Por projeto |
| Features | Globais + override por database | Por projeto |

## Stack Tecnológica

| Componente | Tecnologia |
|------------|------------|
| Backend | Laravel 13+ / PHP 8.4+ |
| Performance | Laravel Octane (Swoole) |
| Database | PostgreSQL 16+ |
| Cache | Redis 7+ |
| Queue | RabbitMQ 7+ |
| Frontend | Inertia.js + Vue 3 + Pinia + TypeScript (strict) |
| UI | shadcn-vue + Tailwind CSS 4.x |
| Feature Flags | Laravel Pennant |
| RBAC | Spatie Permission |
| Storage | MinIO (Self-hosted S3) |
| AI Tools | Laravel MCP Server |
| Observabilidade | Laravel Pulse |

## Arquitetura de Acesso - Dois Níveis

O DockaBase separa claramente dois níveis de acesso:

### Nível 1: Aplicação (Spatie RBAC)

Para **System Users** que acessam o painel administrativo do DockaBase.

| Role | Descrição | Permissões |
|------|-----------|------------|
| `super-admin` | Acesso total | Todas as permissões |
| `admin` | Gerencia instância | create-database, manage-credentials, manage-features |
| `manager` | Gerencia usuários | manage-users, view-databases |
| `user` | Usuário comum | view-databases |

**Uso:** Controle de quem pode fazer o que no painel administrativo.

### Nível 2: API (Credentials)

Para **End Users** que consomem a API (frontend-only apps, DBeaver, etc).

```
┌─────────────────────────────────────────────────────────────┐
│                      CREDENTIAL                              │
│  Name: "Dev Team"                                           │
│  Permission: read-write                                     │
├─────────────────────────────────────────────────────────────┤
│  Users:              │  Databases:                          │
│  • alice@company     │  • dev (read-write)                  │
│  • bob@company       │  • staging (read-write)              │
└─────────────────────────────────────────────────────────────┘
```

| Permissão | Descrição | Operações |
|-----------|-----------|-----------|
| `read` | Apenas leitura | GET |
| `write` | Apenas escrita | POST, PUT, PATCH, DELETE |
| `read-write` | Leitura e escrita | Todas |

**Fluxo de Criação:**
1. Admin cria **Credential** com permissão (R, W, RW) e atrela usuários
2. Admin cria **Database** e atrela credentials
3. Usuários das credentials ganham acesso ao database

**Importante:**
- Usuários são atrelados à Credential na criação da Credential
- Credentials são atreladas ao Database na criação do Database
- Um usuário pode ter múltiplas credentials (access groups diferentes)
- Uma credential pode ser atrelada a múltiplos databases

### Modelo de Dados

```
users ──┐
        ├── credential_user (pivot) ──► credentials
        │                                    │
        │                                    ├── permission: enum(read, write, read-write)
        │                                    │
        └────────────────────────────────────┴──► credential_database (pivot) ──► databases
```

### Endpoints por Nível

| Nível | Prefixo | Autenticação | Uso |
|-------|---------|--------------|-----|
| Aplicação | `/system/*` | Session (web) | Painel admin |
| API | `/api/v1/{database}/*` | Sanctum (token) | End users |

## Design System

### UI Components
Usar **shadcn-vue** (https://www.shadcn-vue.com/) - componentes copiáveis, não dependência npm.

### Regra 10-30-60 (Proporção de Cores)
- **10%** - Cores de destaque (Primary, Accent) - Botões, links
- **30%** - Cores secundárias (Secondary, Neutral) - Textos, ícones
- **60%** - Cores de fundo (Base-100, Base-200, Base-300) - Backgrounds, cards

### Cores Principais
| Nome | Light | Dark |
|------|-------|------|
| Primary | `#2563eb` | `#3b82f6` |
| Background | `#f8fafc` | `#0f172a` |
| Card | `#f1f5f9` | `#1e293b` |
| Border | `#e2e8f0` | `#334155` |

### Cores de Status
- **Info:** `#0ea5e9` / **Success:** `#22c55e` / **Warning:** `#f59e0b` / **Error:** `#ef4444`

## Arquitetura

### Estrutura de Diretórios

```
app/
├── Http/
│   ├── Controllers/     # Controllers enxutos
│   ├── Middleware/      # RLS, Features, Auth
│   └── Requests/        # FormRequest para validação
├── DTOs/                # Data Transfer Objects (imutáveis, tipados)
├── Models/              # Models gordas com Scopes e Relationships
├── Services/            # Lógica de negócio
├── Strategies/          # Comportamentos variáveis (filtros, ordenação)
├── Policies/            # Autorização (RBAC)
├── Resources/           # Transformação JSON
├── Enums/               # Enums com métodos
├── Traits/              # Traits compartilhados
└── Features/            # Laravel Pennant Feature Flags
```

### Rotas
- `/system/*` → Painel administrativo
- `/api/v1/*` → Dynamic REST API
- `/auth/v1/*` → Autenticação OTP
- `/realtime/v1/*` → Websockets
- `/storage/v1/*` → Storage endpoints

### Camadas da Aplicação

```
Request → FormRequest (validação) → Controller (orquestração) → Service (regras) → Resource
                                        ↓
                              Model / DTO / Events / Transactions
```

**Fluxo:** Controller recebe Request validado → busca dados via Model → aplica regras via Service → persiste/transaciona → dispara eventos → retorna Resource.

### Responsabilidades por Camada

| Camada | Responsabilidade |
|--------|------------------|
| **Controller** | Orquestração: busca dados, chama Service, transações, eventos, retorna Resource |
| **FormRequest** | Validação de entrada + Autorização via Policy |
| **DTO** | Imutável, tipado, transferência entre camadas |
| **Service** | Regras de negócio puras: entrada → processamento → saída |
| **Model** | Property Hooks, Scopes `scopeOf{Entity}()`, Relationships, Traits, SoftDeletes |
| **Resource** | Transforma Model em JSON com metadados |

### Padrão de Scopes

**Convenção obrigatória:** `scopeOf{Entity}($query, $value)` para filtros reutilizáveis.

- `scopeOfProject($query, $projectId)`
- `scopeOfStatus($query, $status)`
- `scopeOfUser($query, $userId)`

### Services (Regras de Negócio Puras)

**Services recebem dados, aplicam regras, retornam resultados.** Nada de orquestração.

- Não buscam dados do banco
- Não disparam eventos
- Não executam transações
- Apenas: entrada → regras de negócio → saída

### Controllers (Orquestração)

Controllers fazem a orquestração completa:

- Buscam dados via Model/Route Model Binding
- Chamam Services para regras de negócio
- Executam transações
- Disparam eventos
- Retornam Resources

### Route Model Binding

Usar Route Model Binding em todas as rotas que recebem IDs.

### Soft Delete

Todas as models que precisam de exclusão lógica devem usar SoftDeletes.

## Fases de Desenvolvimento

### Fase 1: Core & Infraestrutura ✅
- [x] Setup Laravel 13 + PHP 8.4
- [x] Configurar Octane, PostgreSQL, Redis, RabbitMQ
- [x] Setup Inertia.js + Vue 3 + shadcn-vue
- [x] Configurar Pennant + Spatie Permission
- [x] Feature Flag Manager (UI + API)

### Fase 2: Database Creator
- [x] Model `Database` e migrations
- [x] Model `Credential` e relacionamentos
- [x] UI para criar databases
- [x] UI para criar credentials e atrelar usuários
- [x] Atrelar credentials ao database na criação
- [x] Feature flag: `database-creator`
- [x] Async database creation com real-time progress

### Async Database Creation

Sistema de criação assíncrona de databases com feedback em tempo real.

| Componente | Arquivo | Descrição |
|------------|---------|-----------|
| Job | `app/Jobs/CreateDatabaseJob.php` | Executa criação do database |
| Events | `app/Events/DatabaseStepUpdated.php` | Broadcast step update |
| Events | `app/Events/DatabaseCreated.php` | Broadcast completion |
| Events | `app/Events/DatabaseFailed.php` | Broadcast failure |
| Service | `app/Services/DatabaseProvisioningService.php` | Lógica de provisioning |
| Enum | `app/Enums/DatabaseCreationStepEnum.php` | 7 steps de criação |
| Notification | `app/Notifications/DatabaseCreatedNotification.php` | Notificação persistente |
| Model | `app/Models/Notification.php` | Sistema de notificações |
| Model | `app/Models/DatabaseSchemaHistory.php` | Histórico de schema |

#### Database Creation Steps

| Step | Valor | Progress |
|------|-------|----------|
| 1 | `validating` | 14% |
| 2 | `creating` | 28% |
| 3 | `configuring` | 42% |
| 4 | `migrating` | 56% |
| 5 | `permissions` | 71% |
| 6 | `testing` | 85% |
| 7 | `ready` | 100% |

#### WebSocket Events

| Canal | Event | Payload |
|-------|-------|---------|
| `database.{id}` | `step.updated` | `{ step, progress, database }` |
| `database.{id}` | `database.created` | `{ database }` |
| `database.{id}` | `database.failed` | `{ status, error, database }` |

#### Frontend Components

| Componente | Arquivo | Descrição |
|------------|---------|-----------|
| Timeline | `resources/js/components/CreationTimeline.vue` | Steps horizontais |
| NotificationCenter | `resources/js/components/NotificationCenter.vue` | Bell + dropdown |
| Toast | `resources/js/composables/useToast.ts` | Sonner wrapper |
| Echo | `resources/js/composables/echo.ts` | WebSocket client |

### Fase 3: Schema Builder
- [ ] Interface visual para criar tabelas
- [ ] Migrations dinâmicas
- [ ] Suporte a tipos PostgreSQL (UUID, JSONB, Arrays)
- [ ] Feature flag: `schema-builder`

### Fase 4: Table Manager
- [ ] CRUD de dados com interface tipo planilha
- [ ] Filtros e ordenação
- [ ] Export/Import CSV
- [ ] Feature flag: `table-manager`

### Fase 5: Dynamic REST API
- [ ] Dynamic Router `/api/v1/{database}/{table}`
- [ ] Query Parser (filtros tipo `?age=gte.18`)
- [ ] Validação dinâmica baseada no schema
- [ ] Autenticação via Credentials
- [ ] Feature flag: `dynamic-api`

### Fase 6: Autenticação OTP
- [ ] Login sem senha via código
- [ ] JWT para end users
- [ ] Rate limiting
- [ ] Feature flag: `otp-auth`

### Fase 7: Realtime
- [ ] Laravel Echo + Redis
- [ ] Postgres LISTEN/NOTIFY + Triggers
- [ ] Feature flag: `realtime`

### Fase 8: Storage
- [ ] MinIO com Buckets
- [ ] Políticas de acesso via RLS
- [ ] Feature flag: `storage`

## Padrões e Convenções

### Frontend (Vue 3 + TypeScript)

- **TypeScript strict mode:** `strict: true` no tsconfig
- **Composition API:** Usar `<script setup lang="ts">`
- **Tipagem explícita:** Props, emits, refs, composables
- **shadcn-vue:** Componentes UI copiáveis
- **Ziggy:** Rotas tipadas (configurar para não expor no console)
- **Validação:** Delegada ao FormRequest do Laravel
- **Helper `__()`:** Util para JSON/formatting igual ao `__()` do Laravel

### Helpers Laravel

- **`__()`:** Usar para strings JSON/formatting (ex: `__('messages.welcome')`)

### Código PHP 8.4
- **Property Hooks:** Usar para getters/setters
- **Type Hints:** Obrigatório em todos os métodos
- **Strict Types:** `declare(strict_types=1);` em todos os arquivos
- **PSR-12:** Seguir padrão de codificação

### Strategies (Comportamentos Variáveis)
- FilterStrategy, SelectStrategy, OrderStrategy
- Cada strategy implementa interface com método `apply()`

### Enums (Type Safety)
- Backed enums com métodos
- `FilterOperator`: EQ, NE, GT, GTE, LT, LTE, LIKE, IN
- Cada caso com método `apply()`

### Traits (Comportamentos Compartilhados)
- `HasProject` - Scope para filtrar por projeto
- `Cacheable` - Cache keys
- `InteractsWithRedis` - Helpers Redis

### Nomenclatura
- **Tabelas de sistema:** Prefixo `system_`
- **Tabelas de projeto:** Prefixo `{project_uuid}_`
- **Models:** Domain-based (`App\Domain\Auth\Models\EndUser`)

### IDs

- **KSUID:** Padrão para entidades (string-based, compatível com JS, ordenável, distribuído)
- **Incremental ID:** Apenas para coisas insignificantes (logs, pivot tables, etc)

## Testes

### Metodologia

**TDD + Extreme Programming** - Test-driven development com ciclos curtos de feedback.

### Princípios Fundamentais
1. **Cada teste deve poder falhar** - Se remover a implementação, o teste DEVE falhar
2. **Teste comportamento, não implementação** - Foque no QUE faz, não COMO
3. **Use dados realistas** - Evite "foo", "bar", "test@test.com"
4. **Não teste código que você não escreveu** - Laravel já testa Eloquent, Validation

### Ambiente
- Dois databases: `dockabase` (dev) e `dockabase_testing` (testes)
- Usar trait `RefreshDatabase` para banco limpo entre testes

### O que DEVE ser testado (70-80% cobertura)
| Tipo | Por quê |
|------|---------|
| Services | Lógica de negócio complexa |
| Strategies | Comportamentos variáveis |
| Policies | Regras de autorização críticas |
| Enums com métodos | Lógica de transformação |
| Scopes complexos | Queries customizadas |

### O que NÃO precisa de teste unitário
- Controllers simples (testar via Feature test)
- Eloquent relationships (Laravel já testa)
- Validação básica (Laravel já testa)
- Getters/Setters simples

### Estrutura de Diretórios
```
tests/
├── Unit/Domain/{Api,Auth,Database}/
│   ├── Services/
│   └── Strategies/
└── Feature/{Api,Auth,RLS}/
```

## RBAC com Spatie Permission

### Setup
- Model `EndUser` usa trait `HasRoles`
- Guard separado: `api` para end users
- Roles e Permissions têm `project_id` para multi-tenant

### Roles Padrão
- `super-admin` - Acesso total
- `admin` - Gerencia projeto
- `manager` - Gerencia usuários
- `user` - Usuário comum

### Permissões por Tabela
Formato: `{tabela}.{operacao}` (ex: `posts.select`, `posts.insert`)

## Feature Flags (Laravel Pennant)

### Modelo Híbrido

Features são definidas globalmente, mas cada database pode ter overrides.

```
Features Globais (config/features.php)
├── database-creator: available ✓
├── schema-builder: available ✓
├── dynamic-api: available ✓
├── realtime: available ✓
├── storage: available ✓
├── otp-auth: available ✓
├── database-encryption: available ✓
├── automated-backups: available ✓
├── rls: available ✓
└── advanced-rbac: available ✓

Database: prod
├── dynamic-api: enabled (usa default)
├── realtime: enabled (usa default)
└── storage: disabled (override local)

Database: dev
├── dynamic-api: enabled (usa default)
├── realtime: disabled (override local)
└── storage: disabled (override local)
```

### Features Disponíveis

| Feature | Descrição | Prioridade |
|---------|-----------|------------|
| `database-creator` | Interface para criar databases PostgreSQL | P0 |
| `schema-builder` | Interface visual para criar tabelas/colunas | P0 |
| `table-manager` | CRUD de dados com interface tipo planilha | P0 |
| `dynamic-api` | API REST dinâmica auto-gerada | P0 |
| `realtime` | Websockets com LISTEN/NOTIFY | P1 |
| `storage` | Storage MinIO com buckets | P1 |
| `otp-auth` | Autenticação OTP (login sem senha) | P1 |
| `database-encryption` | Criptografia com pgcrypto | P2 |
| `automated-backups` | Backups automáticos | P2 |
| `rls` | Row Level Security | P2 |
| `advanced-rbac` | RBAC avançado | P2 |

### Estratégias de Rollout

| Estratégia | Descrição | Uso |
|------------|-----------|-----|
| `inactive` | Feature desativada | Default |
| `percentage` | X% dos usuários | Rollout gradual |
| `users` | Lista específica | Beta testers |
| `all` | 100% dos usuários | General Availability |

### Integração com Pennant

```php
// FeatureServiceProvider define features dinamicamente
Feature::define('realtime', function (User $user) use ($featureName) {
    return $this->resolveFeature($user, $featureName);
});

// FeatureFlagService purga cache do Pennant ao atualizar settings
Feature::purge($featureName);
```

## RLS - Row Level Security

### Middleware
- Define contexto PostgreSQL: `user_id`, `project_id`, `user_roles`
- Aplica RlsScope baseado nas permissões

### Comportamento por Role
- **Admin:** Vê tudo
- **Editor:** Vê dados do projeto
- **User:** Vê apenas próprios dados

## Query Syntax (API Dinâmica)

Segue sintaxe Supabase/PostgREST:
- `GET /api/v1/users?id=eq.1&select=id,name`
- `GET /api/v1/users?age=gte.18&order=created_at.desc`
- `POST /api/v1/users` com JSON body
- `PATCH /api/v1/users?id=eq.1`
- `DELETE /api/v1/users?id=eq.1`

## Realtime com Postgres NOTIFY

- Triggers PostgreSQL detectam mudanças
- `pg_notify()` envia payload JSON
- Laravel listener dispara eventos para Echo Client

## Features Previstas

### Database
- **One DB per Project:** Database isolado `dockabase_{project_uuid}`
- **Encryption:** pgcrypto para colunas sensíveis
- **Backups:** Diários automáticos com retenção configurável

### Realtime
- **WebSocket Broadcasts:** Canais por tabela
- **PostgreSQL LISTEN/NOTIFY:** Triggers automáticos
- **Filtros:** Subscribe com condições

### Auth (OTP)
- **Login sem senha:** Códigos de 6 dígitos
- **Validade:** 5-15 minutos
- **Rate limiting:** Prevenir abuso

### Storage
- **Buckets:** Containers lógicos (pasta no MinIO)
- **Private Files:** URLs temporárias com expiração
- **Políticas:** Acesso via RLS

### API
- **REST:** CRUD automático por tabela
- **SDKs:** JavaScript/TypeScript e PHP

## Próximos Passos

1. Criar estrutura base do projeto Laravel 13
2. Implementar DynamicController inicial
3. Configurar Inertia.js com dashboard básico
4. Implementar cache de schema com Redis
5. Criar primeira tabela dinâmica funcional
6. Configurar Spatie Permission para RBAC
7. Implementar RLS integrado com roles e permissões

## Roadmap Futuro (P3+)

### MCP Server Integration

**Model Context Protocol (MCP)** é um protocolo para conectar AI assistants com fontes de dados externas. O DockaBase terá um MCP Server nativo.

#### Objetivo
Permitir que AI assistants (Claude, GPT, etc.) se conectem diretamente ao DockaBase para:
- Consultar schema do database
- Executar queries (com permissões adequadas)
- Gerenciar dados via linguagem natural
- Automação de tarefas

#### Arquitetura Planejada
```
┌─────────────────┐     MCP Protocol      ┌─────────────────┐
│  Claude / AI    │ ◄───────────────────► │  DockaBase MCP  │
│  Assistant      │                       │     Server      │
└─────────────────┘                       └────────┬────────┘
                                                   │
                                          ┌────────▼────────┐
                                          │   DockaBase     │
                                          │   PostgreSQL    │
                                          └─────────────────┘
```

#### Recursos do MCP Server
| Recurso | Descrição |
|---------|-----------|
| `database://schema` | Lista tabelas, colunas e tipos |
| `database://query` | Executa queries SELECT (read-only) |
| `database://credentials` | Lista credenciais do usuário |
| `database://features` | Status das feature flags |

#### Ferramentas do MCP Server
| Tool | Descrição |
|------|-----------|
| `query` | Executa SQL com validação de permissão |
| `insert` | Insere dados em tabela |
| `update` | Atualiza dados com filtros |
| `delete` | Remove dados com filtros |
| `describe_table` | Retorna schema de uma tabela |

#### Implementação
- Pacote: `laravel-mcp-server` ou custom
- Endpoint: `/mcp` (protocolo JSON-RPC)
- Autenticação: Via API Key da Credential
- Rate Limiting: Por credential/user

### Claude Plugin / Extension

**Objetivo:** Criar uma integração nativa com Claude (similar ao Supabase MCP ou Firebase Extension).

#### Funcionalidades Planejadas
1. **Conexão Direta**: Configurar DockaBase como data source no Claude
2. **Schema Awareness**: Claude entende a estrutura do database
3. **Query Generation**: Gerar queries SQL a partir de linguagem natural
4. **Data Exploration**: Explorar dados via conversação
5. **Migration Suggestion**: Sugerir alterações de schema

#### Fluxo de Uso
```
User: "Mostre os últimos 10 pedidos do cliente X"

Claude: [Conecta ao DockaBase MCP]
        [Consulta schema da tabela pedidos]
        [Executa query segura]

Response:
| id | cliente | valor | status | criado_em |
|----|---------|-------|--------|-----------|
| 42 | X       | R$ 99 | paid   | 2024-01-15|
...
```

#### Configuração no Claude
```json
{
  "mcpServers": {
    "dockabase": {
      "command": "dockabase-mcp",
      "args": ["--url", "https://your-dockabase.com", "--key", "db_xxx"]
    }
  }
}
```

### Comparação com Concorrentes

| Feature | DockaBase | Supabase | Firebase |
|---------|-----------|----------|----------|
| MCP Server | Planejado | ✅ Disponível | Extension |
| Claude Plugin | Planejado | ✅ Disponível | ❌ |
| Natural Language Queries | Planejado | ✅ | ❌ |
| Schema Awareness | Planejado | ✅ | Parcial |

### Prioridade
**P3** - Implementar após features core (API, Auth, Realtime, Storage) estarem estáveis.

---

*Este arquivo serve como contexto central para o desenvolvimento do DockaBase.*
