# DockaBase - Refactoring P0 Design

> **Branch:** `refac1`
> **Data:** 2026-04-12
> **Artefatos:** `docs/refactoring/`

---

## Decisoes Tomadas

| Decisao | Escolha |
|---------|---------|
| Estrategia de branch | Branch unico (`refac1`) com commits atomicos |
| Escopo | P0 completo (critico) |
| DTOs | Resources-only - deletar `FeatureConfigDTO` |
| Auto-away | Deletar `SetAutoAwayStatus` + implementar heartbeat Redis |
| Frontend | Unificacao completa (migrar Breeze -> shadcn-vue) |
| Sequencia | Backend-first |

---

## Regra Cross-Cutting

**`php artisan test` apos CADA fase.** Se quebrar, corrige antes de avancar.

---

## Fase 0: Test Suite Green

**Objetivo:** Corrigir todos os testes que estao falhando agora, antes de qualquer refactor.

### Acoes

- [ ] Rodar `php artisan test` e catalogar todas as falhas
- [ ] Corrigir cada falha (fixtures, factories, assertions, imports quebrados)
- [ ] Garantir suite 100% verde antes de prosseguir

### Criterio de saida

- `php artisan test` com 0 failures e 0 errors

---

## Fase 1: Cleanup (Deletar Codigo Morto)

### Acoes

- [ ] Deletar `app/Console/Commands/InitMinioBucketsCommand.php`
- [ ] Deletar `app/Console/Commands/SetAutoAwayStatus.php`
- [ ] Deletar `app/Listeners/LogUserStatusChangeListener.php`
- [ ] Deletar `app/Http/Controllers/Profile/ProfilePhotoRefreshController.php`
- [ ] Deletar `app/DTOs/FeatureConfigDTO.php`
- [ ] Remover registros de commands deletados em `bootstrap/app.php`
- [ ] Remover `LogUserStatusChangeListener` do `EventServiceProvider.php`
- [ ] Deletar testes de codigo morto:
  - `tests/Unit/Commands/SetAutoAwayStatusTest.php`
  - `tests/Unit/Listeners/LogUserStatusChangeListenerTest.php`
  - `tests/Unit/Commands/CleanupOldActivitiesTest.php` (se associado a command deletado)
  - `tests/Unit/DTOs/FeatureConfigDTOTest.php` (se existir)
- [ ] Rodar `php artisan test` - tudo verde

---

## Fase 2: Padronizar Enums

### Mudancas por Enum

| Enum | Mudancas |
|------|----------|
| `CredentialPermissionEnum` | Cases: `Read` -> `READ`, `Write` -> `WRITE`, `ReadWrite` -> `READ_WRITE`. Labels com `__()` |
| `RolloutStrategyEnum` | Cases: `Inactive` -> `INACTIVE`, `Percentage` -> `PERCENTAGE`, etc. Labels com `__()` |
| `UserActivityTypeEnum` | Adicionar metodo `label()` com `__()` |
| `UserStatusEnum` | Labels: hardcoded PT -> `__()` |
| `DatabaseCreationStepEnum` | Ja OK, apenas verificar |

### Acoes

- [ ] Refatorar cada enum para UPPER_CASE
- [ ] Envolver todos os `label()` com `__()`
- [ ] Adicionar `label()` ao `UserActivityTypeEnum`
- [ ] Adicionar chaves de traducao em `lang/pt.json`, `lang/en.json`, `lang/es.json`
- [ ] Atualizar todos os usos dos cases antigos (controllers, services, tests)
- [ ] Atualizar tests de enums para refletir novos cases
- [ ] Rodar `php artisan test` - tudo verde

---

## Fase 3: Reorganizar Estrutura Backend

### 3a. Requests por Dominio

| De | Para |
|----|------|
| `System/CreateCredentialRequest` | `Credential/CreateCredentialRequest` |
| `System/UpdateCredentialRequest` | `Credential/UpdateCredentialRequest` |
| `System/CreateDatabaseRequest` | `Database/CreateDatabaseRequest` |
| `System/UpdateDatabaseRequest` | `Database/UpdateDatabaseRequest` |
| `PasswordUpdateRequest` (raiz) | `Profile/PasswordUpdateRequest` |
| `ProfileUpdateRequest` (raiz) | `Profile/ProfileUpdateRequest` |
| `UpdateProfilePhotoRequest` (raiz) | `Profile/UpdateProfilePhotoRequest` |

### 3b. Controllers

- [ ] Mover `ProfileController.php` (raiz) -> `Profile/ProfileInformationController.php`
- [ ] Deletar `UserController.php` (raiz) - funcionalidade absorvida por `System/UserController`
- [ ] Resolver `LocaleController` (raiz, guest) vs `Profile/LocaleController` (auth) - renomear para `GuestLocaleController` e `Profile/LocaleController`
- [ ] Atualizar routes correspondentes

### 3c. Resources por Dominio

