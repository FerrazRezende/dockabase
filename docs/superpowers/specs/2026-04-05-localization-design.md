# Localization System - Design Spec

**Date:** 2026-04-05
**Status:** Draft
**Feature:** Multi-language support for DockaBase

## Overview

Implement a comprehensive localization system supporting 3 languages (PT-BR, EN, ES) with translation via `__()` helper on both backend and frontend. Language preference is persisted in database for authenticated users and in cookie/localStorage for visitors.

## Supported Languages

| Code | Language | Flag |
|------|----------|------|
| `pt` | Portuguese (Brazil) | 🇧🇷 |
| `en` | English | 🇺🇸 |
| `es` | Spanish | 🇪🇸 |

## Architecture

### Backend

#### Translation Files

**Location:** `/lang/` directory

**Structure:**
```
/lang
├── pt.json  ← Portuguese (BR)
├── en.json  ← English
└── es.json  ← Español
```

**Format:** JSON with original string as key (Laravel default)

```json
{
  "User updated successfully": "Usuário atualizado com sucesso",
  "Database created": "Banco de dados criado",
  "There is one apple|There are many apples": "Há uma maçã|Há muitas maçãs"
}
```

**Usage:**
```php
__('User updated successfully')
__('messages.welcome', ['name' => 'John'])
trans_choice('messages.apples', 10)
```

#### Database Changes

**Migration:** `add_locale_to_users_table.php`

```php
$table->string('locale', 5)->default('pt')->index();
```

**Model User - Updates:**

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'is_admin',
    'password_changed_at',
    'active',
    'denied_permissions',
    'locale', // Add this
];

protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'password_changed_at' => 'datetime',
        'active' => 'boolean',
        'denied_permissions' => 'array',
        'locale' => 'string', // Add this
    ];
}
```

#### Middleware - SetLocale

**File:** `app/Http/Middleware/SetLocaleMiddleware.php`

**Priority:** High (executes early in middleware stack)

**Logic:**
1. Check if user is authenticated
   - If yes: use `user->locale`
   - If no: check `locale` cookie
2. Fallback to `APP_LOCALE` config
3. Execute `App::setLocale($locale)`

**Registration:** Add to `bootstrap/app.php` in web middleware group.

#### Configuration

**Update:** `config/app.php`

```php
'locale' => env('APP_LOCALE', 'pt'),
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
'faker_locale' => env('APP_FAKER_LOCALE', 'pt_BR'),
```

**Environment variables:**
```env
APP_LOCALE=pt
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR
```

#### Controllers - Response Translation

All controllers must translate messages before returning:

```php
// Success response
return redirect()->back()->with('toast', [
    'type' => 'success',
    'message' => __('User updated successfully')
]);

// Error response
throw ValidationException::withMessages([
    'email' => __('The email must be a valid email address.')
]);

// JSON response
return response()->json([
    'message' => __('Resource created successfully')
]);
```

### Frontend

#### Composable - useLang

**File:** `resources/js/composables/useLang.ts`

```typescript
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

export function useLang() {
  const page = usePage()

  const locale = computed(() => page.props.locale as string || 'pt')
  const availableLocales = [
    { code: 'pt', label: 'Português', flag: '🇧🇷' },
    { code: 'en', label: 'English', flag: '🇺🇸' },
    { code: 'es', label: 'Español', flag: '🇪🇸' },
  ]

  const setLocale = (newLocale: string) => {
    router.patch(route('profile.locale.update'), { locale })
  }

  const currentLocale = computed(() =>
    availableLocales.find(l => l.code === locale.value)
  )

  return {
    locale,
    availableLocales,
    currentLocale,
    setLocale,
  }
}
```

#### Translation Helper - __()

**File:** `resources/js/utils/lang.ts`

```typescript
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

type TranslationParams = Record<string, string | number>

const translations = computed(() => {
  const page = usePage()
  return (page.props.translations || {}) as Record<string, string>
})

export function __(key: string, params?: TranslationParams): string {
  const translated = translations.value[key]

  if (!translated) {
    console.warn(`[Translation] Missing key: ${key}`)
    return key
  }

  // Replace parameters :name → {name}
  if (params) {
    return translated.replace(/:(\w+)/g, (_, paramKey) => {
      return params[paramKey]?.toString() || `:${paramKey}`
    })
  }

  return translated
}

