# DockaBase - BaaS Platform (Backend as a Service)

## Visão Geral

DockaBase é um clone funcional e simplificado do Supabase, construído com Laravel 12. O objetivo é criar uma plataforma BaaS que fornece:

- **Database Manager** - Interface visual para gerenciar tabelas PostgreSQL
- **Auth Provider** - Autenticação multi-tenant para usuários finais (OTP-based)
- **Dynamic REST API** - API auto-gerada a partir do schema do banco
- **Realtime** - Websockets com LISTEN/NOTIFY do PostgreSQL
- **Storage** - Abstração S3/MinIO com políticas de acesso

## Stack Tecnológica

| Componente | Tecnologia |
|------------|------------|
| Backend | Laravel 12+ / PHP 8.4+ |
| Performance | Laravel Octane (Swoole) |
| Database | PostgreSQL 16+ |
| Cache | Redis 7+ |
| Queue | RabbitMQ 7+ |
| Frontend | Inertia.js + Vue 3 + Pinia |
| UI | shadcn-vue + Tailwind CSS 4.x |
| Feature Flags | Laravel Pennant |
| RBAC | Spatie Permission |
| Storage | MinIO (Self-hosted S3) |

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

### Estrutura MVC Laravel

```
app/
├── Http/
│   ├── Controllers/     # Controllers enxutos (System, Api, Auth, Realtime, Storage)
│   ├── Middleware/      # RLS, Features, Auth
│   └── Requests/        # FormRequest para validação
├── Models/              # Models com Scopes e Relationships
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
Controller (Enxuto)
    ↓ delega para
Service (Lógica de negócio)
    ↓ usa
Model (Robusto com Scopes)
```

**Controller:** Apenas recebe Request, valida com FormRequest, delega para Service, retorna Resource.

**Service:** Contém regras de negócio, usa Strategies para comportamentos variáveis.

**Model:** Property Hooks, Scopes reutilizáveis (`scopeOf*`), Relationships, Traits.

## Fases de Desenvolvimento

### Fase 1: Core & Infraestrutura
- [ ] Setup Laravel 12 + PHP 8.4
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

### Código PHP 8.4
- **Property Hooks:** Usar para getters/setters
- **Type Hints:** Obrigatório em todos os métodos
- **Strict Types:** `declare(strict_types=1);` em todos os arquivos
- **PSR-12:** Seguir padrão de codificação

### Controllers (Enxutos)
- Recebe Request
- Valida com FormRequest
- Delega para Service
- Retorna Resource

### Services (Lógica)
- Contém regras de negócio
- Usa Strategies para comportamentos variáveis
- Retorna Resources para respostas

### Strategies (Comportamentos Variáveis)
- FilterStrategy, SelectStrategy, OrderStrategy
- Cada strategy implementa interface com método `apply()`

### Models (Robustos)
- Property Hooks para atributos
- Scopes reutilizáveis: `scopeOfProject()`, `scopeOfStatus()`
- Traits para comportamentos compartilhados

### Requests (Validação)
- FormRequest para validação
- Método `authorize()` com Policy
- Método `rules()` com validações

### Policies (RBAC)
- Integradas com Spatie Permission
- Métodos: `viewAny`, `view`, `create`, `update`, `delete`
- Usar `$user->hasPermissionTo()` e `$user->hasRole()`

### Resources (Transformação)
- Transformer para respostas JSON
- Incluir `data` + `meta` (paginação)

### Enums (Type Safety)
- Backed enums com métodos
- `FilterOperator`: EQ, NE, GT, GTE, LT, LTE, LIKE, IN
- Cada caso com método `apply()`

### Traits (Comportamentos Compartilhados)
- `HasProject` - Scope para filtrar por projeto
- `Cacheable` - Cache keys
- `InteractsWithRedis` - Helpers Redis

### Scopes (Queries Reutilizáveis)
- Convenção: `scopeOf{Entity}($query, $value)`
- Exemplo: `Table::ofProject($projectId)->ofStatus('active')->get()`

### Nomenclatura
- **Tabelas de sistema:** Prefixo `system_`
- **Tabelas de projeto:** Prefixo `{project_uuid}_`
- **Models:** Domain-based (`App\Domain\Auth\Models\EndUser`)

## Testes

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

### Anti-Padrões a Evitar
- ❌ Teste que nunca falha (`$this->assertTrue(true)`)
- ❌ Mockar tudo e não testar nada
- ❌ Testar código do framework
- ❌ Dados irreais

### Estrutura de Diretórios
```
tests/
├── Unit/Domain/{Api,Auth,Database}/
│   ├── Services/
│   └── Strategies/
└── Feature/{Api,Auth,RLS}/
```

### Checklist de Qualidade
- [ ] O teste pode falhar se eu remover a implementação?
- [ ] Dados de teste são realistas?
- [ ] Nome do teste descreve claramente o que testa?
- [ ] Não estou testando código do framework?
- [ ] Não estou mockando o sistema sob teste?

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

### Sintaxe de Verificação
- `$user->hasRole('admin')`
- `$user->hasPermissionTo('posts.select')`
- `$user->can('posts.insert')`

## Feature Flags (Laravel Pennant)

### Features Disponíveis
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

### Uso
- `Feature::active('dynamic-api', $projectId)`
- `Feature::activate()` / `Feature::deactivate()`

## RLS - Row Level Security

### Middleware
- Define contexto PostgreSQL: `user_id`, `project_id`, `user_roles`
- Aplica RlsScope baseado nas permissões

### Comportamento por Role
- **Admin:** Vê tudo
- **Editor:** Vê dados do projeto
- **User:** Vê apenas próprios dados

### Integração com RBAC
- Policies usam `$user->hasPermissionTo()` + verificação de projeto
- RLS aplicado via Global Scope no Eloquent

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
- **Lib:** Laravel OTP ou implementação com Notifications

### Storage
- **Buckets:** Containers lógicos (pasta no MinIO)
- **Private Files:** URLs temporárias com expiração
- **Políticas:** Acesso via RLS

### API
- **REST:** CRUD automático por tabela
- **SDKs:** JavaScript/TypeScript e PHP

## Próximos Passos

1. Criar estrutura base do projeto Laravel 12
2. Implementar DynamicController inicial
3. Configurar Inertia.js com dashboard básico
4. Implementar cache de schema com Redis
5. Criar primeira tabela dinâmica funcional
6. Configurar Spatie Permission para RBAC
7. Implementar RLS integrado com roles e permissões

---

*Este arquivo serve como contexto central para o desenvolvimento do DockaBase.*
