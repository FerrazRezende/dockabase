# Feature Flags Refactor - Design Document

**Data:** 2026-03-30
**Branch:** fix/feature-flags-refactor

## Objetivo

Refatorar o sistema de feature flags para:
1. Remover features mock (manter apenas features implementadas)
2. Ativar features automaticamente baseado no ambiente
3. Restringir menu do admin para Features + Usuários

## Contexto Atual

- `config/features.php` contém 12 features (maioria não implementada)
- Feature flags são controladas manualmente via banco de dados
- Admin vê todos os menus (Databases, Credentials, Features)

## Mudanças

### 1. Config de Features (`config/features.php`)

Manter apenas features implementadas com campo `implemented_at`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | First Deploy Date
    |--------------------------------------------------------------------------
    |
    | Data do primeiro deploy em produção. Features com implemented_at
    | anterior ou igual a esta data são ativadas por padrão em prod.
    | Em dev/local, todas as features implementadas são ativadas.
    |
    */
    'first_deploy_date' => env('FEATURES_FIRST_DEPLOY_DATE'),

    'definitions' => [
        'database-creator' => [
            'name' => 'Database Creator',
            'description' => 'Interface para criar e gerenciar databases PostgreSQL',
            'implemented_at' => '2026-03-15',
        ],
        'credentials-manager' => [
            'name' => 'Credentials Manager',
            'description' => 'Gerenciamento de credenciais de acesso ao sistema',
            'implemented_at' => '2026-03-20',
        ],
    ],
];
```

### 2. Variável de Ambiente

Adicionar ao `.env` e `.env.example`:

```env
FEATURES_FIRST_DEPLOY_DATE=2026-03-30
```

### 3. FeatureFlagService

Adicionar método `isFeatureActiveByDefault()`:

```php
protected function isFeatureActiveByDefault(string $featureName): bool
{
    $env = config('app.env');

    // Dev/Local/Testing: todas as features implementadas ativas
    if (in_array($env, ['local', 'development', 'dev', 'testing'])) {
        return true;
    }

    // Production: features até FIRST_DEPLOY_DATE ativas
    $feature = config("features.definitions.{$featureName}");
    $deployDate = config('features.first_deploy_date');

    if (!$feature['implemented_at'] ?? null) {
        return false;
    }

    if (!$deployDate) {
        return false;
    }

    return Carbon::parse($feature['implemented_at'])
        ->lte(Carbon::parse($deployDate));
}
```

Modificar `isActiveForUser()` para usar o default quando não houver setting no banco:

```php
public function isActiveForUser(string $featureName, User $user): bool
{
    // God Admin always sees all features
    if ($user->is_admin === true) {
        return true;
    }

    $setting = FeatureSetting::where('feature_name', $featureName)->first();

    // Se não há setting no banco, usa o default por ambiente
    if (!$setting) {
        return $this->isFeatureActiveByDefault($featureName);
    }

    // Se há setting mas está inativo, feature desativada
    if (!$setting->is_active) {
        return false;
    }

    // Se há setting ativo, segue a estratégia definida
    return match ($setting->strategy) {
        RolloutStrategyEnum::All => true,
        RolloutStrategyEnum::Percentage => $this->checkPercentage($user->id, $setting->percentage),
        RolloutStrategyEnum::Users => in_array($user->id, $setting->user_ids ?? []),
        RolloutStrategyEnum::Inactive => false,
    };
}
```

### 4. FeatureServiceProvider

Atualizar `resolveFeature()` para usar a mesma lógica:

```php
protected function resolveFeature(User $user, string $featureName): bool
{
    // God Admin always has access to all features
    if ($user->is_admin === true) {
        return true;
    }

    $setting = FeatureSetting::where('feature_name', $featureName)->first();

    // Se não há setting, usa default por ambiente
    if (!$setting) {
        return $this->isFeatureActiveByDefault($featureName);
    }

    // Se há setting inativo, feature desativada
    if (!$setting->is_active) {
        return false;
    }

    return match ($setting->strategy) {
        'all' => true,
        'percentage' => $this->checkPercentage($user->id, $setting->percentage),
        'users' => in_array($user->id, $setting->user_ids ?? []),
        default => false,
    };
}

protected function isFeatureActiveByDefault(string $featureName): bool
{
    $env = config('app.env');

    if (in_array($env, ['local', 'development', 'dev', 'testing'])) {
        return true;
    }

    $feature = config("features.definitions.{$featureName}");
    $deployDate = config('features.first_deploy_date');

    if (!($feature['implemented_at'] ?? null) || !$deployDate) {
        return false;
    }

    return Carbon::parse($feature['implemented_at'])
        ->lte(Carbon::parse($deployDate));
}
```

### 5. Menu do Admin (`AuthenticatedLayout.vue`)

Estrutura do menu:

```
Usuário comum (não admin):
├── Home
├── Databases
└── Credentials

Admin:
├── Home
└── Sistema
    ├── Features
    └── Usuários
```

Mudanças no template:
- Databases e Credentials: adicionar `v-if="!auth.user.is_admin"`
- Sistema: adicionar link para Users

### 6. Rota de Usuários (se necessário)

Verificar se já existe rota de listagem de usuários para admin. Se não, criar.

### 7. UI de Features - Toggle

Substituir dropdown menu por toggle switch direto na tabela.

**Features/Index.vue - Mudanças:**

- Remover dropdown menu com "Ativar/Desativar/Configurar"
- Adicionar Switch component na coluna Status
- Ao clicar no toggle: ativa/desativa feature instantaneamente
- Manter link para página de detalhes na coluna do nome

**Layout da tabela:**

| Feature | Descrição | Status (Toggle) | Estratégia | Rollout |
|---------|-----------|-----------------|------------|---------|
| Database Creator | ... | [Switch ON] | Todos | 100% |
| Credentials Manager | ... | [Switch OFF] | - | - |

## Arquivos Modificados

| Arquivo | Ação |
|---------|------|
| `config/features.php` | Reduzir para 2 features + adicionar `first_deploy_date` |
| `.env` | Adicionar `FEATURES_FIRST_DEPLOY_DATE=2026-03-30` |
| `.env.example` | Adicionar `FEATURES_FIRST_DEPLOY_DATE=` |
| `app/Services/FeatureFlagService.php` | Adicionar `isFeatureActiveByDefault()` |
| `app/Providers/FeatureServiceProvider.php` | Adicionar `isFeatureActiveByDefault()` |
| `resources/js/Layouts/AuthenticatedLayout.vue` | Ajustar menu por role |
| `resources/js/Pages/System/Features/Index.vue` | Substituir dropdown por toggle switch |
| `routes/web.php` | Verificar/adicionar rota de users |
| `app/Http/Controllers/UserController.php` | Verificar se existe index para admin |

## Comportamento por Ambiente

| Ambiente | Features Ativas |
|----------|-----------------|
| local, development, dev, testing | Todas as implementadas |
| production | Apenas features com `implemented_at <= FEATURES_FIRST_DEPLOY_DATE` |

## Fluxo de Decisão

```
Feature check request
       │
       ▼
   is_admin? ─────yes────► return true
       │
       no
       │
       ▼
   Setting no banco?
       │
       ├──yes──► is_active? ──no──► return false
       │              │
       │             yes
       │              │
       │              ▼
       │         Seguir strategy (all/percentage/users)
       │
       └──no───► isFeatureActiveByDefault()
                      │
                      ▼
                 Dev/Local? ──yes──► return true
                      │
                      no
                      │
                      ▼
                 implemented_at <= first_deploy_date? ──yes──► return true
                      │
                      no
                      │
                      ▼
                 return false
```
