# Feature Flag Manager

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 1 |
| Feature Flag | - (esta é a feature que gerencia features) |
| Dependencies | Core Infrastructure |

---

## User Story

**As a** administrador do projeto
**I want to** gerenciar feature flags com rollout gradual por percentual, usuários específicos e lançamento geral
**So that** posso liberar features de forma controlada e segura para meus usuários

---

## Acceptance Criteria

```gherkin
Scenario: Listar features disponíveis
  Given sou admin do projeto
  When GET para `/system/projects/{id}/features`
  Then recebo lista de todas as features disponíveis:
    | name | description | status | rollout_strategy |
    | dynamic-api | API REST dinâmica | active | percentage |
    | realtime | Websockets | inactive | - |
    | otp-auth | Login sem senha | active | users |
```

```gherkin
Scenario: Ativar feature com rollout por percentual
  Given sou admin do projeto
  And feature "realtime" está inativa
  When POST para `/system/projects/{id}/features/realtime/activate` com:
    | strategy | percentage |
    | percentage | 25 |
  Then feature é ativada
  And 25% dos usuários recebem a feature
  And body contém:
    | feature | realtime |
    | strategy | percentage |
    | percentage | 25 |
```

```gherkin
Scenario: Usuário recebe feature baseado em percentual
  Given feature "realtime" está com rollout de 50%
  And sou um usuário do projeto
  When verifico minhas features
  Then recebo a feature se estou no grupo dos 50%
  (determinístico baseado em hash do user_id)
```

```gherkin
Scenario: Ativar feature para usuários específicos
  Given sou admin do projeto
  When POST para `/system/projects/{id}/features/otp-auth/activate` com:
    | strategy | users |
    | user_ids | ["uuid-1", "uuid-2", "uuid-3"] |
  Then apenas os usuários especificados recebem a feature
```

```gherkin
Scenario: Adicionar usuário à lista de feature
  Given feature "otp-auth" está ativa para usuários específicos
  When POST para `/system/projects/{id}/features/otp-auth/users` com:
    | user_id | uuid-novo |
  Then usuário é adicionado à lista
  E passa a receber a feature
```

```gherkin
Scenario: Remover usuário da feature
  Given usuário "uuid-1" tem acesso à feature "otp-auth"
  When DELETE para `/system/projects/{id}/features/otp-auth/users/uuid-1`
  Then usuário perde acesso à feature
```

```gherkin
Scenario: Lançar feature para todos (general availability)
  Given feature "dynamic-api" está ativa para 50%
  When POST para `/system/projects/{id}/features/dynamic-api/activate` com:
    | strategy | all |
  Then feature é liberada para 100% dos usuários
```

```gherkin
Scenario: Desativar feature completamente
  Given feature "realtime" está ativa
  When POST para `/system/projects/{id}/features/realtime/deactivate`
  Then feature é desativada para todos os usuários
```

```gherkin
Scenario: Admin do projeto sempre vê feature
  Given feature "realtime" está desativada
  And sou admin do projeto
  When verifico minhas features
  Then vejo a feature "realtime" disponível
  (admins sempre têm acesso para testar)
```

```gherkin
Scenario: Admin do sistema ignora feature flags
  Given sou System Admin (não End User)
  And feature "realtime" está desativada para todos
  When acesso qualquer endpoint
  Then a feature está disponível para mim
  (system admins bypass todas as flags)
```

```gherkin
Scenario: Verificar feature via API
  When GET para `/api/v1/features`
  Then recebo lista de features ativas para mim:
    | features | ["dynamic-api", "storage"] |
```

```gherkin
Scenario: Middleware bloqueia feature desativada
  Given feature "realtime" está desativada para mim
  When POST para `/realtime/v1/subscribe`
  Then recebo status 403
  And body contém:
    | error | feature_disabled |
    | message | This feature is not available for your account |
```

```gherkin
Scenario: Histórico de mudanças de feature
  Given houve 5 mudanças na feature "dynamic-api"
  When GET para `/system/projects/{id}/features/dynamic-api/history`
  Then recebo histórico:
    | timestamp | action | actor | details |
    | 2024-03-01 | activated | admin@email.com | {"strategy": "percentage", "value": 25} |
    | 2024-03-05 | updated | admin@email.com | {"strategy": "percentage", "value": 50} |
    | 2024-03-10 | updated | admin@email.com | {"strategy": "all"} |
```

```gherkin
Scenario: Alterar percentual de rollout
  Given feature "realtime" está com 25% de rollout
  When PATCH para `/system/projects/{id}/features/realtime` com:
    | percentage | 75 |
  Then rollout é atualizado para 75%
  And usuários que já tinham acesso continuam tendo
  ( rollout consistente)
```

---

## Technical Notes

### Estratégias de Rollout
| Estratégia | Descrição | Uso |
|------------|-----------|-----|
| `inactive` | Feature desativada | Default |
| `percentage` | X% dos usuários | Rollout gradual |
| `users` | Lista específica | Beta testers |
| `all` | 100% dos usuários | General Availability |

