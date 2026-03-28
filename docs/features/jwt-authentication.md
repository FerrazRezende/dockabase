# JWT Authentication

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 3 |
| Feature Flag | - |
| Dependencies | Multi-tenant - System vs End Users |

---

## User Story

**As a** usuário final da aplicação
**I want to** me autenticar via JWT e ter tokens seguros com refresh
**So that** posso acessar a API de forma stateless e segura

---

## Acceptance Criteria

```gherkin
Scenario: Login com sucesso gera tokens
  Given sou um End User cadastrado
  When POST para `/auth/v1/login` com:
    | email | user@example.com |
    | password | correct_password |
  Then recebo status 200
  And recebo JSON com:
    | access_token | <jwt> |
    | refresh_token | <jwt> |
    | expires_in | 3600 |
    | token_type | Bearer |
```

```gherkin
Scenario: Usar access token para acessar API
  Given tenho um access_token válido
  When GET para `/api/v1/products` com header:
    | Authorization | Bearer <access_token> |
  Then recebo os dados solicitados
  And status 200
```

```gherkin
Scenario: Token expirado retorna 401
  Given tenho um access_token expirado
  When GET para `/api/v1/products`
  Then recebo status 401
  And body contém:
    | error | token_expired |
    | message | Token has expired |
```

```gherkin
Scenario: Refresh token renova acesso
  Given tenho um refresh_token válido
  And meu access_token expirou
  When POST para `/auth/v1/refresh` com:
    | refresh_token | <refresh_token> |
  Then recebo novo access_token
  And recebo novo refresh_token
  And tokens antigos são invalidados
```

```gherkin
Scenario: Logout invalida tokens
  Given estou autenticado
  When POST para `/auth/v1/logout`
  Then meus tokens são invalidados
  And recebo status 200
```

```gherkin
Scenario: Login com credenciais inválidas
  When POST para `/auth/v1/login` com:
    | email | user@example.com |
    | password | wrong_password |
  Then recebo status 401
  And body contém:
    | error | invalid_credentials |
```

```gherkin
Scenario: Token contém claims do projeto
  Given faço login como End User do projeto "ecommerce"
  When decodifico o access_token
  Then vejo claims:
    | sub | <user_uuid> |
    | project_id | <project_uuid> |
    | roles | ["user"] |
    | iat | <issued_at> |
    | exp | <expires_at> |
```

---

## Technical Notes

### JWT Configuration
```php
// config/jwt.php
'ttl' => env('JWT_ACCESS_TTL', 60), // 1 hora
'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 2 semanas
'blacklist_enabled' => true,
'blacklist_grace_period' => 30,
```

### Token Structure
```json
{
  "iss": "dockabase.app",
  "iat": 1709827200,
  "exp": 1709830800,
  "sub": "01ARZ3NXXK29B5FZ3XZ1XZ",
  "project_id": "01ARZ3NXXK29B5FZ3XZ2YZ",
  "roles": ["user", "editor"],
  "permissions": ["posts.select", "posts.insert"]
}
```

### Fluxo de Autenticação
```
┌─────────┐     POST /login      ┌─────────────┐
│  Client │ ───────────────────► │ AuthController │
└─────────┘                      └──────┬──────┘
                                        │
     ┌──────────────────────────────────┘
     │
     ▼
┌─────────────────┐
│ ValidateCredentials │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ GenerateJWT     │
│ - access_token  │
│ - refresh_token │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ StoreBlacklist  │
│ (for refresh)   │
└────────┬────────┘
         │
         ▼
     Return Tokens
```

### Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/v1/login` | Autenticar e obter tokens |
| POST | `/auth/v1/refresh` | Renovar tokens |
| POST | `/auth/v1/logout` | Invalidar tokens |
| GET | `/auth/v1/me` | Dados do usuário logado |

### Files to Create
```
app/
├── Domain/Auth/
│   ├── Controllers/
│   │   └── JwtAuthController.php
│   ├── Services/
│   │   ├── JwtTokenService.php
│   │   └── TokenBlacklistService.php
│   └── Middleware/
│       └── JwtMiddleware.php
├── Http/
│   └── Middleware/
│       └── AuthenticateJwt.php
```

### Package
```bash
composer require tymon/jwt-auth
```

---

## Security Considerations

- [ ] Access token com TTL curto (1h)
- [ ] Refresh token com TTL longo (2 semanas)
- [ ] Blacklist para tokens revogados (Redis)
- [ ] Algoritmo HS256 ou RS256
- [ ] Secret key rotacionável
- [ ] Rate limiting em `/login`
- [ ] Não expor detalhes de erro em produção