export function transChoice(key: string, count: number, params?: TranslationParams): string {
  const fullKey = `${key}`

  if (!translations.value[fullKey]) {
    console.warn(`[Translation] Missing key: ${fullKey}`)
    return key
  }

  const parts = translations.value[fullKey].split('|')

  // Simple pluralization logic
  if (count === 1) {
    return parts[0]
  }
  return parts[1] || parts[0]
}
```

#### Global Registration

**Update:** `resources/js/app.ts`

```typescript
import {__, transChoice} from './utils/lang'

// Make globally available
window.__ = __
```

#### Page Props - HandleInertiaRequests

**Update:** `app/Http/Middleware/HandleInertiaRequests.php`

```php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        // ... existing
        'locale' => App::currentLocale(),
        'translations' => $this->getTranslations(),
    ]);
}

protected function getTranslations(): array
{
    $langFile = lang_path(App::currentLocale() . '.json');

    if (!file_exists($langFile)) {
        return [];
    }

    return json_decode(file_get_contents($langFile), true);
}
```

### Profile Settings - Locale Selection

**Location:** `resources/js/Pages/Profile/Edit.vue`

**Add locale selector:**

```vue
<select v-model="form.locale" @change="updateLocale">
  <option v-for="lang in availableLocales" :key="lang.code" :value="lang.code">
    {{ lang.flag }} {{ lang.label }}
  </option>
</select>
```

**Controller:** `app/Http/Controllers/Profile/LocaleController.php`

```php
public function update(Request $request)
{
    $validated = $request->validate([
        'locale' => 'required|in:pt,en,es',
    ]);

    $request->user()->update($validated);

    App::setLocale($validated['locale']);

    return redirect()->back()->with('toast', [
        'type' => 'success',
        'message' => __('Language updated successfully')
    ]);
}
```

### Landing Page - Language Selector

**Location:** `resources/js/Layouts/GuestLayout.vue`

**Component:**

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'

const selectedLocale = ref('pt')

onMounted(() => {
  const saved = localStorage.getItem('dockabase_locale')
  if (saved) {
    selectedLocale.value = saved
  }
})

function changeLocale() {
  localStorage.setItem('dockabase_locale', selectedLocale.value)
  window.location.reload() // Simple reload to apply
}

const locales = [
  { code: 'pt', label: 'Português', flag: '🇧🇷' },
  { code: 'en', label: 'English', flag: '🇺🇸' },
  { code: 'es', label: 'Español', flag: '🇪🇸' },
]
</script>

<template>
  <select v-model="selectedLocale" @change="changeLocale">
    <option v-for="lang in locales" :key="lang.code" :value="lang.code">
      {{ lang.flag }} {{ lang.label }}
    </option>
  </select>
</template>
```

**Middleware Integration:**

Update `SetLocaleMiddleware` to check localStorage via cookie:

```php
// For guest users
if (!$request->user()) {
    $locale = $request->cookie('locale', config('app.locale'));
}
```

JavaScript sets cookie:

```typescript
function changeLocale() {
  localStorage.setItem('dockabase_locale', selectedLocale.value)
  document.cookie = `locale=${selectedLocale.value}; path=/; max-age=31536000`
  window.location.reload()
}
```

## Initial Translation Keys

### Auth Context

| Key | PT | EN | ES |
|-----|----|----|----|
| "Email" | "E-mail" | "Email" | "Correo electrónico" |
| "Password" | "Senha" | "Password" | "Contraseña" |
| "Remember me" | "Lembrar-me" | "Remember me" | "Recordarme" |
| "Forgot your password?" | "Esqueceu sua senha?" | "Forgot your password?" | "¿Olvidaste tu contraseña?" |
| "Login" | "Entrar" | "Login" | "Iniciar sesión" |
| "Logout" | "Sair" | "Logout" | "Cerrar sesión" |

### Common Messages