### Feature Flags Disponíveis
```php
// config/pennant.php
'features' => [
    'dynamic-api' => [
        'name' => 'Dynamic REST API',
        'description' => 'API REST auto-gerada a partir do schema',
        'default' => false,
    ],
    'realtime' => [
        'name' => 'Realtime Subscriptions',
        'description' => 'Websockets com LISTEN/NOTIFY',
        'default' => false,
    ],
    'storage' => [
        'name' => 'File Storage',
        'description' => 'MinIO com buckets e políticas',
        'default' => false,
    ],
    'otp-auth' => [
        'name' => 'OTP Authentication',
        'description' => 'Login sem senha via código',
        'default' => false,
    ],
    'database-encryption' => [
        'name' => 'Database Encryption',
        'description' => 'Criptografia de dados sensíveis',
        'default' => false,
    ],
    'automated-backups' => [
        'name' => 'Automated Backups',
        'description' => 'Backups automáticos programados',
        'default' => false,
    ],
    'rls' => [
        'name' => 'Row Level Security',
        'description' => 'Isolamento de dados por linha',
        'default' => false,
    ],
    'advanced-rbac' => [
        'name' => 'Advanced RBAC',
        'description' => 'Controle de acesso granular',
        'default' => false,
    ],
],
```

### Arquitetura Pennant Multi-tenant
```
┌─────────────────┐
│ FeatureController│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ FeatureService  │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐  ┌─────────────────┐
│ Pennant│  │ ProjectFeature  │
│ (Core) │  │ Model           │
└────────┘  └─────────────────┘
                  │
                  ▼
           ┌─────────────┐
           │  Database   │
           │ (per project)│
           └─────────────┘
```

### Feature Service Implementation
```php
class FeatureService
{
    public function isActive(string $feature, EndUser $user): bool
    {
        // System admin bypass
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Project admin always sees
        if ($user->isAdmin() && $this->featureExists($feature)) {
            return true;
        }

        $config = $this->getFeatureConfig($feature);

        return match ($config->strategy) {
            'inactive' => false,
            'all' => true,
            'percentage' => $this->checkPercentage($user, $config->percentage),
            'users' => $config->user_ids->contains($user->id),
        };
    }

    // Deterministic percentage based on user hash
    private function checkPercentage(EndUser $user, int $percentage): bool
    {
        $hash = crc32($user->id);
        return ($hash % 100) < $percentage;
    }
}
```

### Middleware de Feature Flag
```php
class EnsureFeatureIsEnabled
{
    public function handle($request, Closure $next, string $feature)
    {
        $user = $request->user();

        // System admin bypass
        if ($user instanceof SystemUser) {
            return $next($request);
        }

        if (!FeatureService::isActive($feature, $user)) {
            return response()->json([
                'error' => 'feature_disabled',
                'message' => 'This feature is not available for your account',
            ], 403);
        }

        return $next($request);
    }
}
```

### Rota com Feature Flag
```php
// routes/api.php
Route::middleware(['auth:api', 'feature:realtime'])
    ->prefix('realtime/v1')
    ->group(function () {
        Route::post('/subscribe', [RealtimeController::class, 'subscribe']);
    });
```

### Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/system/projects/{id}/features` | Listar features |
| GET | `/system/projects/{id}/features/{feature}` | Detalhes da feature |
| POST | `/system/projects/{id}/features/{feature}/activate` | Ativar feature |
| POST | `/system/projects/{id}/features/{feature}/deactivate` | Desativar feature |
| PATCH | `/system/projects/{id}/features/{feature}` | Atualizar rollout |
| GET | `/system/projects/{id}/features/{feature}/history` | Histórico |
| POST | `/system/projects/{id}/features/{feature}/users` | Adicionar usuário |
| DELETE | `/system/projects/{id}/features/{feature}/users/{userId}` | Remover usuário |
| GET | `/api/v1/features` | Features ativas para mim |

### Files to Create
```
app/
├── Http/Controllers/System/
│   └── FeatureFlagController.php
├── Services/
│   ├── FeatureFlagService.php
│   └── FeatureRolloutService.php
├── Http/Middleware/
│   └── EnsureFeatureIsEnabled.php
├── Models/
│   ├── ProjectFeature.php
│   └── FeatureHistory.php
├── DTOs/
│   └── FeatureConfigDTO.php
└── Enums/
    └── RolloutStrategyEnum.php

config/
└── pennant.php

database/migrations/
├── create_project_features_table.php
└── create_feature_histories_table.php

resources/js/Pages/System/Features/
├── Index.vue
└── Show.vue
```

### Database Schema
```sql
-- Configuração de features por projeto
CREATE TABLE project_features (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES system_projects(id),
    feature_name VARCHAR(100) NOT NULL,
    strategy VARCHAR(20) NOT NULL DEFAULT 'inactive', -- inactive, percentage, users, all
    percentage INTEGER DEFAULT 0,
    user_ids UUID[] DEFAULT '{}',
    is_active BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(project_id, feature_name)
);

-- Histórico de mudanças
CREATE TABLE feature_histories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_feature_id UUID NOT NULL REFERENCES project_features(id),
    action VARCHAR(50) NOT NULL, -- activated, deactivated, updated
    actor_id UUID NOT NULL,
    actor_type VARCHAR(50) NOT NULL, -- SystemUser, EndUser
    previous_state JSONB,
    new_state JSONB,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Index para query rápida
CREATE INDEX idx_project_features_lookup ON project_features(project_id, feature_name);
```

---

## Security Considerations

- [ ] Apenas admins do projeto podem gerenciar features
- [ ] System admin bypass documentado e auditado
- [ ] Log de todas as mudanças para auditoria
- [ ] Validação de feature_name contra whitelist
- [ ] Rate limiting em endpoints de verificação
- [ ] Não expor lista de usuários beta para não-admins
