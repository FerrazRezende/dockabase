# Design: Sistema de Múltiplos Databases + Credentials

**Data:** 2026-03-28
**Status:** Draft
**Autor:** Claude + User

---

## 1. Visão Geral

O DockaBase evolui de single-database para **múltiplos databases por instância**, permitindo que uma única instalação gerencie `dev`, `staging`, `prod` e outros ambientes.

Para controle de acesso à API, introduzimos o conceito de **Credentials** - similar a Security Groups da AWS - que agrupam usuários com um nível de permissão (read/write/read-write).

---

## 2. Objetivos

1. Permitir múltiplos databases PostgreSQL por instância DockaBase
2. Controlar acesso à API via Credentials (não diretamente por usuário)
3. Suportar cenários como: "Dev Team tem RW em dev e staging, Analytics Team tem R em prod"
4. Features globais com override por database

---

## 3. Arquitetura de Acesso

### 3.1 Dois Níveis de Acesso

```
┌─────────────────────────────────────────────────────────────────┐
│                    NÍVEL DA APLICAÇÃO                            │
│                    (Spatie RBAC)                                │
│                                                                 │
│  System Users (admins)                                          │
│  ├── Permissões: create-database, manage-credentials, etc.     │
│  └── Acesso: Painel administrativo DOCKABASE                    │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    NÍVEL DA API                                 │
│                    (Credentials)                                │
│                                                                 │
│  End Users (desenvolvedores frontend, DBeaver, etc.)           │
│  ├── Permissões: read, write, read-write                       │
│  └── Acesso: API REST /api/v1/{database}/...                   │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Modelo de Dados

```
users ──┐
        ├── credential_user (pivot) ──► credentials
        │                                    │
        │                                    ├── permission: enum(read, write, read-write)
        │                                    │
        └────────────────────────────────────┴──► credential_database (pivot) ──► databases
```

### 3.3 Fluxo de Criação

```
┌─────────────────────────────────────────┐
│ PASSO 1: Criar Credential               │
│                                         │
│   Nome: "Dev Team"                      │
│   Permissão: read-write                 │
│   Usuários: [alice, bob, carol]  ←───── attach aqui
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│ PASSO 2: Criar Database                 │
│                                         │
│   Nome: "dev"                           │
│   Credenciais: [Dev Team, QA Team] ←─── attach aqui
│   (usuários NÃO aparecem aqui)          │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│ RESULTADO                               │
│                                         │
│   alice, bob, carol → acesso RW a "dev" │
│   usuários do QA Team → acesso conforme │
│   permissão do QA Team                  │
└─────────────────────────────────────────┘
```

---

## 4. Models

### 4.1 Database

Representa um database PostgreSQL físico gerenciado pelo DockaBase.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | UUID | PK |
| name | string(64) | Identificador único (ex: 'dev', 'prod') |
| display_name | string(255) | Nome amigável (ex: 'Development') |
| description | text | Descrição opcional |
| host | string(255) | Host PostgreSQL (default: localhost) |
| port | integer | Porta PostgreSQL (default: 5432) |
| database_name | string(64) | Nome real no PostgreSQL |
| is_active | boolean | Se está ativo |
| settings | jsonb | Feature overrides por database |
| created_at | timestamp | |
| updated_at | timestamp | |

**Relacionamentos:**
- `hasMany` CredentialDatabase
- `belongsToMany` Credential via credential_database

### 4.2 Credential

Grupo de acesso com nível de permissão.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | UUID | PK |
| name | string(255) | Nome do grupo (ex: 'Dev Team') |
| permission | enum | 'read', 'write', 'read-write' |
| description | text | Descrição opcional |
| created_at | timestamp | |
| updated_at | timestamp | |

**Relacionamentos:**
- `belongsToMany` User via credential_user
- `belongsToMany` Database via credential_database

### 4.3 CredentialUser (Pivot)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | UUID | PK |
| credential_id | UUID | FK → credentials |
| user_id | UUID | FK → users |
| created_at | timestamp | |

### 4.4 CredentialDatabase (Pivot)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | UUID | PK |
| credential_id | UUID | FK → credentials |
| database_id | UUID | FK → databases |
| created_at | timestamp | |

---

## 5. Migrations

```php
// 2026_03_28_000001_create_databases_table.php
Schema::create('databases', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->string('name', 64)->unique();
    $table->string('display_name', 255)->nullable();
    $table->text('description')->nullable();
    $table->string('host', 255)->default('localhost');
    $table->unsignedInteger('port')->default(5432);
    $table->string('database_name', 64);
    $table->boolean('is_active')->default(true);
    $table->jsonb('settings')->nullable();
    $table->timestamps();

    $table->index('name');
    $table->index('is_active');
});

// 2026_03_28_000002_create_credentials_table.php
Schema::create('credentials', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->string('name', 255);
    $table->enum('permission', ['read', 'write', 'read-write']);
    $table->text('description')->nullable();
    $table->timestamps();

    $table->index('name');
});

// 2026_03_28_000003_create_credential_user_table.php
Schema::create('credential_user', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('credential_id')->constrained('credentials')->cascadeOnDelete();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->unique(['credential_id', 'user_id']);
});

