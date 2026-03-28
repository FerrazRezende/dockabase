# OTP Authentication

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P1 (High) |
| Phase | 3 |
| Feature Flag | `otp-auth` |
| Dependencies | JWT Authentication |

---

## User Story

**As a** usuário final da aplicação
**I want to** fazer login sem senha usando código OTP
**So that** posso me autenticar de forma segura e conveniente sem precisar lembrar senhas

---

## Acceptance Criteria

```gherkin
Scenario: Solicitar código OTP
  Given sou um usuário cadastrado com email "user@example.com"
  When POST para `/auth/v1/otp/request` com:
    | email | user@example.com |
  Then um código OTP de 6 dígitos é gerado
  And o código é enviado por email
  And recebo status 200 com:
    | message | Código enviado para user@example.com |
    | expires_in | 300 |
```

```gherkin
Scenario: Login com OTP válido
  Given solicitei um código OTP
  And recebi o código "123456"
  When POST para `/auth/v1/otp/verify` com:
    | email | user@example.com |
    | code | 123456 |
  Then recebo tokens JWT
  And o código OTP é invalidado
```

```gherkin
Scenario: OTP expirado
  Given solicitei um código OTP há 6 minutos
  When tento verificar com o código
  Then recebo status 401
  And body contém:
    | error | otp_expired |
    | message | Código expirado. Solicite um novo. |
```

```gherkin
Scenario: OTP inválido
  Given solicitei um código OTP
  When POST para `/auth/v1/otp/verify` com código errado "000000"
  Then recebo status 401
  And body contém:
    | error | invalid_otp |
    | attempts_remaining | 2 |
```

```gherkin
Scenario: Bloqueio após muitas tentativas
  Given falhei 3 vezes ao tentar verificar OTP
  When tento verificar novamente
  Then recebo status 429
  And body contém:
    | error | too_many_attempts |
    | message | Muitas tentativas. Aguarde 15 minutos. |
```

```gherkin
Scenario: Rate limiting na solicitação
  Given solicitei um código OTP há 30 segundos
  When solicito outro código
  Then recebo status 429
  And body contém:
    | error | rate_limited |
    | retry_after | 30 |
```

```gherkin
Scenario: OTP para novo usuário
  Given não existe usuário com email "new@example.com"
  When solicito OTP para "new@example.com"
  Then o usuário é criado automaticamente
  And o código OTP é enviado
```

---

## Technical Notes

### Configurações OTP
| Configuração | Valor | Descrição |
|--------------|-------|-----------|
| Code Length | 6 | Dígitos do código |
| TTL | 5 min | Tempo de expiração |
| Max Attempts | 3 | Tentativas por código |
| Cooldown | 60s | Tempo entre solicitações |
| Lockout Duration | 15 min | Bloqueio após max attempts |

### Fluxo OTP
```
┌─────────┐  POST /otp/request  ┌──────────────┐
│  Client │ ──────────────────► │ OtpController │
└─────────┘                     └──────┬───────┘
                                       │
        ┌──────────────────────────────┘
        │
        ▼
┌─────────────────┐
│ Rate Limit Check│
└────────┬────────┘
         │
         ▼
┌─────────────────┐     ┌─────────────┐
│ Generate OTP    │────►│ Redis Store │
│ (6 digits)      │     │ key: otp:{email} │
└────────┬────────┘     │ val: {code, attempts} │
         │              └─────────────┘
         ▼
┌─────────────────┐
│ Send Email      │
│ (Mailable)      │
└─────────────────┘


Verification Flow:
┌─────────┐  POST /otp/verify   ┌──────────────┐
│  Client │ ──────────────────► │ OtpController │
└─────────┘                     └──────┬───────┘
                                       │
        ┌──────────────────────────────┘
        │
        ▼
┌─────────────────┐
│ Validate OTP    │◄──── Redis Lookup
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Check Attempts  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Generate JWT    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Invalidate OTP  │
└─────────────────┘
```

### Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/v1/otp/request` | Solicitar código OTP |
| POST | `/auth/v1/otp/verify` | Verificar código e obter tokens |

### Files to Create
```
app/
├── Domain/Auth/
│   ├── Controllers/
│   │   └── OtpAuthController.php
│   ├── Services/
│   │   ├── OtpService.php
│   │   └── OtpRateLimiterService.php
│   ├── Mail/
│   │   └── OtpCodeMail.php
│   └── Requests/
│       ├── OtpRequest.php
│       └── OtpVerifyRequest.php
```

### Redis Schema
```
otp:{project_id}:{email}
    ├── code: "123456"
    ├── attempts: 0
    ├── created_at: timestamp
    └── TTL: 300 segundos

otp_cooldown:{project_id}:{email}
    └── TTL: 60 segundos

otp_lockout:{project_id}:{email}
    └── TTL: 900 segundos
```

---

## Security Considerations

- [ ] Rate limiting rigoroso (previne brute force)
- [ ] Código curto + TTL curto (5 min)
- [ ] Bloqueio após tentativas excessivas
- [ ] Invalidar código após uso
- [ ] Log de tentativas para auditoria
- [ ] Não revelar se email existe no sistema
- [ ] Throttling por IP + email
