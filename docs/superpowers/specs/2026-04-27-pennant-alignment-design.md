# Feature Flags: Alinhamento ao Laravel Pennant

**Data:** 2026-04-27
**Status:** Draft

## Problema

Implementação atual de feature flags desvia do contrato oficial do Laravel Pennant em 7 pontos. O desvio principal é usar propriedade `$name` customizada em vez do atributo `#[Name]` do Pennant, quebrando lookup por string (`Feature::active('schema-builder')`).

## Desvios Identificados

| # | Desvio | Impacto |
|---|--------|---------|
| 1 | `$name` property em vez de `#[Name]` attribute | Pennant ignora a propriedade. Armazena por FQCN, não por nome. |
| 2 | `Feature::active('string')` pode não resolver | Sem `#[Name]`, string lookup falha. Rotas usam `feature:schema-builder`. |
| 3 | Registro manual de 12 classes | Pennant suporta `Feature::discover()` — auto-discovery. |
| 4 | CLAUDE.md documenta incorretamente | Afirma que Pennant lê `$name` property (falso). |

## O Que Muda

### 1. Atributo `#[Name]` nas Feature Classes

Cada classe concreta ganha o atributo PHP `#[Name]` do Pennant. Remove propriedade `$name`.

```php
// ANTES
class SchemaBuilder extends Feature
{
    public string $name = 'schema-builder';
}

// DEPOIS
use Laravel\Pennant\Attributes\Name;

#[Name('schema-builder')]
class SchemaBuilder extends Feature
{
}
```

### 2. Auto-Discovery no FeatureServiceProvider

```php
// ANTES — 12 linhas manuais
Feature::define(DatabaseCreator::class);
Feature::define(CredentialsManager::class);
// ... 10 mais

// DEPOIS — 1 linha
Feature::discover();
```

### 3. Base Class Limpa

Remove propriedade `$name` da classe abstrata `App\Features\Feature`. Mantém `before()` e `resolve()` inalterados.

```php
abstract class Feature
{
    // Remove: protected string $name = '';

    public function before(User $user): mixed { ... }
    public function resolve(User $user): mixed { ... }
    protected function isActiveByDefault(): bool { ... }
    public static function checkPercentage(string $userId, int $percentage): bool { ... }
}
```

## O Que NÃO Muda

| Componente | Razão |
|------------|-------|
| `FeatureSetting` model | Armazena config de estratégia (strategy, percentage, user_ids). Complementa Pennant, não duplica. Pennant guarda valores resolvidos por scope; FeatureSetting guarda config global. |
| `FeatureFlagService` | Admin UI CRUD. Atualiza FeatureSetting + purge Pennant. Único ponto de entrada pra mudanças via painel. |
| `config/features.php` | Metadata pra UI (description, implemented_at). Pennant usa `config/pennant.php` pra driver config. |
| `resolve()` lendo FeatureSetting | Necessário pra estratégias dinâmicas. Admin muda estratégia → purge → resolve() re-executa. |
| `before()` admin bypass | Já segue convenção Pennant. |
| Estratégias ALL / PERCENTAGE / USERS / INACTIVE | Mantidas no `resolve()`. |
| Frontend | `HandleInertiaRequests` → `getActiveFeaturesForUser()` → `activeFeatures` props. Inalterado. |
| Middleware `EnsureFeatureIsEnabled` | `Feature::active($feature)` funciona após `#[Name]`. |

## Estratégias Preservadas

```php
// ALL → true pra todo mundo
RolloutStrategyEnum::ALL => true,

// PERCENTAGE → random determinístico por user ID
RolloutStrategyEnum::PERCENTAGE => $this->checkPercentage((string) $user->id, $setting->percentage),

// USERS → lista específica de user IDs
RolloutStrategyEnum::USERS => in_array((string) $user->id, $setting->user_ids ?? []),

// INACTIVE → false
RolloutStrategyEnum::INACTIVE => false,
```

## Fluxo de Resolução (Alinhado ao Pennant)

```
Feature::active('schema-builder')
  → Pennant encontra classe via #[Name('schema-builder')] attribute
  → before($user) — admin bypass? retorna true
  → resolve($user) — lê FeatureSetting, aplica estratégia
  → Pennant armazena resultado na tabela `features` (cache)
  → Próximo check: lê do cache (sem re-resolver)
  → Admin muda estratégia → FeatureSetting::update + Feature::purge()
  → Próximo check: purge limpa cache → resolve() re-executa com nova estratégia
```

## Arquivos Afetados

| Arquivo | Ação |
|---------|------|
| `app/Features/Feature.php` | Remove `protected string $name = ''` |
| `app/Features/DatabaseCreator.php` | Adiciona `#[Name('database-creator')]`, remove `public string $name` |
| `app/Features/CredentialsManager.php` | Adiciona `#[Name('credentials-manager')]`, remove `public string $name` |
| `app/Features/SchemaBuilder.php` | Adiciona `#[Name('schema-builder')]`, remove `public string $name` |
| `app/Features/TableManager.php` | Adiciona `#[Name('table-manager')]`, remove `public string $name` |
| `app/Features/DynamicApi.php` | Adiciona `#[Name('dynamic-api')]`, remove `public string $name` |
| `app/Features/Realtime.php` | Adiciona `#[Name('realtime')]`, remove `public string $name` |
| `app/Features/Storage.php` | Adiciona `#[Name('storage')]`, remove `public string $name` |
| `app/Features/OtpAuth.php` | Adiciona `#[Name('otp-auth')]`, remove `public string $name` |
| `app/Features/DatabaseEncryption.php` | Adiciona `#[Name('database-encryption')]`, remove `public string $name` |
| `app/Features/AutomatedBackups.php` | Adiciona `#[Name('automated-backups')]`, remove `public string $name` |
| `app/Features/Rls.php` | Adiciona `#[Name('rls')]`, remove `public string $name` |
| `app/Features/AdvancedRbac.php` | Adiciona `#[Name('advanced-rbac')]`, remove `public string $name` |
| `app/Providers/FeatureServiceProvider.php` | Substitui 12 defines por `Feature::discover()` |
| `CLAUDE.md` | Corrige documentação sobre `#[Name]` attribute |

## Testes

- Verificar `Feature::active('schema-builder')` resolve corretamente
- Verificar `Feature::active(SchemaBuilder::class)` resolve corretamente
- Verificar middleware `feature:schema-builder` bloqueia/desbloqueia
- Verificar estratégias ALL, PERCENTAGE, USERS, INACTIVE
- Verificar admin bypass via `before()`
- Verificar `FeatureFlagService::activate()` → purge → re-resolve
