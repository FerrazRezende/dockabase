# Multi-tenant - System vs End Users

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 3 |
| Feature Flag | - |
| Dependencies | Schema Builder completo |

---

## User Story

**As a** administrador da plataforma DockaBase
**I want to** ter usuários separados para administração do sistema e usuários finais das aplicações
**So that** a segurança e o isolamento entre tenants sejam garantidos

---

## Acceptance Criteria

```gherkin
Scenario: System User acessa painel administrativo
  Given sou um System User com role "admin"
  When faço login no painel `/system`
  Then tenho acesso à gestão de projetos
  And posso criar/editar/deletar projetos
  And posso gerenciar outros System Users
```

```gherkin
Scenario: End User acessa API do projeto
  Given sou um End User do projeto "ecommerce-app"
  When faço login via `/auth/v1/login`
  Then recebo um JWT token
  And posso acessar apenas recursos do projeto "ecommerce-app"
  And não tenho acesso ao painel administrativo
```

```gherkin
Scenario: Isolamento de dados entre projetos
  Given sou End User do projeto "project-a"
  And existe um projeto "project-b"
  When faço uma requisição para a API
  Then vejo apenas dados do projeto "project-a"
  And não consigo acessar dados do "project-b"
```

```gherkin
Scenario: Criar End User via API
  Given sou um app frontend do projeto "ecommerce-app"
  When POST para `/auth/v1/register` com:
    | email | user@example.com |
    | password | secure123 |
  Then um novo End User é criado
  And o usuário é associado ao projeto "ecommerce-app"
  And recebe role padrão "user"
```

```gherkin
Scenario: System User não pode ser End User
  Given existe um System User "admin@dockabase.com"
  When tento criar End User com mesmo email
  Then vejo erro "Email já cadastrado no sistema"
```

---

## Technical Notes

### Modelos de Usuário
| Modelo | Guard | Tabela | Propósito |
|--------|-------|--------|-----------|
| `SystemUser` | `web` | `system_users` | Admins da plataforma |
| `EndUser` | `api` | `{project_uuid}_users` | Usuários das aplicações |

### Arquitetura de Guards
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'system_users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'end_users',
    ],
],

'providers' => [
    'system_users' => [
        'driver' => 'eloquent',
        'model' => App\Models\SystemUser::class,
    ],
    'end_users' => [
        'driver' => 'eloquent',
        'model' => App\Domain\Auth\Models\EndUser::class,
    ],
],
```

### Middleware Chain
```
Request
    │
    ▼
┌─────────────────┐
│ IdentifyProject │ ← Resolve project from host/header
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ SetDatabase     │ ← Switch to project database
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Authenticate    │ ← JWT for End Users
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ SetRLSContext   │ ← Set PostgreSQL context
└────────┬────────┘
         │
         ▼
Controller
```

### Endpoints
| Method | Endpoint | Guard | Description |
|--------|----------|-------|-------------|
| POST | `/system/login` | web | Login System User |
| POST | `/auth/v1/register` | api | Registrar End User |
| POST | `/auth/v1/login` | api | Login End User |
| POST | `/auth/v1/logout` | api | Logout End User |
| GET | `/auth/v1/me` | api | Dados do usuário logado |

### Files to Create
```
app/
├── Models/
│   └── SystemUser.php
├── Domain/Auth/
│   ├── Models/
│   │   └── EndUser.php
│   ├── Controllers/
│   │   └── AuthController.php
│   ├── Services/
│   │   └── UserRegistrationService.php
│   └── Requests/
│       ├── LoginRequest.php
│       └── RegisterRequest.php
├── Http/
│   ├── Middleware/
│   │   ├── IdentifyProject.php
│   │   ├── SetDatabaseConnection.php
│   │   └── SetRLSContext.php
│   └── Controllers/System/
│       └── AuthController.php
```

### Database Schema
```sql
-- System Users (no banco system)
CREATE TABLE system_users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- End Users (no banco do projeto)
CREATE TABLE {project_uuid}_users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(email)
);
```

---

## Security Considerations

- [ ] Guards separados previnem acesso cruzado
- [ ] Validação de email único entre system_users e end_users
- [ ] Middleware de projeto em todas as rotas de API
- [ ] JWT com claim de project_id
- [ ] Rate limiting em endpoints de autenticação