| Key | PT | EN | ES |
|-----|----|----|----|
| "Success" | "Sucesso" | "Success" | "Éxito" |
| "Error" | "Erro" | "Error" | "Error" |
| "Warning" | "Aviso" | "Warning" | "Advertencia" |
| "Save" | "Salvar" | "Save" | "Guardar" |
| "Cancel" | "Cancelar" | "Cancel" | "Cancelar" |
| "Confirm" | "Confirmar" | "Confirm" | "Confirmar" |
| "Delete" | "Excluir" | "Delete" | "Eliminar" |
| "Edit" | "Editar" | "Edit" | "Editar" |
| "Create" | "Criar" | "Create" | "Crear" |
| "Update" | "Atualizar" | "Update" | "Actualizar" |
| "Loading..." | "Carregando..." | "Loading..." | "Cargando..." |

### Users

| Key | PT | EN | ES |
|-----|----|----|----|
| "Users" | "Usuários" | "Users" | "Usuarios" |
| "Create user" | "Criar usuário" | "Create user" | "Crear usuario" |
| "User created successfully" | "Usuário criado com sucesso" | "User created successfully" | "Usuario creado con éxito" |
| "User updated successfully" | "Usuário atualizado com sucesso" | "User updated successfully" | "Usuario actualizado con éxito" |
| "User deleted successfully" | "Usuário excluído com sucesso" | "User deleted successfully" | "Usuario eliminado con éxito" |

### Databases

| Key | PT | EN | ES |
|-----|----|----|----|
| "Databases" | "Bancos de dados" | "Databases" | "Bases de datos" |
| "Create database" | "Criar banco de dados" | "Create database" | "Crear base de datos" |
| "Database created successfully" | "Banco de dados criado com sucesso" | "Database created successfully" | "Base de datos creada con éxito" |
| "Database is being created" | "Banco de dados está sendo criado" | "Database is being created" | "La base de datos está siendo creada" |

### Features

| Key | PT | EN | ES |
|-----|----|----|----|
| "Features" | "Funcionalidades" | "Features" | "Características" |
| "Enable feature" | "Ativar funcionalidade" | "Enable feature" | "Activar característica" |
| "Disable feature" | "Desativar funcionalidade" | "Disable feature" | "Desactivar característica" |
| "Feature enabled successfully" | "Funcionalidade ativada com sucesso" | "Feature enabled successfully" | "Característica activada con éxito" |
| "Feature disabled successfully" | "Funcionalidade desativada com sucesso" | "Feature disabled successfully" | "Característica desactivada con éxito" |

### Profile

| Key | PT | EN | ES |
|-----|----|----|----|
| "Profile" | "Perfil" | "Profile" | "Perfil" |
| "Profile Information" | "Informações do Perfil" | "Profile Information" | "Información del Perfil" |
| "Update Profile" | "Atualizar Perfil" | "Update Profile" | "Actualizar Perfil" |
| "Language" | "Idioma" | "Language" | "Idioma" |
| "Language updated successfully" | "Idioma atualizado com sucesso" | "Language updated successfully" | "Idioma actualizado con éxito" |

### Validation

| Key | PT | EN | ES |
|-----|----|----|----|
| "The :attribute field is required." | "O campo :attribute é obrigatório." | "The :attribute field is required." | "El campo :attribute es obligatorio." |
| "The :attribute must be a valid email address." | "O campo :attribute deve ser um e-mail válido." | "The :attribute must be a valid email address." | "El campo :attribute debe ser una dirección de correo válida." |
| "The :attribute must be at least :min characters." | "O campo :attribute deve ter pelo menos :min caracteres." | "The :attribute must be at least :min characters." | "El campo :attribute debe tener al menos :min caracteres." |

## Testing Strategy

### Unit Tests

**Test Suite:** `tests/Unit/Lang/LangHelperTest.php`

```php
it('translates a key correctly', function () {
    App::setLocale('pt');
    expect(__('User updated successfully'))->toBe('Usuário atualizado com sucesso');
});

it('returns key when translation missing', function () {
    App::setLocale('pt');
    expect(__('non.existent.key'))->toBe('non.existent.key');
});

it('replaces parameters in translation', function () {
    App::setLocale('pt');
    expect(__('Welcome, :name', ['name' => 'John']))->toBe('Bem-vindo, John');
});

it('handles pluralization correctly', function () {
    App::setLocale('pt');
    expect(trans_choice('messages.apples', 1))->toContain('maçã');
    expect(trans_choice('messages.apples', 10))->toContain('maçãs');
});
```

