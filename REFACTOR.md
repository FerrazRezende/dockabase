# DockaBase - Documento de Refactor

> **Objetivo:** Este documento mapeia TODAS as incongruências, code smells, violações arquiteturais e pontos de melhoria da aplicação DockaBase. Serve como base para o primeiro grande refactor.

---

## Sumário

1. [Backend - Console/Commands](#1-consolecommands)
2. [Backend - DTOs](#2-dtos)
3. [Backend - Enums](#3-enums)
4. [Backend - Events](#4-events)
5. [Backend - Features (Pennant)](#5-features-pennant)
6. [Backend - Controllers](#6-controllers)
7. [Backend - Requests](#7-requests)
8. [Backend - Resources](#8-resources)
9. [Backend - Jobs](#9-jobs)
10. [Backend - Listeners](#10-listeners)
11. [Backend - Notifications](#11-notifications)
12. [Backend - Models](#12-models)
13. [Backend - Services](#13-services)
14. [Backend - Policies](#14-policies)
15. [Backend - Traits](#15-traits)
16. [Backend - Strategies](#16-strategies)
17. [Configuração Laravel](#17-configuração-laravel)
18. [Rotas](#18-rotas)
19. [Database / Migrations](#19-database--migrations)
20. [Frontend - Componentes](#20-frontend---componentes)
21. [Frontend - Pages](#21-frontend---pages)
22. [Frontend - Composables](#22-frontend---composables)
23. [Frontend - Layouts](#23-frontend---layouts)
24. [Frontend - Stores (Pinia)](#24-frontend---stores-pinia)
25. [Frontend - Tipos TypeScript](#25-frontend---tipos-typescript)
26. [Infra / Composição](#26-infra--composição)
27. [Testes](#27-testes)
28. [i18n / Traduções](#28-i18n--traduções)

---

## 1. Console/Commands

### Arquivos existentes

| Arquivo | Propósito | Status |
|---------|-----------|--------|
| `CleanupOldActivities.php` | Cronjob para limpar atividades antigas | **OK** - Mas confirmar se está no scheduler |
| `InitMinioBucketsCommand.php` | Criava bucket `profilepic` | **DELETAR** - Legado, substituído pelo próximo |
| `InitStorageCommand.php` | Cria bucket `dockabase` via S3Client | **OK** - Substitui o anterior |
| `SetAutoAwayStatus.php` | Seta users para away via polling | **REMOVER** - Auto-away deve ser real-time, não command com polling |

### Problemas

- **`InitMinioBucketsCommand.php`** é legado. Referencia bucket `profilepic` que não existe mais. Deve ser deletado junto com seu registro no `bootstrap/app.php`.
- **`SetAutoAwayStatus.php`** faz polling em todos os users via `User::chunk(100, ...)`. Isso não escala. O auto-away deveria ser resolvido via Redis TTL/heartbeat no front-end, não via command agendado.
- **`CleanupOldActivities.php`** precisa ser verificado se está registrado no scheduler (`routes/console.php`).

### Ações

- [ ] Deletar `InitMinioBucketsCommand.php` e remover registro do bootstrap
- [ ] Deletar `SetAutoAwayStatus.php` e remover registro do bootstrap/scheduler
- [ ] Implementar auto-away via TTL do Redis (heartbeat com expiry)
- [ ] Verificar se `CleanupOldActivities` está no scheduler

---

## 2. DTOs

### Estado atual

**Apenas 1 DTO:** `FeatureConfigDTO.php`

### Problemas

- A aplicação declara no CLAUDE.md que usa DTOs para "transferência entre camadas, imutáveis e tipados", mas só tem um.
- Ao mesmo tempo, tem Resources que fazem o mesmo papel de transformação de dados.
- **Decisão necessária:** Ou padronizamos em DTOs (e criamos para todas as entidades) ou usamos Resources como data transfer (e removemos o único DTO).

### Análise

| Abordagem | Prós | Contras |
|-----------|------|---------|
| **Resources apenas** | Já existem, Laravel nativo, simples | Menos tipagem no PHP |
| **DTOs + Resources** | Tipagem forte, separação clara | Mais boilerplate, duplicação |
| **DTOs apenas** | Tipagem forte, imutabilidade | Perde metadata do Laravel Resource |

### Recomendação

Usar **Resources como data transfer** e **remover o DTO**. Resources já fazem o trabalho de transformação e serialização. Se no futuro a complexidade exigir, introduzimos DTOs de forma gradual e consistente.

### Ações

- [ ] Decidir abordagem: Resources-only ou DTOs+Resources
- [ ] Se Resources-only: deletar `FeatureConfigDTO.php` e refatorar onde é usado
- [ ] Se DTOs+Resources: criar DTOs para todas as entidades principais

---

## 3. Enums

### Arquivos existentes

| Arquivo | Case dos cases | Tem `label()` | Tem `color()` | Tem `all()` | Usa `__()` |
|---------|---------------|---------------|---------------|-------------|------------|
| `CredentialPermissionEnum` | **CamelCase** ❌ | ✅ | ❌ | ❌ | ❌ (hardcoded EN) |
| `DatabaseCreationStepEnum` | **UPPER_CASE** ✅ | ✅ | ❌ | ❌ | ✅ |
| `RolloutStrategyEnum` | **CamelCase** ❌ | ✅ | ❌ | ❌ | ❌ (hardcoded PT) |
| `UserActivityTypeEnum` | **CamelCase** ❌ | ❌ | ❌ | ❌ | N/A |
| `UserStatusEnum` | **UPPER_CASE** ✅ | ✅ | ✅ | ✅ | ❌ (hardcoded PT) |

### Problemas

1. **Inconsistência de nomenclatura:** 3 enums usam CamelCase (`Read`, `Write`, `Inactive`, `StatusChanged`) e 2 usam UPPER_CASE (`VALIDATING`, `ONLINE`). PSR-12 e a convenção PHP para backed enums recomenda UPPER_CASE.

2. **Métodos faltantes:** `UserActivityTypeEnum` não tem nenhum método auxiliar. Deveria ter `label()` no mínimo.

3. **Hardcoded strings nos `label()`:**
   - `CredentialPermissionEnum::label()` retorna strings em inglês hardcoded ("Read Only")
   - `RolloutStrategyEnum::label()` retorna strings em português hardcoded ("Inativo")
   - `UserStatusEnum::label()` retorna strings em português hardcoded ("Ausente")
   - Só `DatabaseCreationStepEnum::label()` usa `__()`

4. **Falta de métodos utilitários:** `CredentialPermissionEnum` não tem `all()` ou `color()`. `RolloutStrategyEnum` não tem `all()`.

### Ações

- [ ] Padronizar TODOS os cases para UPPER_CASE
- [ ] Adicionar `label()` ao `UserActivityTypeEnum`
- [ ] Envolver todos os `label()` com `__()` em todos os enums
- [ ] Adicionar `all()` e outros métodos utilitários consistentemente
- [ ] Considerar adicionar `color()` ao `CredentialPermissionEnum` e `RolloutStrategyEnum`

---

## 4. Events

### Arquivos existentes

| Arquivo | Broadcast? | Canal |
|---------|-----------|-------|
| `DatabaseCreated.php` | ✅ | `database.{id}` |
| `DatabaseFailed.php` | ✅ | `database.{id}` |
| `DatabaseStepUpdated.php` | ✅ | `database.{id}` |
| `UserStatusUpdatedEvent.php` | ✅ | Presença |

### Problemas

1. **Nomenclatura inconsistente:** 3 usam suffix sem "Event" (`DatabaseCreated`), 1 usa "Event" (`UserStatusUpdatedEvent`). Padronizar sem suffix.
2. **Event faltante para Credential:** Não existe `UserAddedToCredential` / `UserRemovedFromCredential`. Quando um usuário é adicionado a uma credential, ele deveria ser notificado.
3. **Events faltantes para outras entidades:** Credential, User, Feature.

### Ações

- [ ] Padronizar nomenclatura: todos sem suffix "Event"
- [ ] Criar `UserAddedToCredential` event
- [ ] Criar `UserRemovedFromCredential` event
- [ ] Considerar `CredentialCreated` / `CredentialUpdated` events
- [ ] Registrar novos eventos no `EventServiceProvider`

---

## 5. Features (Pennant)

### Estado atual

**Pasta `app/Features/` está VAZIA.** Não existe no filesystem.

### Problemas

- O CLAUDE.md descreve que features devem ser organizadas como classes Pennant em `app/Features/`
- Atualmente as features são definidas em `config/features.php` (apenas 2) e resolvidas no `FeatureFlagService`
- Todas as features do CLAUDE.md estão declaradas como "available" mas **não existem como código**:
  - `schema-builder`, `table-manager`, `dynamic-api`, `realtime`, `storage`, `otp-auth`, `database-encryption`, `automated-backups`, `rls`, `advanced-rbac`
- Só existem no config: `database-creator` e `credentials-manager`

### Ações

- [ ] Criar pasta `app/Features/` com classes Pennant para cada feature
- [ ] Completar `config/features.php` com todas as features documentadas no CLAUDE.md
- [ ] Migrar lógica de resolução do `FeatureFlagService` para as classes Pennant

---

## 6. Controllers

### Arquivos existentes (27 controllers)

```
Controllers/
├── Auth/
│   ├── AuthenticatedSessionController.php
│   ├── ConfirmablePasswordController.php
│   ├── EmailVerificationNotificationController.php
│   ├── EmailVerificationPromptController.php
│   ├── NewPasswordController.php
│   ├── PasswordController.php
│   ├── PasswordResetLinkController.php
│   ├── RegisteredUserController.php
│   └── VerifyEmailController.php
├── Api/
│   ├── NotificationController.php
│   └── V1/FeatureController.php
├── App/
│   ├── CredentialController.php
│   └── DatabaseController.php
├── Profile/
│   ├── LocaleController.php
│   ├── ProfilePhotoController.php
│   └── ProfilePhotoRefreshController.php  ← NÃO UTILIZADO (rota comentada)
├── System/
│   ├── FeatureFlagController.php
│   ├── ImpersonateController.php
│   ├── PermissionController.php
│   ├── RoleController.php
│   └── UserController.php
├── AvatarController.php
├── Controller.php
├── LocaleController.php          ← DUPLICADO de Profile/LocaleController
├── ProfileController.php         ← Deveria estar em Profile/
├── UserController.php            ← DUPLICADO de System/UserController
├── UserStatusController.php
```

### Problemas identificados

#### 6.1 Controllers duplicados

| Controller Raiz | Equivalente em pasta | Diferença |
|----------------|---------------------|-----------|
| `LocaleController.php` | `Profile/LocaleController.php` | Raiz é para guests (set locale), Profile/ é para user autenticado (update preference). **Mesma responsabilidade, arquitetura diferente** |
| `UserController.php` | `System/UserController.php` | Raiz retorna lista simples de users. System/ é CRUD admin completo. **Deveria ser um só** |
| `ProfileController.php` (raiz) | Deveria estar em `Profile/` | Edit/update/destroy password - pertence ao domínio Profile |

#### 6.2 `ProfilePhotoRefreshController.php` - Código morto

Existe na pasta `Profile/` mas a rota está **comentada** no `web.php` (linha 41-42). Se não vai ser usado, deletar.

#### 6.3 `FeatureFlagController.php` vs `Api/V1/FeatureController.php`

- `System/FeatureFlagController.php` - CRUD completo de feature flags (admin, painel web)
- `Api/V1/FeatureController.php` - API read-only de features para end users

**OK** - São propósitos diferentes. Mas a nomenclatura é confusa. Considerar renomear para `System/FeatureController.php` e `Api/V1/FeatureStatusController.php` ou similar.

#### 6.4 Uso de `to_route()` em vez de `redirect()->back()`

| Arquivo | Linha | Código |
|---------|-------|--------|
| `App/CredentialController.php` | 70 | `return to_route('app.credentials.show', $credential);` |
| `App/CredentialController.php` | 107 | `return to_route('app.credentials.index');` |
| `App/DatabaseController.php` | 77 | `return to_route('app.databases.show', $database)` |
| `App/DatabaseController.php` | 122 | `return to_route('app.databases.index');` |

O padrão do projeto é `redirect()->back()`. Esses usam `to_route()`. Verificar qual é o padrão desejado e padronizar.

#### 6.5 Inline imports (FQN no corpo do método)

| Arquivo | Linha | Código |
|---------|-------|--------|
| `System/PermissionController.php` | 23 | `\Spatie\Permission\Models\Role::with(...)` |
| `UserStatusController.php` | 55 | `\App\Enums\UserStatusEnum::from(...)` |
| `UserStatusController.php` | 63 | `\App\Models\UserActivity::create(...)` |
| `Auth/RegisteredUserController.php` | 36 | `Rules\Password::defaults()` |
| `Auth/NewPasswordController.php` | 40 | `Rules\Password::defaults()` |

Todos os imports devem estar no topo do arquivo. Procurar por `\App\`, `\Spatie\`, etc. no corpo de todos os controllers.

#### 6.6 Falta `declare(strict_types=1)` em alguns controllers

`ProfileController.php` (raiz) não tem `declare(strict_types=1)`. O CLAUDE.md exige strict types em todos os arquivos PHP.

### Ações

- [ ] Mover `ProfileController.php` (raiz) para `Profile/ProfileController.php` ou `Profile/ProfileInformationController.php`
- [ ] Eliminar `UserController.php` (raiz) - mover funcionalidade para `System/UserController.php` ou `App/UserController.php`
- [ ] Decidir entre `LocaleController` (guest) e `Profile/LocaleController` (auth) - unificar ou separar claramente com nomes distintos
- [ ] Deletar `ProfilePhotoRefreshController.php` (código morto) ou implementar a rota
- [ ] Padronizar `to_route()` vs `redirect()->back()` em toda a aplicação
- [ ] Mover todos os inline imports para o topo dos arquivos
- [ ] Adicionar `declare(strict_types=1)` onde faltar
- [ ] Considerar renomear `System/FeatureFlagController` para `System/FeatureController`

---

## 7. Requests

### Arquivos existentes (21)

```
Requests/
├── Auth/
│   └── LoginRequest.php
├── Profile/
│   └── UpdateLocaleRequest.php
├── System/
│   ├── ActivateFeatureRequest.php
│   ├── CreateCredentialRequest.php      ← Deveria estar em Credential/
│   ├── CreateDatabaseRequest.php         ← Deveria estar em Database/
│   ├── StorePermissionRequest.php        ← OK (System)
│   ├── StoreRoleRequest.php              ← OK (System)
│   ├── StoreUserRequest.php              ← OK (System)
│   ├── SyncDirectPermissionsRequest.php  ← OK (System)
│   ├── SyncRolePermissionsRequest.php    ← OK (System)
│   ├── SyncUserPermissionsRequest.php    ← OK (System)
│   ├── UpdateCredentialRequest.php       ← Deveria estar em Credential/
│   ├── UpdateDatabaseRequest.php         ← Deveria estar em Database/
│   ├── UpdatePermissionRequest.php       ← OK (System)
│   ├── UpdateRoleRequest.php             ← OK (System)
│   ├── UpdateUserRequest.php             ← OK (System)
│   ├── UpdateUserRoleRequest.php         ← OK (System)
│   └── UpdateFeatureRequest.php          ← OK (System)
├── PasswordUpdateRequest.php             ← Deveria estar em Profile/
├── ProfileUpdateRequest.php              ← Deveria estar em Profile/
└── UpdateProfilePhotoRequest.php         ← Deveria estar em Profile/
```

### Problemas

1. **Requests de Credential e Database em `System/`:** Credential e Database NÃO são features de system (admin godlike). São features de usuário/app. Deveriam estar em pastas próprias: `Credential/` e `Database/`.

2. **Requests de Profile soltos na raiz:** `PasswordUpdateRequest`, `ProfileUpdateRequest`, `UpdateProfilePhotoRequest` deveriam estar em `Profile/`.

3. **Convenção de naming misturada:** `Create*Request` vs `Store*Request` para criação. `CreateCredentialRequest` vs `StoreUserRequest`. Padronizar.

### Estrutura proposta

```
Requests/
├── Auth/
│   └── LoginRequest.php
├── Profile/
│   ├── UpdateLocaleRequest.php
│   ├── PasswordUpdateRequest.php
│   ├── ProfileUpdateRequest.php
│   └── UpdateProfilePhotoRequest.php
├── Credential/
│   ├── CreateCredentialRequest.php
│   └── UpdateCredentialRequest.php
├── Database/
│   ├── CreateDatabaseRequest.php
│   └── UpdateDatabaseRequest.php
└── System/
    ├── StorePermissionRequest.php
    ├── StoreRoleRequest.php
    ├── StoreUserRequest.php
    ├── UpdatePermissionRequest.php
    ├── UpdateRoleRequest.php
    ├── UpdateUserRequest.php
    ├── UpdateUserRoleRequest.php
    ├── SyncDirectPermissionsRequest.php
    ├── SyncRolePermissionsRequest.php
    ├── SyncUserPermissionsRequest.php
    ├── ActivateFeatureRequest.php
    └── UpdateFeatureRequest.php
```

### Ações

- [ ] Mover Credential requests para `Requests/Credential/`
- [ ] Mover Database requests para `Requests/Database/`
- [ ] Mover Profile requests soltos para `Requests/Profile/`
- [ ] Padronizar naming: `Store` para criar, `Update` para atualizar (ou `Create` para criar)
- [ ] Atualizar imports nos controllers correspondentes

---

## 8. Resources

### Arquivos existentes (13)

```
Resources/
├── CredentialCollection.php
├── CredentialResource.php
├── DatabaseCollection.php
├── DatabaseResource.php
├── FeatureCollection.php
├── FeatureResource.php
├── PermissionResource.php
├── RoleResource.php
├── UserCollection.php
├── UserProfileResource.php
├── UserResource.php
├── SystemUserCollection.php
└── SystemUserResource.php
```

### Problemas

1. **Sem organização por pasta:** Todos soltos na raiz. Deveriam seguir a mesma organização dos controllers/requests por domínio.

2. **Duplicação de User resources:** `UserResource` + `UserCollection` e `SystemUserResource` + `SystemUserCollection`. Podem ser o mesmo resource com contexto diferente, ou deveriam estar em pastas separadas (`App/` vs `System/`).

3. **Missing resources:** Não existe `NotificationResource`, `ActivityResource`, `FeatureSettingResource`.

### Estrutura proposta

```
Resources/
├── App/
│   ├── CredentialResource.php
│   ├── CredentialCollection.php
│   ├── DatabaseResource.php
│   ├── DatabaseCollection.php
│   ├── UserResource.php
│   └── UserCollection.php
├── System/
│   ├── FeatureResource.php
│   ├── FeatureCollection.php
│   ├── PermissionResource.php
│   ├── RoleResource.php
│   ├── SystemUserResource.php
│   └── SystemUserCollection.php
├── Profile/
│   └── UserProfileResource.php
└── Api/
    └── NotificationResource.php
```

### Ações

- [ ] Organizar resources por domínio em subpastas
- [ ] Criar `NotificationResource` e `ActivityResource`
- [ ] Atualizar imports nos controllers

---

## 9. Jobs

### Estado atual

**1 Job:** `CreateDatabaseJob.php`

### Problemas

- O job é usado no `DatabaseController::store()` - **OK, está sendo usado**.
- Não existem jobs para outras operações assíncronas que poderiam precisar (ex: bulk credential operations, backups).

### Ações

- [ ] Confirmar que `CreateDatabaseJob` está OK (está)
- [ ] Considerar jobs futuros conforme features são implementadas

---

## 10. Listeners

### Arquivos existentes

| Arquivo | Event | Problema |
|---------|-------|----------|
| `CacheUserStatusListener.php` | `UserStatusUpdatedEvent` | **OK** - Cacheia status no Redis |
| `LogUserStatusChangeListener.php` | `UserStatusUpdatedEvent` | **PROBLEMÁTICO** |

### Problemas com `LogUserStatusChangeListener`

1. **Sempre loga `from: OFFLINE`:** Na linha 24, o `from` é hardcoded como `UserStatusEnum::OFFLINE`. Isso significa que sempre registra a transição como se viesse do OFFLINE, perdendo o status anterior real.
2. **O controller já faz o log:** No `UserStatusController::store()`, o log de activity já é feito diretamente. O listener é redundante e com dados incorretos.
3. **Dupla gravação:** O status change é logado duas vezes - uma pelo controller e outra pelo listener.

### Ações

- [ ] Deletar `LogUserStatusChangeListener.php`
- [ ] Remover do `EventServiceProvider.php`
- [ ] Remover o log do controller (`UserStatusController`) se o listener for corrigido, OU corrigir o listener para receber o status `from` correto via event e deletar o log do controller

---

## 11. Notifications

### Estado atual

**1 Notification:** `DatabaseCreatedNotification.php`

### Problemas

- Falta notificação para quando um usuário é adicionado a uma credential
- Falta notificação para mudança de role
- Falta notificação para features ativadas/desativadas
- Falta notificação de boas-vindas (welcome) para novos usuários

### Ações

- [ ] Criar `UserAddedToCredentialNotification.php`
- [ ] Criar `UserRemovedFromCredentialNotification.php` (ou combinar)
- [ ] Criar `RoleChangedNotification.php`
- [ ] Criar `WelcomeNotification.php` para novos usuários
- [ ] Considerar `FeatureStatusChangedNotification.php`

---

## 12. Models

### Arquivos existentes (8)

```
Models/
├── Credential.php
├── Database.php
├── DatabaseSchemaHistory.php
├── FeatureHistory.php
├── FeatureSetting.php
├── Notification.php
├── User.php
└── UserActivity.php
```

### Problemas

1. **Falta `declare(strict_types=1)` em alguns models** - Verificar cada um
2. **Falta Scopes `scopeOf{Entity}()`:** O CLAUDE.md define que scopes devem seguir `scopeOf{Entity}($query, $value)`. Verificar se existe.
3. **Falta Property Hooks:** O CLAUDE.md define uso de PHP 8.4 property hooks. Verificar se estão sendo usados.
4. **User model:** Referenciado como `App\Models\User` mas o CLAUDE.md menciona `App\Domain\Auth\Models\EndUser`. Decidir qual padrão seguir.

### Ações

- [ ] Verificar `declare(strict_types=1)` em todos os models
- [ ] Verificar se os scopes seguem o padrão `scopeOf{Entity}()`
- [ ] Verificar uso de Property Hooks do PHP 8.4
- [ ] Decidir entre `App\Models\User` e domínio-based

---

## 13. Services

### Arquivos existentes (7)

```
Services/
├── CredentialService.php
├── DatabaseProvisioningService.php
├── DatabaseService.php
├── FeatureFlagService.php
├── ProfilePictureService.php
├── UserActivityService.php
└── UserStatusService.php
```

### Problemas

1. **Sem organização por domínio:** Todos soltos na raiz. Considerar subpastas: `Database/`, `User/`, `Feature/`.
2. **`DatabaseService.php` vs `DatabaseProvisioningService.php`:** São responsabilidades diferentes (CRUD vs provisioning), mas nomes confusos. Considerar `DatabaseService` e `DatabaseProvisioningService` em subpasta `Database/`.
3. **Missing services:** `CredentialService` existe, mas não existe `NotificationService` (lógica está no controller).

### Ações

- [ ] Considerar organizar por domínio em subpastas
- [ ] Extrair lógica de notificação para `NotificationService`
- [ ] Verificar se services seguem o princípio "entrada → regras → saída" (sem busca de dados, sem eventos, sem transações)

---

## 14. Policies

### Arquivos existentes (3)

```
Policies/
├── CredentialPolicy.php
├── DatabasePolicy.php
└── FeaturePolicy.php
```

### Problemas

- Faltam policies para: `UserPolicy`, `RolePolicy`, `NotificationPolicy`
- As policies existentes precisam ser verificadas se estão sendo usadas nos controllers/requests

### Ações

- [ ] Criar `UserPolicy.php`
- [ ] Criar `RolePolicy.php`
- [ ] Verificar uso das policies existentes nos controllers

---

## 15. Traits

### Estado atual

**1 Trait:** `HasKsuid.php`

### Problemas

O CLAUDE.md lista traits que deveriam existir mas não existem:

| Trait | Propósito | Status |
|-------|-----------|--------|
| `HasKsuid` | Gerar KSUID automaticamente | ✅ Existe |
| `HasProject` | Scope para multi-tenant | ❌ Não existe |
| `Cacheable` | Cache keys helper | ❌ Não existe |
| `InteractsWithRedis` | Helpers Redis | ❌ Não existe |

### Ações

- [ ] Avaliar necessidade de cada trait listada no CLAUDE.md
- [ ] Criar conforme necessário durante o refactor
- [ ] Se não são necessários agora, documentar no CLAUDE.md como "planejados"

---

## 16. Strategies

### Estado atual

**Pasta `app/Strategies/` NÃO EXISTE.**

### Problemas

O CLAUDE.md define uso de Strategies para comportamentos variáveis (filtros, ordenação). Nenhuma existe. Será necessário quando a Dynamic REST API (Fase 5) for implementada.

### Ações

- [ ] Criar pasta `app/Strategies/` quando a Fase 5 for iniciada
- [ ] Por enquanto, adicionar nota no CLAUDE.md que Strategies são planejadas para Fase 5

---

## 17. Configuração Laravel

### config/features.php

**Problema crítico:** Apenas 2 features definidas, mas o CLAUDE.md lista 11.

```php
// Atual: só 2
'database-creator' => [...],
'credentials-manager' => [...],

// Faltando:
'schema-builder', 'table-manager', 'dynamic-api',
'realtime', 'storage', 'otp-auth',
'database-encryption', 'automated-backups', 'rls', 'advanced-rbac'
```

### config/auth.php

- Referencia `App\Models\User` (linha ~65). Se a arquitetura muda para domain-based, precisa atualizar.

### config/database.php

- Default username: `'root'` (deveria ser `env('DB_USERNAME', 'root')`)
- Default database: `'laravel'` (deveria ser `env('DB_DATABASE', 'dockabase')`)

### config/filesystems.php

- Configuração MinIO existe mas pode precisar de revisão após deletar `InitMinioBucketsCommand`.

### composer.json

- `"php": "^8.2"` mas CLAUDE.md especifica PHP 8.4+. Atualizar para `"^8.4"`.
- `"description"` genérica: "The skeleton application for Laravel". Deveria ser "DockaBase - BaaS Platform".
- `"name"` genérico: `"laravel/laravel"`. Deveria ser `"dockabase/dockabase"` ou similar.

### bootstrap/app.php

- Registra `InitMinioBucketsCommand` (legado, remover).
- Registra `SetAutoAwayStatus` (remover).

### Ações

- [ ] Completar `config/features.php` com todas as 11 features
- [ ] Corrigir defaults em `config/database.php`
- [ ] Atualizar `composer.json`: PHP version, name, description
- [ ] Remover registro de commands legados do bootstrap

---

## 18. Rotas

### Arquivos existentes

| Arquivo | Prefixo | Middleware | Propósito |
|---------|---------|------------|-----------|
| `web.php` | `/` | `web`, `auth` | Dashboard, Profile, Welcome |
| `auth.php` | `/` | - | Login, Register, Password Reset |
| `app.php` | `/app` | `web`, `auth` | Databases, Credentials, Users |
| `system.php` | `/system` | `web`, `auth` | Features, Permissions, Roles, Users, Impersonate |
| `api.php` | `/api` | `web`, `auth` | User Status, Notifications |
| `api_v1.php` | `/api/v1` | `auth:sanctum` | Feature Flags API |
| `console.php` | - | - | Artisan commands |
| `channels.php` | - | - | Broadcast channels |

### Problemas

1. **`api.php` usa middleware `web`:** A rota `/api/*` usa middleware `web` + `auth` em vez de `auth:sanctum`. Isso faz as rotas API dependerem de sessão, não de token. Se é intencional (painel admin fazendo AJAX), ok. Se não, deveria usar sanctum.

2. **`api_v1.php` separado de `api.php`:** Dois arquivos de API com middleware diferente. Considerar unificar ou documentar a diferença clara:
   - `api.php` = Internal API (session-based, para o painel)
   - `api_v1.php` = External API (token-based, para end users)

3. **UserController em duas rotas:** `UserController` (raiz) é usado em `app.php` para `/app/users`. `System/UserController` é usado em `system.php` para `/system/users`. O da raiz só lista, o do System é CRUD completo.

4. **`ProfilePhotoRefreshController` rota comentada:** Linha 41-42 do `web.php`. Deletar controller e comentário se não vai ser usado.

5. **Inline imports nas rotas:** `web.php` linha 20 e 33 usam FQN inline (`App\Http\Controllers\LocaleController::class`). Importar no topo.

6. **Dashboard como closure:** `web.php` linha 22-24 renderiza Dashboard via closure. Considerar criar `DashboardController`.

### Ações

- [ ] Documentar diferença clara entre `api.php` (internal) e `api_v1.php` (external)
- [ ] Limpar rota comentada do `ProfilePhotoRefreshController`
- [ ] Mover inline imports para o topo nos arquivos de rota
- [ ] Considerar criar `DashboardController`
- [ ] Verificar se `api.php` deveria usar sanctum

---

## 19. Database / Migrations

### Migrations existentes (23)

**Problemas:**

1. **Missing foreign keys em:**
   - `features` table - sem FK para users/roles
   - `feature_settings` table - sem FK para features
   - `notifications` table - sem FK para users (pode ser intencional para polimorfismo)
   - `user_activities` table - verificar FK para users

2. **Missing indexes:**
   - `users.email` - frequentemente queryado, sem index
   - `users.is_active` - sem index
   - `user_activities.user_id` - sem index
   - `user_activities.created_at` - sem index para queries de range

3. **Coluna `avatar` em users:** Usa `string` mas o path pode ser longo. Considerar `text`.

### Seeders

- `DatabaseSeeder.php`
- `RolePermissionSeeder.php`
- `UserSeeder.php`

### Ações

- [ ] Adicionar indexes nas colunas frequentemente queryadas
- [ ] Verificar foreign keys faltantes
- [ ] Considerar mudar coluna `avatar` de `string` para `text`

---

## 20. Frontend - Componentes

### Estrutura atual

```
resources/js/
├── Components/          ← PASTA ANTIGA (Breeze stubs, PascalCase imports)
│   ├── ApplicationLogo.vue
│   ├── Checkbox.vue
│   ├── DangerButton.vue
│   ├── Dropdown.vue
│   ├── DropdownLink.vue
│   ├── InputError.vue
│   ├── InputLabel.vue
│   ├── Modal.vue
│   ├── NavLink.vue
│   ├── PrimaryButton.vue
│   ├── ResponsiveNavLink.vue
│   ├── SecondaryButton.vue
│   ├── TextInput.vue
│   └── ui/sidebar/     ← Sidebar components (5 arquivos)
├── components/          ← PASTA NOVA (kebab-case imports, shadcn-vue)
│   ├── ui/              ← shadcn-vue components (~70+ arquivos)
│   ├── user/            ← User-related (3 arquivos)
│   ├── ConfirmDialog.vue
│   ├── CreationTimeline.vue
│   ├── ImpersonateBanner.vue
│   └── NotificationCenter.vue
```

### Problemas

#### 20.1 Duas pastas de componentes: `Components/` vs `components/`

- **`Components/`** (PascalCase) = Componentes da **aplicação** (Sidebar, Nav, Dropdown, Buttons customizados, etc.)
- **`components/`** (kebab-case) = Componentes **shadcn-vue** (biblioteca UI copiada) + componentes custom novos

**Problema:** Dois diretórios com case diferente causam confusão de imports (`@/Components/` vs `@/components/`). No Linux (case-sensitive), são pastas diferentes. A decisão precisa ser:
- Unificar em uma só pasta (ex: tudo em `components/` com kebab-case)
- Ou separar claramente por propósito: `components/ui/` (shadcn) e `components/app/` (aplicação)

**Nota:** Os componentes em `Components/` (Breeze stubs como PrimaryButton, TextInput, etc.) têm equivalente direto no shadcn-vue. A sidebar em `Components/ui/sidebar/` está na pasta "errada" (deveria estar junto dos outros componentes da aplicação ou na pasta shadcn).

#### 20.2 Componentes da aplicação vs componentes shadcn-vue

Os componentes em `Components/` (PrimaryButton, DangerButton, TextInput, etc.) são wrappers customizados da aplicação, NÃO apenas stubs do Breeze. No entanto, muitos têm equivalente direto no shadcn-vue e podem ser consolidados:

| Componente App (`Components/`) | Equivalente shadcn-vue (`components/ui/`) |
|---|---|
| `PrimaryButton.vue` | `button/Button.vue` |
| `DangerButton.vue` | `button/Button.vue` (variant: destructive) |
| `SecondaryButton.vue` | `button/Button.vue` (variant: secondary) |
| `Checkbox.vue` | `checkbox/Checkbox.vue` |
| `InputError.vue` | `form/FormMessage.vue` |
| `InputLabel.vue` | `form/FormLabel.vue` |
| `TextInput.vue` | `input/Input.vue` |
| `Modal.vue` | `dialog/Dialog.vue` |
| `Dropdown.vue` | `dropdown-menu/*.vue` |
| `ApplicationLogo.vue` | Sem equivalente - manter |
| `NavLink.vue` | Sem equivalente direto - pode ser mantido como wrapper |
| `ResponsiveNavLink.vue` | Sem equivalente direto - pode ser mantido como wrapper |
| `DropdownLink.vue` | Pode usar `DropdownMenuItem` do shadcn |

A sidebar em `Components/ui/sidebar/` está na pasta da aplicação, mas é do tipo shadcn. Deveria estar em `components/ui/sidebar/`.

#### 20.3 Sidebar em lugar errado

`Components/ui/sidebar/` está na pasta antiga (PascalCase). Deveria estar em `components/ui/sidebar/`.

#### 20.4 Componentes sem TypeScript

Todos os componentes em `Components/` (Breeze) usam `<script setup>` sem `lang="ts"`. Muitos componentes `ui/` do shadcn-vue também.

### Ações

- [ ] **Decidir organização:** Unificar tudo em `components/` ou manter separação clara com subpastas (`components/ui/` para shadcn, `components/app/` para aplicação)
- [ ] Mover `Components/ui/sidebar/` para `components/ui/sidebar/`
- [ ] Migrar componentes que têm equivalente shadcn-vue (PrimaryButton, DangerButton, TextInput, etc.)
- [ ] Manter componentes únicos (ApplicationLogo, NavLink, ResponsiveNavLink) - mover para pasta unificada
- [ ] Adicionar `lang="ts"` a componentes que não têm
- [ ] Padronizar todos os imports para um único path base

---

## 21. Frontend - Pages

### Arquivos existentes (28)

```
Pages/
├── App/
│   ├── Credentials/ (Create, Index, Show)
│   └── Databases/ (Create, Index, Show)
├── Auth/
│   ├── ConfirmPassword.vue       ← Usa componentes Breeze antigos
│   ├── ForgotPassword.vue        ← Usa componentes Breeze antigos
│   ├── ForcePasswordChange.vue
│   ├── Login.vue
│   ├── Register.vue
│   ├── ResetPassword.vue         ← Usa componentes Breeze antigos
│   └── VerifyEmail.vue           ← Usa componentes Breeze antigos
├── Profile/
│   ├── Edit.vue
│   └── Partials/
│       ├── DeleteUserForm.vue    ← Usa componentes Breeze antigos
│       ├── LocaleForm.vue
│       ├── ProfilePhotoCard.vue
│       ├── ProfilePhotoDialog.vue
│       ├── UpdatePasswordForm.vue
│       └── UpdateProfileInformationForm.vue
├── System/
│   ├── Features/ (Index, Show)
│   ├── Permissions/ (Index)
│   ├── Roles/ (Form)
│   └── Users/ (Index, Show)
├── Dashboard.vue
└── Welcome.vue
```

### Problemas

1. **5 páginas usam componentes Breeze antigos:** `ConfirmPassword`, `ForgotPassword`, `ResetPassword`, `VerifyEmail`, `DeleteUserForm`. Precisam migrar para shadcn-vue.

2. **Hardcoded strings sem `__()`:**
   - `Dashboard.vue:30` - `"Bem-vindo, {{ auth.user.name }}!"`
   - `ConfirmDialog.vue` - `"Cancelar"`, `"Excluir"`, `"Confirmar"`
   - `CreationTimeline.vue:77` - `"Progresso"`
   - `NotificationCenter.vue:145` - `"Notificacoes"`
   - `AuthenticatedLayout.vue:270` - `"Usuários"`
   - `Roles/Form.vue` - `"Cancelar"`, `"Criar"`, `"Salvar"`, `"Role"`

3. **`: any` em TypeScript:** `System/Users/Show.vue` tem 6 usos de `: any` (linhas 205, 216, 217, 227, 244, 248).

### Ações

- [ ] Migrar 5 páginas Auth/Profile para shadcn-vue
- [ ] Envolver todas as strings hardcoded com `__()`
- [ ] Eliminar `: any` em `System/Users/Show.vue` criando tipos adequados

---

## 22. Frontend - Composables

### Arquivos existentes (7)

```
composables/
├── echo.ts
├── useDarkMode.js         ← JavaScript, deveria ser TypeScript
├── useEcho.ts
├── useEchoChannels.ts
├── useLang.ts
├── usePermissions.ts
├── useToast.ts
└── useUserStatus.ts
```

### Problemas

1. **`echo.ts` vs `useEcho.ts`:** Dois arquivos de echo. Verificar se são duplicados ou responsabilidades diferentes.
2. **`useDarkMode.js`:** Único composable em JavaScript. Deveria ser `.ts`.
3. **Composables faltantes:** `useNotification`, `useCredential`, `useDatabase` (quando houver stores Pinia).

### Ações

- [ ] Converter `useDarkMode.js` para `useDarkMode.ts`
- [ ] Verificar se `echo.ts` e `useEcho.ts` são duplicados
- [ ] Se sim, consolidar em um só arquivo

---

## 23. Frontend - Layouts

### Arquivos existentes

```
Layouts/
├── AuthenticatedLayout.vue    ← tem lang="ts" ✅
└── GuestLayout.vue            ← sem lang="ts" ❌
```

### Problemas

1. **`GuestLayout.vue` sem TypeScript:** Usa `<script setup>` sem `lang="ts"`.
2. **`GuestLayout.vue` importa de `@/Components/`:** Usa `ApplicationLogo` da pasta antiga.
3. **Hardcoded strings em `AuthenticatedLayout.vue`:** Sidebar tem texto em PT sem `__()`.

### Ações

- [ ] Adicionar `lang="ts"` ao `GuestLayout.vue`
- [ ] Mover import de `ApplicationLogo` para shadcn-vue ou novo component
- [ ] Envolver strings hardcoded com `__()`

---

## 24. Frontend - Stores (Pinia)

### Estado atual

**Pasta `stores/` está VAZIA.** Nenhum store Pinia implementado.

### Problemas

- Pinia está no `package.json` mas não é usado
- Estados complexos (notifications, user status) são gerenciados via composables com `ref()` locais
- Sem persistência de estado entre componentes

### Ações

- [ ] Decidir quais estados precisam de stores Pinia
- [ ] Criar stores para: `auth`, `notification`, `userStatus`, `features`
- [ ] Migrar lógica de composables para stores quando apropriado

---

## 25. Frontend - Tipos TypeScript

### Arquivos existentes (6)

```
types/
├── credential.ts
├── database.ts
├── feature.ts
├── global.d.ts
├── notification.ts
├── user.ts
└── user-status.ts
```

### Problemas

1. **Tipos faltantes:** Não existe `role.ts`, `permission.ts`, `activity.ts`
2. **`: any` em uso:** `System/Users/Show.vue` usa `: any` em vez de tipos desses arquivos
3. **`global.d.ts`** precisa ser verificado se tem todas as page props necessárias

### Ações

- [ ] Criar `types/role.ts` e `types/permission.ts`
- [ ] Criar `types/activity.ts`
- [ ] Eliminar `: any` do código usando os tipos adequados
- [ ] Verificar `global.d.ts` para completude

---

## 26. Infra / Composição

### docker-compose / Docker

- Verificar se os serviços declarados (PostgreSQL, Redis, RabbitMQ, MinIO, Reverb) estão todos configurados corretamente.

### package.json

- Verificar se todas as dependências front-end estão atualizadas
- Verificar se há dependências não utilizadas

### vite.config.ts

- Verificar configuração de aliases (`@/Components` vs `@/components`)
- Deve resolver a ambiguidade de pastas

### tsconfig.json

- Verificar se `strict: true` está configurado (CLAUDE.md exige)
- Verificar se paths estão corretos

### Ações

- [ ] Verificar docker-compose.yml contra stack do CLAUDE.md
- [ ] Limpar aliases duplicados no vite.config.ts após unificar pasta de componentes
- [ ] Confirmar `strict: true` no tsconfig.json

---

## 27. Testes

### Estrutura atual

```
tests/
├── Feature/ (~48 testes)
│   ├── Auth/
│   ├── System/
│   ├── Profile/
│   ├── Middleware/
│   ├── Lang/
│   ├── Database/
│   └── User/
└── Unit/ (~34 testes)
    ├── DTOs/
    ├── Enums/
    ├── Models/
    ├── Notifications/
    ├── Policies/
    ├── Services/
    ├── Commands/
    ├── Listeners/
    └── Events/
```

### Problemas

1. **Testes de componentes deletados:** Se `LogUserStatusChangeListener` e `SetAutoAwayStatus` forem deletados, seus testes também devem ser removidos.
2. **Faltam testes para:** `ProfilePictureService`, `CredentialService`, Policies
3. **Estrutura `tests/Unit/Domain/` e `tests/Feature/`:** O CLAUDE.md define estrutura `tests/Unit/Domain/{Api,Auth,Database}/` mas a atual é flat.
4. **Teste de translations:** `TranslationKeysTest.php` existe - bom.

### Ações

- [ ] Remover testes de componentes deletados
- [ ] Adicionar testes para services sem cobertura
- [ ] Considerar reorganizar para estrutura domain-based conforme CLAUDE.md

---

## 28. i18n / Traduções

### Estado atual

- 3 arquivos: `lang/pt.json`, `lang/en.json`, `lang/es.json`
- Teste `TranslationKeysTest.php` verifica sincronia

### Problemas

1. **Enum labels hardcoded:** 3 de 4 enums com `label()` têm strings hardcoded (PT ou EN) sem `__()`.
2. **Frontend:** Múltiplas strings hardcoded em Vue components (listadas na seção 21).
3. **Backend:** Verificar se controllers/services usam `__()` em mensagens de erro/sucesso.
4. **Strings nos configs:** `config/features.php` tem description em PT hardcoded.

### Ações

- [ ] Auditar todos os enums para usar `__()` nos labels
- [ ] Auditar todos os componentes Vue para strings hardcoded
- [ ] Auditar controllers para mensagens com `__()`
- [ ] Adicionar chaves faltantes nos 3 arquivos de tradução

---

## Priorização do Refactor

### P0 - Crítico (Resolver primeiro)

1. Deletar código morto: `InitMinioBucketsCommand`, `SetAutoAwayStatus`, `LogUserStatusChangeListener`, `ProfilePhotoRefreshController`
2. Padronizar Enums (UPPER_CASE + `__()` nos labels)
3. Reorganizar Requests por domínio
4. Unificar controllers duplicados
5. Completar `config/features.php`

### P1 - Importante (Resolver em seguida)

6. Organizar Resources por domínio
7. Mover inline imports para topo dos arquivos
8. Adicionar `declare(strict_types=1)` onde falta
9. Criar Events/Notifications faltantes (UserAddedToCredential)
10. Atualizar `composer.json` (PHP version, name, description)

### P2 - Melhoria (Resolver por último)

11. Migrar componentes Breeze para shadcn-vue
12. Deletar pasta `Components/` antiga
13. Unificar imports para `@/components/`
14. Adicionar `lang="ts"` em componentes sem TypeScript
15. Eliminar `: any` do TypeScript
16. Envolver strings hardcoded com `__()` (frontend e backend)
17. Converter `useDarkMode.js` para TypeScript
18. Criar Pinia stores
19. Adicionar indexes e FKs nas migrations
20. Criar pasta `app/Features/` com classes Pennant

---

## Notas para o Agente Executor

- **Não quebrar funcionalidade:** Cada item deve ser feito de forma atômica, com testes passando antes e depois.
- **Rodar `php artisan test`** após cada mudança backend.
- **Rodar `npm run build`** (ou `npm run dev`) após mudanças frontend.
- **Seguir TDD:** Testes primeiro, implementação depois (conforme CLAUDE.md).
- **Commits atômicos:** Um commit por item ou grupo lógico de items.
- **Atualizar imports:** Ao mover arquivos, atualizar TODOS os imports.
