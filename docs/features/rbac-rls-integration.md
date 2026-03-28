# RBAC & RLS Integration

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 3 |
| Feature Flag | `rls`, `advanced-rbac` |
| Dependencies | JWT Authentication |

---

## User Story

**As a** administrador do projeto
**I want to** definir papéis e permissões granulares com isolamento automático de dados
**So that** cada usuário acessa apenas os dados que tem permissão, garantindo segurança multi-tenant

---

## Acceptance Criteria

```gherkin
Scenario: Criar role customizada
  Given sou admin do projeto "ecommerce"
  When POST para `/system/projects/ecommerce/roles` com:
    | name | editor |
    | permissions | ["products.select", "products.insert", "products.update"] |
  Then a role "editor" é criada
  And está disponível para atribuição
```

```gherkin
Scenario: Atribuir role a usuário
  Given existe a role "editor"
  And existe o usuário "joao@example.com"
  When atribuo a role "editor" ao usuário
  Then o usuário herda as permissões da role
```

```gherkin
Scenario: Permissão concedida permite acesso
  Given usuário "joao" tem permissão "products.select"
  When GET para `/api/v1/products`
  Then recebo lista de produtos do projeto
  And status 200
```

```gherkin
Scenario: Permissão negada bloqueia acesso
  Given usuário "joao" NÃO tem permissão "products.delete"
  When DELETE para `/api/v1/products/123`
  Then recebo status 403
  And body contém:
    | error | forbidden |
```

```gherkin
Scenario: RLS filtra dados por projeto
  Given usuário "joao" é do projeto "project-a"
  And existem produtos em "project-a" e "project-b"
  When GET para `/api/v1/products`
  Then recebo apenas produtos de "project-a"
  And não vejo produtos de "project-b"
```

```gherkin
Scenario: RLS filtra dados por owner
  Given usuário "joao" tem role "user" (não admin)
  And existem pedidos de "joao" e "maria"
  When GET para `/api/v1/orders`
  Then recebo apenas pedidos criados por "joao"
```

```gherkin
Scenario: Admin vê todos os dados do projeto
  Given usuário "ana" tem role "admin"
  When GET para `/api/v1/orders`
  Then recebo todos os pedidos do projeto
  (não filtrado por owner)
```

```gherkin
Scenario: Verificar permissão via API
  When GET para `/auth/v1/me/permissions`
  Then recebo lista de permissões:
    | permissions | ["products.select", "products.insert", "products.update"] |
    | roles | ["editor"] |
```

---

## Technical Notes

### Roles Padrão
| Role | Descrição | Permissões |
|------|-----------|------------|
| `super-admin` | Acesso total | `*.*` |
| `admin` | Gerencia projeto | `*.select, insert, update, delete` |
| `manager` | Gerencia usuários | `*.select, insert, update` |
| `editor` | Edita conteúdo | `*.select, insert, update` |
| `user` | Usuário comum | `*.select` (próprios dados) |

### Formato de Permissões
```
{resource}.{operation}

Exemplos:
- products.select
- products.insert
- products.update
- products.delete
- users.manage
- settings.admin
```

### Middleware Stack
```php
// Rota: /api/v1/{table}
Route::middleware([
    'auth:api',           // JWT authentication
    'project.context',    // Set project context
    'rls.context',        // Set PostgreSQL RLS context
    'permission.check',   // Spatie permission check
])->group(function () {
    Route::get('/{table}', [DynamicController::class, 'index']);
});
```

### PostgreSQL RLS Setup
```sql
-- Habilitar RLS na tabela
ALTER TABLE products ENABLE ROW LEVEL SECURITY;

-- Política para admin (vê tudo do projeto)
CREATE POLICY admin_all ON products
    FOR ALL
    TO admin_role
    USING (project_id = current_setting('app.current_project')::uuid);

-- Política para user (vê apenas próprios dados)
CREATE POLICY user_own ON products
    FOR SELECT
    TO user_role
    USING (
        project_id = current_setting('app.current_project')::uuid
        AND created_by = current_setting('app.current_user')::uuid
    );
```

### SetRLSContext Middleware
```php
class SetRLSContext
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        $project = $request->attributes->get('project');

        DB::statement("SET app.current_project = ?", [$project->id]);
        DB::statement("SET app.current_user = ?", [$user->id]);
        DB::statement("SET app.current_roles = ?", [
            implode(',', $user->roles->pluck('name')->toArray())
        ]);

        return $next($request);
    }
}
```

### Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/system/projects/{id}/roles` | Listar roles |
| POST | `/system/projects/{id}/roles` | Criar role |
| PUT | `/system/projects/{id}/roles/{role}` | Atualizar role |
| DELETE | `/system/projects/{id}/roles/{role}` | Deletar role |
| POST | `/system/projects/{id}/users/{user}/roles` | Atribuir role |
| GET | `/auth/v1/me/permissions` | Minhas permissões |

### Files to Create
```
app/
├── Domain/Auth/
│   ├── Models/
│   │   ├── Role.php (Spatie extended)
│   │   └── Permission.php (Spatie extended)
│   ├── Services/
│   │   └── RbacService.php
│   └── Policies/
│       └── DynamicResourcePolicy.php
├── Http/
│   ├── Controllers/System/
│   │   └── RoleController.php
│   ├── Middleware/
│   │   ├── SetRLSContext.php
│   │   └── CheckDynamicPermission.php
│   └── Requests/
│       └── CreateRoleRequest.php
```

### Database Schema
```sql
-- Tabelas Spatie no banco do projeto
CREATE TABLE {project_uuid}_roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) DEFAULT 'api',
    project_id UUID NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(name, guard_name, project_id)
);

CREATE TABLE {project_uuid}_permissions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) DEFAULT 'api',
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(name, guard_name)
);

CREATE TABLE {project_uuid}_role_has_permissions (
    permission_id UUID REFERENCES {project_uuid}_permissions(id),
    role_id UUID REFERENCES {project_uuid}_roles(id),
    PRIMARY KEY (permission_id, role_id)
);

CREATE TABLE {project_uuid}_model_has_roles (
    role_id UUID REFERENCES {project_uuid}_roles(id),
    model_type VARCHAR(255) NOT NULL,
    model_id UUID NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type)
);
```

---

## Security Considerations

- [ ] RLS habilitado em todas as tabelas de projeto
- [ ] Contexto PostgreSQL setado em cada request
- [ ] Permissões verificadas antes de cada operação
- [ ] Admin bypass documentado e auditado
- [ ] Log de mudanças de role/permission
- [ ] Não permitir remoção da última role admin