### Feature Tests

**Test Suite:** `tests/Feature/Profile/LocaleUpdateTest.php`

```php
it('updates user locale', function () {
    $user = User::factory()->create(['locale' => 'pt']);

    $this->actingAs($user)
        ->patch('/profile/locale', ['locale' => 'en'])
        ->assertRedirect();

    expect($user->fresh()->locale)->toBe('en');
});

it('validates locale value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile/locale', ['locale' => 'fr'])
        ->assertSessionHasErrors();
});
```

**Test Suite:** `tests/Feature/Middleware/SetLocaleMiddlewareTest.php`

```php
it('sets locale from authenticated user', function () {
    $user = User::factory()->create(['locale' => 'es']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSessionHasNoErrors();

    expect(App::currentLocale())->toBe('es');
});

it('sets locale from cookie for guests', function () {
    $this->withCookie('locale', 'en')
        ->get('/')
        ->assertSuccessful();

    expect(App::currentLocale())->toBe('en');
});
```

**Test Suite:** `tests/Feature/Lang/TranslationKeysTest.php`

```php
it('has all translation keys in all languages', function () {
    $languages = ['pt', 'en', 'es'];
    $translations = [];

    foreach ($languages as $lang) {
        $translations[$lang] = json_decode(
            file_get_contents(lang_path("{$lang}.json")),
            true
        );
    }

    $allKeys = array_unique(array_merge(...array_map('array_keys', $translations)));

    foreach ($languages as $lang) {
        foreach ($allKeys as $key) {
            expect(isset($translations[$lang][$key]))->toBeTrue(
                "Missing key '{$key}' in {$lang}.json"
            );
        }
    }
});
```

### Test Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| `SetLocaleMiddleware` | 100% |
| `LocaleController` | 100% |
| `LangHelper` functions | 100% |
| Pluralization logic | 100% |
| Parameter replacement | 100% |
| Integration tests | 90%+ |

## Routes

```php
// Profile locale update
Route::patch('/profile/locale', [LocaleController::class, 'update'])
    ->name('profile.locale.update')
    ->middleware(['auth', 'verified']);
```

## Environment Variables

```env
# Default application locale
APP_LOCALE=pt

# Fallback locale when translation is missing
APP_FALLBACK_LOCALE=en

# Faker locale for factories
APP_FAKER_LOCALE=pt_BR
```

## Migration Plan

1. **Phase 1:** Backend foundation
   - Create migration for `users.locale`
   - Create `/lang/*.json` files with initial translations
   - Implement `SetLocaleMiddleware`
   - Update `HandleInertiaRequests` to share translations

2. **Phase 2:** Frontend integration
   - Implement `useLang` composable
   - Implement `__()` helper
   - Update `Profile/Edit.vue` with locale selector

3. **Phase 3:** Landing page
   - Add language selector to `GuestLayout.vue`
   - Implement cookie + localStorage logic

4. **Phase 4:** Testing & validation
   - Write unit tests
   - Write feature tests
   - Validate all translations exist across all languages

5. **Phase 5:** Rollout
   - Deploy to production
   - Monitor for missing translation keys
   - Add translations incrementally as new features are added

## Dependencies

None - uses Laravel's built-in localization features.

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Missing translations in some languages | Test suite validates all keys exist in all languages; fallback to default locale |
| Inconsistent translations | Establish translation guidelines; use original string as key |
| Performance overhead of loading all translations | Cache translations; only load current locale |
| Client-side translations becoming stale | Server sends translations on every request via Inertia props |

## Success Criteria

- [ ] All 3 languages (PT, EN, ES) fully supported
- [ ] User preference persisted in database
- [ ] Guest preference stored in cookie/localStorage
- [ ] Backend uses `__()` for all user-facing strings
- [ ] Frontend uses `__()` for all user-facing strings
- [ ] Toast messages are translated
- [ ] Test coverage >90% for translation logic
- [ ] All translation keys validated to exist in all languages