// 2026_03_28_000004_create_credential_database_table.php
Schema::create('credential_database', function (Blueprint $table) {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('credential_id')->constrained('credentials')->cascadeOnDelete();
    $table->foreignUuid('database_id')->constrained('databases')->cascadeOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->unique(['credential_id', 'database_id']);
});
```

---

## 6. API Endpoints

### 6.1 System Routes (Admin Panel)

| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/system/databases` | Lista todos os databases |
| POST | `/system/databases` | Cria novo database |
| GET | `/system/databases/{database}` | Detalhes do database |
| PATCH | `/system/databases/{database}` | Atualiza database |
| DELETE | `/system/databases/{database}` | Remove database |
| POST | `/system/databases/{database}/credentials` | Atrela credential |
| DELETE | `/system/databases/{database}/credentials/{credential}` | Remove credential |

| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/system/credentials` | Lista todas as credentials |
| POST | `/system/credentials` | Cria nova credential |
| GET | `/system/credentials/{credential}` | Detalhes da credential |
| PATCH | `/system/credentials/{credential}` | Atualiza credential |
| DELETE | `/system/credentials/{credential}` | Remove credential |
| POST | `/system/credentials/{credential}/users` | Adiciona usuário |
| DELETE | `/system/credentials/{credential}/users/{user}` | Remove usuário |

### 6.2 API Routes (End Users)

| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/v1/{database}/{table}` | Lista registros |
| POST | `/api/v1/{database}/{table}` | Cria registro |
| GET | `/api/v1/{database}/{table}/{id}` | Detalhes |
| PATCH | `/api/v1/{database}/{table}/{id}` | Atualiza |
| DELETE | `/api/v1/{database}/{table}/{id}` | Remove |

**Autenticação:** Sanctum token
**Autorização:** Verifica se usuário tem credential atrelada ao database com permissão adequada

---

## 7. Services

### 7.1 DatabaseService

```php
class DatabaseService
{
    public function create(string $name, array $options): Database;
    public function delete(string $id): void;
    public function attachCredential(Database $db, Credential $cred): void;
    public function detachCredential(Database $db, Credential $cred): void;
    public function getDatabasesForUser(User $user): Collection;
}
```

### 7.2 CredentialService

```php
class CredentialService
{
    public function create(string $name, string $permission, array $userIds): Credential;
    public function update(Credential $cred, array $data): Credential;
    public function delete(string $id): void;
    public function attachUser(Credential $cred, string $userId): void;
    public function detachUser(Credential $cred, string $userId): void;
    public function getUserPermissionForDatabase(User $user, string $databaseName): ?string;
}
```

---

## 8. Middleware

### 8.1 EnsureDatabaseAccess

Verifica se o usuário autenticado tem acesso ao database via credentials.

```php
class EnsureDatabaseAccess
{
    public function handle($request, Closure $next, string $permission = 'read')
    {
        $user = $request->user();
        $database = $request->route('database');

        $userPermission = app(CredentialService::class)
            ->getUserPermissionForDatabase($user, $database);

        if (!$userPermission) {
            abort(403, 'Access denied to this database');
        }

        if (!$this->hasRequiredPermission($userPermission, $permission)) {
            abort(403, 'Insufficient permissions');
        }

        return $next($request);
    }
}
```

---

## 9. Feature Flags Integration

### 9.1 Database-Level Overrides

O model `Database` tem um campo `settings` (jsonb) que pode conter feature overrides:

```json
{
  "features": {
    "realtime": false,
    "storage": true
  }
}
```

### 9.2 FeatureServiceProvider Update

```php
protected function resolveFeature(User $user, string $featureName): bool
{
    // God Admin always has access
    if ($user->is_admin === true) {
        return true;
    }

    // Get current database context (if any)
    $databaseName = request()->route('database');
    $database = $databaseName ? Database::where('name', $databaseName)->first() : null;

    // Check database-level override first
    if ($database && isset($database->settings['features'][$featureName])) {
        return $database->settings['features'][$featureName];
    }

    // Fall back to global setting
    $setting = FeatureSetting::where('feature_name', $featureName)->first();
    // ... existing logic
}
```

---

## 10. Vue Pages

### 10.1 Databases

- `resources/js/Pages/System/Databases/Index.vue` - Lista databases
- `resources/js/Pages/System/Databases/Create.vue` - Criar database
- `resources/js/Pages/System/Databases/Show.vue` - Detalhes + credentials atreladas

### 10.2 Credentials

- `resources/js/Pages/System/Credentials/Index.vue` - Lista credentials
- `resources/js/Pages/System/Credentials/Create.vue` - Criar credential + adicionar users
- `resources/js/Pages/System/Credentials/Show.vue` - Detalhes + users + databases

---

## 11. Testes

### 11.1 Unit Tests

- `DatabaseServiceTest` - Criação, deleção, attach/detach credentials
- `CredentialServiceTest` - Criação, permissões, attach/detach users
- `CredentialPermissionTest` - Lógica de verificação de permissões

### 11.2 Feature Tests

- `DatabaseControllerTest` - CRUD endpoints
- `CredentialControllerTest` - CRUD endpoints
- `DatabaseAccessMiddlewareTest` - Middleware de acesso
- `ApiAccessTest` - Endpoints da API com diferentes níveis de permissão

---

## 12. Rollout Plan

### Fase 2.1: Models e Migrations
- Criar migrations
- Criar models com relationships
- Testes unitários

### Fase 2.2: Services
- DatabaseService
- CredentialService
- Testes unitários

### Fase 2.3: Controllers + Routes
- DatabaseController
- CredentialController
- Testes feature

### Fase 2.4: Vue Pages
- Index/Create/Show para Databases
- Index/Create/Show para Credentials

### Fase 2.5: API Middleware
- EnsureDatabaseAccess middleware
- Integração com Dynamic API (fase 5)

---

## 13. Questões Abertas

Nenhuma. Design aprovado pelo usuário em 2026-03-28.
