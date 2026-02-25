# DockaBase - BaaS Platform (Backend as a Service)

## Visão Geral

DockaBase é um clone funcional e simplificado do Supabase, construído com Laravel 12. O objetivo é criar uma plataforma BaaS que fornece:

- **Database Manager** (similar ao Supabase Studio) - Interface visual para gerenciar tabelas PostgreSQL
- **Auth Provider** (similar ao GoTrue) - Autenticação multi-tenant para usuários finais
- **Dynamic REST API** (similar ao PostgREST) - API auto-gerada a partir do schema do banco
- **Realtime** (similar ao Supabase Realtime) - Websockets com LISTEN/NOTIFY do PostgreSQL
- **Storage** (similar ao Supabase Storage) - Abstração S3/MinIO com políticas de acesso

## Stack Tecnológica

| Componente | Tecnologia | Versão |
|------------|------------|--------|
| Backend | Laravel | 12+ |
| PHP | PHP | 8.4+ |
| Performance | Laravel Octane | (Swoole/RoadRunner) |
| Database | PostgreSQL | 16+ |
| Cache | Redis | 7+ |
| Queue | RabbitMQ | 7+ |
| Frontend | Inertia.js + Vue 3 | Latest |
| State | Pinia | Latest |
| Feature Flags | Laravel Pennant | Latest |
| RBAC | Spatie Permission | Latest |
| Realtime | Laravel Echo + Socket.io | Latest |

## Arquitetura

### Estrutura de Domínios

```
app/
├── Domain/
│   ├── System/          # Painel administrativo do DockaBase
│   │   ├── Projects/
│   │   ├── Users/       # Admins do DockaBase
│   │   └── Billing/
│   │
│   ├── Auth/            # Autenticação multi-tenant (GoTrue-like)
│   │   ├── Models/
│   │   │   ├── EndUser.php           # Usuários dos projetos (usa Spatie contracts)
│   │   │   └── EndUserRole.php       # Roles via Spatie Permission
│   │   ├── JWT/
│   │   └── Providers/
│   │
│   ├── Database/        # Schema Builder & Migrations
│   │   ├── Migrations/
│   │   ├── Schema/
│   │   ├── Types/       # UUID, JSONB, Arrays
│   │   └── RLS/          # Row Level Security Engine
│   │       ├── RlsPolicy.php
│   │       ├── RlsChecker.php
│   │       └── RlsScope.php
│   │
│   ├── Api/             # Dynamic REST API (PostgREST-like)
│   │   ├── Controllers/
│   │   │   └── DynamicController.php
│   │   ├── QueryParser/
│   │   │   ├── FilterParser.php      # ?age=gte.18
│   │   │   ├── SelectParser.php      # ?select=id,name
│   │   │   └── OrderParser.php       # ?order=created_at.desc
│   │   └── Middleware/
│   │       ├── RlsMiddleware.php     # Aplica RLS nas queries
│   │       └── AuthMiddleware.php
│   │
│   ├── Realtime/        # Websockets & Postgres LISTEN/NOTIFY
│   │   ├── Channels/
│   │   ├── Listeners/
│   │   └── Broadcasters/
│   │
│   ├── Storage/         # S3/MinIO Abstraction
│   │   ├── Buckets/
│   │   ├── Policies/
│   │   └── Upload/
│   │
│   └── Features/        # Laravel Pennant - Feature Flags
│       ├── DynamicApi.php
│       ├── Realtime.php
│       ├── Storage.php
│       └── Rls.php
```

### Rotas

```
/system/*          → Painel administrativo (DockaBase admins)
/api/v1/*         → Dynamic REST API (pública para projetos)
/auth/v1/*        → Endpoints de autenticação (GoTrue-like)
/realtime/v1/*    → Websockets
/storage/v1/*     → Storage endpoints
```

## Fases de Desenvolvimento

### Fase 1: Core & Infraestrutura
- [ ] Setup Laravel 12
- [ ] Configurar PHP 8.4 properties hooks
- [ ] Configurar Laravel Octane (Swoole)
- [ ] Configurar PostgreSQL 16+
- [ ] Setup Inertia.js + Vue 3
- [ ] Setup Laravel Pennant (Feature Flags)
- [ ] Setup Spatie Permission (RBAC)
- [ ] Estrutura de domínios