| Resource | Destino |
|----------|---------|
| `CredentialResource` + `CredentialCollection` | `Resources/App/` |
| `DatabaseResource` + `DatabaseCollection` | `Resources/App/` |
| `UserResource` + `UserCollection` | `Resources/App/` |
| `FeatureResource` + `FeatureCollection` | `Resources/System/` |
| `PermissionResource`, `RoleResource` | `Resources/System/` |
| `SystemUserResource` + `SystemUserCollection` | `Resources/System/` |
| `UserProfileResource` | `Resources/Profile/` |

### Acoes por sub-fase

- [ ] Mover requests, atualizar imports nos controllers
- [ ] Reorganizar controllers, atualizar routes
- [ ] Mover resources, atualizar imports nos controllers
- [ ] Rodar `php artisan test` apos cada sub-fase - tudo verde

---

## Fase 4: Config e Convencoes

### Acoes

- [ ] Completar `config/features.php` com todas as 11 features do CLAUDE.md
- [ ] Atualizar `composer.json`: `"php": "^8.4"`, name `"dockabase/dockabase"`, description
- [ ] Corrigir defaults em `config/database.php` (`dockabase` em vez de `laravel`)
- [ ] Adicionar `declare(strict_types=1)` em todos os arquivos PHP que nao tem
- [ ] Mover inline imports (`\App\`, `\Spatie\`, `Rules\Password`) para topo dos arquivos
- [ ] Rodar `php artisan test` - tudo verde

---

## Fase 5: Heartbeat Redis (Auto-Away)

### Design

Substituir o polling de `SetAutoAwayStatus` por heartbeat via Redis TTL:

1. **Middleware `TrackUserPresence`** (ja existe): a cada request, faz `SETEX` no Redis com chave `user:{id}:heartbeat` e TTL configuravel (ex: 5 min)
2. **Command `CheckUserHeartbeats`**: scheduler roda a cada minuto, verifica quais users NAO tem chave heartbeat viva, marca como OFFLINE
3. **Frontend**: composable `useUserStatus` envia heartbeat periodico (a cada 2 min) via POST ou websocket

### Acoes

- [ ] Atualizar `TrackUserPresence` para SETEX heartbeat
- [ ] Criar `CheckUserHeartbeats` command (substitui `SetAutoAwayStatus`)
- [ ] Registrar no scheduler (`routes/console.php`)
- [ ] Atualizar `useUserStatus` composable para enviar heartbeat periodico
- [ ] Escrever testes para o novo behavior
- [ ] Rodar `php artisan test` - tudo verde

---

## Fase 6: Frontend - Unificacao Completa

### 6a. Mover Sidebar

- [ ] Mover `Components/ui/sidebar/*` -> `components/ui/sidebar/`
- [ ] Atualizar imports em `AuthenticatedLayout.vue`

### 6b. Migrar Breeze -> shadcn-vue

| Componente Breeze | Replacer shadcn |
|-------------------|----------------|
| `PrimaryButton` | `Button` (variant: default) |
| `DangerButton` | `Button` (variant: destructive) |
| `SecondaryButton` | `Button` (variant: secondary) |
| `TextInput` | `Input` |
| `InputLabel` | `FormLabel` |
| `InputError` | `FormMessage` |
| `Checkbox` | `Checkbox` (shadcn) |
| `Modal` | `Dialog` |
| `Dropdown` | `DropdownMenu` |
| `DropdownLink` | `DropdownMenuItem` |

### 6c. Componentes Unicos

- [ ] `ApplicationLogo` -> `components/app/ApplicationLogo.vue`
- [ ] `NavLink` -> `components/app/NavLink.vue`
- [ ] `ResponsiveNavLink` -> `components/app/ResponsiveNavLink.vue`

### 6d. Finalizacao

- [ ] Deletar pasta `Components/` inteira
- [ ] Adicionar `lang="ts"` em todos os componentes sem TypeScript
- [ ] Atualizar TODOS os imports em Pages e Layouts
- [ ] Verificar `vite.config.ts` aliases
- [ ] Rodar `npm run build` sem erros
- [ ] Rodar `php artisan test` - tudo verde

---

## Fase 7: Validacao Final

### Acoes

- [ ] `php artisan test` - suite completa verde
- [ ] `npm run build` - sem erros
- [ ] `php artisan test tests/Feature/Lang/TranslationKeysTest.php` - chaves sincronizadas
- [ ] Revisao visual das Pages no browser (login, dashboard, profile, system)

---

## Notas para o Agente Executor

- **Commits atomicos:** Um commit por fase ou sub-fase logica
- **Nunca quebrar testes:** `php artisan test` verde entre cada fase
- **Seguir TDD:** Correcoes de tests primeiro, implementacao depois
- **Atualizar imports:** Ao mover arquivos, atualizar TODOS os imports referenciados
- **Nao adicionar features novas:** Este e refactor puro. Nenhuma funcionalidade nova.
- **Consultar REFACTOR.md:** Documento original em `/REFACTOR.md` para detalhes completos de cada area
