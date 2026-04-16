# DockaBase - Refactoring P1/P2 Design

> **Branch:** `refac2`
> **Data:** 2026-04-13
> **Pre-requisito:** PR #7 (refac1) merged

---

## Regra Cross-Cutting

**`php artisan test` apos CADA fase.** Se quebrar, corrige antes de avancar.

---

## Fase 1: useDarkMode.js -> TypeScript

### Acoes
- [ ] Renomear `resources/js/composables/useDarkMode.js` para `useDarkMode.ts`
- [ ] Adicionar tipos explicitos (sem `any`)
- [ ] Verificar imports em componentes que usam `useDarkMode`
- [ ] `npm run build` sem erros

### Contexto
Arquivo atual:
```js
import { ref, watch } from 'vue';
const isDark = ref(false);
// ... logica de dark mode simples
export function useDarkMode() {
    const toggleDark = () => { isDark.value = !isDark.value; };
    return { isDark, toggleDark };
}
```
So precisa adicionar tipos TypeScript basicos. Nenhuma mudanca de logica.

---

## Fase 2: Eliminar `: any` do TypeScript

### Acoes
- [ ] Criar tipos em `resources/js/types/role.ts` e `resources/js/types/permission.ts`
- [ ] Substituir 6 usos de `: any` em `resources/js/Pages/System/Users/Show.vue` (linhas 205, 216, 217, 227, 244, 248)
- [ ] Buscar outros `: any` no projeto: `grep -rn ": any" resources/js/ --include="*.vue" --include="*.ts"`
- [ ] `npm run build` sem erros

### Contexto
Tipos necessarios:
```ts
// types/role.ts
interface Role {
    id: string;
    name: string;
    permissions?: Permission[];
}

// types/permission.ts
interface Permission {
    id: string;
    name: string;
    guard_name: string;
}
```

---

## Fase 3: Events e Notifications faltantes

### 3a. Event: UserAddedToCredential
- [ ] Criar `app/Events/UserAddedToCredential.php`
  - Broadcast em canal privado do usuario: `private-users.{userId}`
  - Payload: `{ credential_id, credential_name, permission, added_by }`
- [ ] Disparar no `CredentialController::attachUser()` e `CredentialService::attachUser()`
- [ ] Registrar no `EventServiceProvider` (sem listener por enquanto)

### 3b. Event: UserRemovedFromCredential
- [ ] Criar `app/Events/UserRemovedFromCredential.php`
  - Mesma estrutura do acima
- [ ] Disparar no `CredentialController::detachUser()`

### 3c. Notification: UserAddedToCredentialNotification
- [ ] Criar `app/Notifications/UserAddedToCredentialNotification.php`
  - Via database + broadcast
  - Titulo com __(), descricao com nome da credential

### 3d. Renomear evento inconsistente
- [ ] Renomear `UserStatusUpdatedEvent` -> `UserStatusUpdated` (padronizar com os outros que nao tem suffix "Event")
- [ ] Atualizar TODOS os imports e referencias (EventServiceProvider, controllers, listeners, tests, frontend)

### Testes
- [ ] Testes unitarios para cada novo Event
- [ ] Teste unitario para Notification
- [ ] Atualizar testes existentes apos renomear UserStatusUpdatedEvent
- [ ] `php artisan test` verde

---

## Fase 4: Migrations - Indexes e FKs faltantes

### Acoes
- [ ] Criar migration `add_indexes_to_features_tables`
  - `feature_settings.feature_name` -> index (busca frequente)
  - `feature_histories.feature_setting_id` -> index + FK
  - `feature_histories.actor_id` -> index
- [ ] Criar migration `add_indexes_to_notifications_table`
  - `notifications.notifiable_type, notifiable_id` -> composite index
  - `notifications.read_at` -> index (para buscar nao lidas)
- [ ] Verificar se ha outras tabelas sem indexes necessarios
- [ ] `php artisan migrate --env=testing` ok
- [ ] `php artisan test` verde

### Nota
Ja verificado que:
- `users.email` ja tem unique (que cria index automatico)
- `user_activities.user_id` ja tem FK + index (migration 2026_04_08)
- Nao adicionar FKs em `features`/`feature_settings` — as relacoes sao resolvidas por nome (string), nao por ID

---

## Fase 5: Validacao Final

### Acoes
- [ ] `php artisan test` verde (local + Docker)
- [ ] `npm run build` sem erros
- [ ] `php artisan test tests/Feature/Lang/TranslationKeysTest.php` chaves sincronizadas
- [ ] Revisao visual no browser

---

## Notas para o Agente Executor

- **Branch:** `refac2` (criar a partir de `main` apos merge do PR #7)
- **Commits atomicos:** Um commit por fase
- **Nenhuma feature nova:** Refactor puro
- **Nao adicionar Pinia stores** — isso sera feito quando houver necessidade real de estado global, criar stores vazias e over-engineering
- **Consultar REFACTOR.md** para detalhes completos de cada area
- **Seguir CLAUDE.md** para convenções de código
