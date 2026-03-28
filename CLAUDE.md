# DockaBase - BaaS Platform (Backend as a Service)

## Visão Geral

DockaBase é um clone funcional e simplificado do Supabase, construído com Laravel 13. O objetivo é criar uma plataforma BaaS que fornece:

- **Database Manager** - Interface visual para gerenciar tabelas PostgreSQL
- **Auth Provider** - Autenticação multi-tenant para usuários finais (OTP-based)
- **Dynamic REST API** - API auto-gerada a partir do schema do banco
- **Realtime** - Websockets com LISTEN/NOTIFY do PostgreSQL
- **Storage** - Abstração S3/MinIO com políticas de acesso

## Estratégia de Distribuição

**Modelo: Single-Tenant Self-Hosted**

Cada instância do DockaBase é **individual e isolada** - não há compartilhamento de dados entre clientes.

### Opções de Deploy

| Modalidade | Descrição |
|------------|-----------|
| **Self-Hosted (Free)** | Cliente roda localmente via Docker |
| **Cloud Managed (Paid)** | Subimos em servidor dedicado para o cliente |

### Implicações Arquiteturais

- **Sem multi-tenancy:** Features são globais por instância
- **Sem Project model:** Não há necessidade de isolar dados por projeto
- **Rotas simplificadas:** `/system/features` ao invés de `/system/projects/{id}/features`
- **Banco isolado:** Cada cliente tem seu próprio PostgreSQL

### Comparação

| Aspecto | DockaBase | Supabase |
|---------|-----------|----------|
| Modelo | Single-tenant | Multi-tenant |
| Isolamento | Por instância | Por projeto |
| Features | Globais | Por projeto |

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

### Fase 1: Core & Infraestrutura
- [ ] Setup Laravel 13 + PHP 8.4
- [ ] Configurar Octane, PostgreSQL, Redis, RabbitMQ
- [ ] Setup Inertia.js + Vue 3 + shadcn-vue
- [ ] Configurar Pennant + Spatie Permission

### Fase 2: Database & Schema Builder
- [ ] Interface visual para criar tabelas
- [ ] Migrations dinâmicas
- [ ] Suporte a tipos PostgreSQL (UUID, JSONB, Arrays)

### Fase 3: Autenticação Multi-tenant & RBAC
- [ ] Separar System Users vs End Users
- [ ] JWT para end users
- [ ] OTP Auth (login sem senha)
- [ ] Spatie Permission + RLS integrado

### Fase 4: API Dinâmica
- [ ] Dynamic Router `/api/v1/{table}`
- [ ] Query Parser (filtros tipo `?age=gte.18`)
- [ ] Validação dinâmica baseada no schema

### Fase 5: Realtime
- [ ] Laravel Echo + Redis
- [ ] Postgres LISTEN/NOTIFY + Triggers

### Fase 6: Storage
- [ ] MinIO com Buckets
- [ ] Políticas de acesso via RLS

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

| Feature | Descrição |
|---------|-----------|
| `dynamic-api` | API REST dinâmica |
| `realtime` | Websockets |
| `storage` | Storage MinIO |
| `otp-auth` | Autenticação OTP |
| `database-encryption` | Criptografia de dados |
| `automated-backups` | Backups automáticos |
| `rls` | Row Level Security |
| `advanced-rbac` | RBAC avançado |

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

---

*Este arquivo serve como contexto central para o desenvolvimento do DockaBase.*