### Fase 2: Database & Schema Builder
- [ ] Interface visual para criar tabelas
- [ ] Migrations dinâmicas
- [ ] Suporte a tipos PostgreSQL (UUID, JSONB, Arrays)
- [ ] Relationships e chaves estrangeiras

### Fase 3: Autenticação Multi-tenant & RBAC
- [ ] Separar System Users vs End Users
- [ ] Laravel Sanctum para API tokens
- [ ] JWT para end users
- [ ] Configurar Spatie Permission para roles e permissões
- [ ] Row Level Security (RLS) integrado com RBAC

### Fase 4: API Dinâmica
- [ ] Dynamic Router (`/api/v1/{table}`)
- [ ] Universal Controller
- [ ] Query Parser (filtros tipo `?age=gte.18`)
- [ ] Validação dinâmica baseada no schema
- [ ] Integração com RLS via Middleware

### Fase 5: Realtime
- [ ] Laravel Echo + Redis
- [ ] Postgres LISTEN/NOTIFY
- [ ] Triggers para detectar mudanças
- [ ] Broadcasting events

### Fase 6: Storage
- [ ] Driver S3/MinIO
- [ ] Buckets lógica
- [ ] Políticas de acesso com RLS

## Padrões e Convenções

### Código PHP 8.4
- **Property Hooks:** Usar para getters/setters ao invés de métodos tradicionais
- **Type Hints:** Obrigatório em todos os métodos
- **Declarações de Tipos:** `declare(strict_types=1);` em todos os arquivos
- **PSR-12:** Seguir padrão de codificação
- **Actions:** Usar Laravel Actions para lógica complexa

### Exemplo com Property Hooks (PHP 8.4)

```php
class EndUser extends Model implements HasRoles
{
    use HasRoles, HasApiTokens;

    public string $name;
    public string $email;
    private string $password;

    public string|null $avatarUrl {
        get => $this->attributes['avatar_url'] ?? null;
        set => $this->attributes['avatar_url'] = $value;
    }

    public readonly array $rolesList {
        get => $this->roles->pluck('name')->toArray();
    }
}
```

### Nomenclatura
- **Tabelas de sistema:** Prefixo `system_`
- **Tabelas de projeto:** Prefixo `{project_uuid}_`
- **Models:** Domain-based (ex: `App\Domain\Auth\Models\EndUser`)

## RBAC com Spatie Permission

### Setup Básico

```php
// Modelo EndUser usando Spatie
use Spatie\Permission\Traits\HasRoles;

class EndUser extends Authenticatable
{
    use HasRoles, HasApiTokens;

    protected $guard_name = 'api'; // Guard para end users
}
```

### Roles e Permissões por Projeto

```php
// Criar roles para um projeto específico
$adminRole = Role::create([
    'name' => 'admin',
    'guard_name' => 'api',
    'project_id' => $projectId  // Multi-tenant via scope
]);

$editorRole = Role::create([
    'name' => 'editor',
    'guard_name' => 'api',
    'project_id' => $projectId
]);

// Criar permissões granulares
Permission::create([
    'name' => 'posts.select',
    'guard_name' => 'api',
    'project_id' => $projectId
]);

Permission::create([
    'name' => 'posts.insert',
    'guard_name' => 'api',
    'project_id' => $projectId
]);

// Atribuir ao usuário
$user->assignRole('admin');
$user->givePermissionTo('posts.select');
```

### Sintaxe de Verificação

```php
// Verificar roles
$user->hasRole('admin');
$user->hasAnyRole(['admin', 'editor']);
$user->hasAllRoles(['admin', 'moderator']);

// Verificar permissões
$user->hasPermissionTo('posts.select');
$user->can('posts.insert');

// Via blade
@role('admin')
    <h1>Admin Panel</h1>
@endrole

@can('posts.delete')
    <button>Delete Post</button>
@endcan
```

### Roles Diretos via Spatie

```php
// Sync roles
$user->syncRoles(['admin', 'editor']);

// Sync permissions
$user->syncPermissions(['posts.select', 'posts.insert', 'posts.update']);

// Revoke
$user->removeRole('admin');
$user->revokePermissionTo('posts.delete');
```

### Permissões de Tabela Dinâmica

```php
// Criar permissão para tabela dinâmica
Permission::create([
    'name' => "{$table}.select",
    'guard_name' => 'api',
    'project_id' => $projectId
]);

Permission::create([
    'name' => "{$table}.insert",
    'guard_name' => 'api',
    'project_id' => $projectId
]);

Permission::create([
    'name' => "{$table}.update",
    'guard_name' => 'api',
    'project_id' => $projectId
]);

Permission::create([
    'name' => "{$table}.delete",
    'guard_name' => 'api',
    'project_id' => $projectId
]);
```

## Feature Flags (Laravel Pennant)

```php
// Habilitar/desabilitar features por projeto
Feature::activate('dynamic-api', $projectId);
Feature::activate('realtime', $projectId);
Feature::activate('storage', $projectId);
Feature::activate('rls', $projectId);

// Verificar no código
if (Feature::active('dynamic-api', $projectId)) {
    // ...
}
```

### Features Disponíveis

| Feature | Descrição | Escopo |
|---------|-----------|--------|
| `dynamic-api` | API REST dinâmica habilitada | Por projeto |
| `realtime` | Websockets habilitados | Por projeto |
| `storage` | Storage S3/MinIO habilitado | Por projeto |
| `rls` | Row Level Security habilitado | Por projeto |
| `advanced-rbac` | RBAC avançado com permissões granulares | Por projeto |

### Definição de Feature

```php
use Illuminate\Support\Feature;
use Laravel\Pennant\Feature as PennantFeature;

class DynamicApi implements Feature
{
    public function resolve(User|null $user): mixed
    {
        if (!$user) {
            return false;
        }

        // Ativar se o projeto tem a feature
        return $user->project->hasFeature('dynamic-api');
    }
}
```

## RLS - Row Level Security (Integrado com RBAC)

### Middleware RLS com Spatie

```php
class RlsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $table = $request->route('table');

        if (!$user || !Feature::active('rls', $user->project_id)) {
            return $next($request);
        }

        // Verificar permissão de acesso à tabela
        if (!$user->hasPermissionTo("{$table}.select")) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Definir contexto no PostgreSQL
        DB::statement("SET LOCAL larabase.user_id = ?", [$user->id]);
        DB::statement("SET LOCAL larabase.project_id = ?", [$user->project_id]);
        DB::statement("SET LOCAL larabase.user_roles = ?", [
            json_encode($user->roles->pluck('name'))
        ]);

        // Aplicar RLS Scope baseado nas permissões
        RlsScope::applyTo($table, $user);

        return $next($request);
    }
}
```

### Scope RLS

```php
class RlsScope implements Scope
{
    public function apply(Builder $query, Model $model): void
    {
        $user = auth()->user();
        $table = $model->getTable();

        // Admin vê tudo
        if ($user->hasRole('admin')) {
            return;
        }

        // Aplicar restrições baseadas em roles
        if ($user->hasRole('editor')) {
            $query->where('project_id', $user->project_id);
        } elseif ($user->hasRole('user')) {
            $query->where('user_id', $user->id);
        }
    }
}
```

### Políticas RLS Baseadas em Permissões

```php
class PostPolicy
{
    public function viewAny(EndUser $user): bool
    {
        return $user->hasPermissionTo('posts.select');
    }

    public function view(EndUser $user, Post $post): bool
    {
        return $user->hasPermissionTo('posts.select')
            && ($user->hasRole('admin') || $post->user_id === $user->id);
    }

    public function create(EndUser $user): bool
    {
        return $user->hasPermissionTo('posts.insert');
    }

    public function update(EndUser $user, Post $post): bool
    {
        return $user->hasPermissionTo('posts.update')
            && ($user->hasRole('admin') || $post->user_id === $user->id);
    }

    public function delete(EndUser $user, Post $post): bool
    {
        return $user->hasPermissionTo('posts.delete')
            && ($user->hasRole('admin') || $post->user_id === $user->id);
    }
}
```

## Query Syntax (API Dinâmica)

A API seguirá a sintaxe do Supabase/PostgREST:

```
GET /api/v1/users?id=eq.1&select=id,name,email
GET /api/v1/users?age=gte.18&order=created_at.desc
GET /api/v1/users?limit=10&offset=20
POST /api/v1/users { "name": "John", "email": "john@example.com" }
PATCH /api/v1/users?id=eq.1 { "name": "Jane" }
DELETE /api/v1/users?id=eq.1
```

## Realtime com Postgres NOTIFY

```sql
-- Trigger no Postgres
CREATE FUNCTION notify_change() RETURNS trigger AS $$
BEGIN
    PERFORM pg_notify('channel_' || TG_TABLE_NAME, row_to_json(NEW)::text);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

Listener em Laravel dispara eventos para o Echo Client.

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
