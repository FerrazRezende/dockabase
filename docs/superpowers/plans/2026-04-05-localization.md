# Localization System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement multi-language support (PT, EN, ES) for DockaBase with JSON translation files, `__()` helper on backend and frontend, and user locale persistence.

**Architecture:** JSON files in `/lang/` directory, `SetLocaleMiddleware` to set locale from authenticated user or cookie, `User.locale` column for persistence, frontend `useLang` composable and `__()` helper.

**Tech Stack:** Laravel 13 localization features, Inertia.js, Vue 3 Composition API, TypeScript

---

## File Structure

### New Files

| Path | Responsibility |
|------|---------------|
| `lang/pt.json` | Portuguese (BR) translations |
| `lang/en.json` | English translations |
| `lang/es.json` | Spanish translations |
| `app/Http/Middleware/SetLocaleMiddleware.php` | Set locale from user or cookie |
| `app/Http/Controllers/Profile/LocaleController.php` | Handle locale updates from profile |
| `app/Http/Requests/Profile/UpdateLocaleRequest.php` | Validate locale update request |
| `resources/js/composables/useLang.ts` | Vue composable for locale management |
| `resources/js/utils/lang.ts` | Frontend `__()` and `transChoice()` helpers |
| `tests/Unit/Lang/LangHelperTest.php` | Unit tests for translation functions |
| `tests/Feature/Middleware/SetLocaleMiddlewareTest.php` | Feature tests for middleware |
| `tests/Feature/Profile/LocaleUpdateTest.php` | Feature tests for locale update |
| `tests/Feature/Lang/TranslationKeysTest.php` | Test all keys exist in all languages |

### Modified Files

| Path | Changes |
|------|---------|
| `database/migrations/0001_01_01_000000_create_users_table.php` | Add `locale` column (or create new migration) |
| `app/Models/User.php` | Add `locale` to fillable and casts |
| `app/Http/Middleware/HandleInertiaRequests.php` | Share locale and translations to frontend |
| `bootstrap/app.php` | Register SetLocaleMiddleware |
| `routes/web.php` | Add profile locale update route |
| `config/app.php` | Update locale configuration defaults |
| `resources/js/app.ts` | Register `__()` helper globally |
| `resources/js/utils/lang.ts` | Create (or update) translation helpers |
| `resources/js/Pages/Profile/Edit.vue` | Add locale selector |

---

## Task 1: Create JSON Translation Files

**Files:**
- Create: `lang/pt.json`
- Create: `lang/en.json`
- Create: `lang/es.json`

- [ ] **Step 1: Create Portuguese translation file**

```bash
cat > lang/pt.json << 'EOF'
{
  "Email": "E-mail",
  "Password": "Senha",
  "Remember me": "Lembrar-me",
  "Forgot your password?": "Esqueceu sua senha?",
  "Login": "Entrar",
  "Logout": "Sair",
  "Success": "Sucesso",
  "Error": "Erro",
  "Warning": "Aviso",
  "Save": "Salvar",
  "Cancel": "Cancelar",
  "Confirm": "Confirmar",
  "Delete": "Excluir",
  "Edit": "Editar",
  "Create": "Criar",
  "Update": "Atualizar",
  "Loading...": "Carregando...",
  "Users": "Usuários",
  "Create user": "Criar usuário",
  "User created successfully": "Usuário criado com sucesso",
  "User updated successfully": "Usuário atualizado com sucesso",
  "User deleted successfully": "Usuário excluído com sucesso",
  "Databases": "Bancos de dados",
  "Create database": "Criar banco de dados",
  "Database created successfully": "Banco de dados criado com sucesso",
  "Database is being created": "Banco de dados está sendo criado",
  "Features": "Funcionalidades",
  "Enable feature": "Ativar funcionalidade",
  "Disable feature": "Desativar funcionalidade",
  "Feature enabled successfully": "Funcionalidade ativada com sucesso",
  "Feature disabled successfully": "Funcionalidade desativada com sucesso",
  "Profile": "Perfil",
  "Profile Information": "Informações do Perfil",
  "Update Profile": "Atualizar Perfil",
  "Language": "Idioma",
  "Language updated successfully": "Idioma atualizado com sucesso",
  "The :attribute field is required.": "O campo :attribute é obrigatório.",
  "The :attribute must be a valid email address.": "O campo :attribute deve ser um e-mail válido.",
  "The :attribute must be at least :min characters.": "O campo :attribute deve ter pelo menos :min caracteres."
}
EOF
```

- [ ] **Step 2: Create English translation file**

```bash
cat > lang/en.json << 'EOF'
{
  "Email": "Email",
  "Password": "Password",
  "Remember me": "Remember me",
  "Forgot your password?": "Forgot your password?",
  "Login": "Login",
  "Logout": "Logout",
  "Success": "Success",
  "Error": "Error",
  "Warning": "Warning",
  "Save": "Save",
  "Cancel": "Cancel",
  "Confirm": "Confirm",
  "Delete": "Delete",
  "Edit": "Edit",
  "Create": "Create",
  "Update": "Update",
  "Loading...": "Loading...",
  "Users": "Users",
  "Create user": "Create user",
  "User created successfully": "User created successfully",
  "User updated successfully": "User updated successfully",
  "User deleted successfully": "User deleted successfully",
  "Databases": "Databases",
  "Create database": "Create database",
  "Database created successfully": "Database created successfully",
  "Database is being created": "Database is being created",
  "Features": "Features",
  "Enable feature": "Enable feature",
  "Disable feature": "Disable feature",
  "Feature enabled successfully": "Feature enabled successfully",
  "Feature disabled successfully": "Feature disabled successfully",
  "Profile": "Profile",
  "Profile Information": "Profile Information",
  "Update Profile": "Update Profile",
  "Language": "Language",
  "Language updated successfully": "Language updated successfully",
  "The :attribute field is required.": "The :attribute field is required.",
  "The :attribute must be a valid email address.": "The :attribute must be a valid email address.",
  "The :attribute must be at least :min characters.": "The :attribute must be at least :min characters."
}
EOF
```

- [ ] **Step 3: Create Spanish translation file**

```bash
cat > lang/es.json << 'EOF'
{
  "Email": "Correo electrónico",
  "Password": "Contraseña",
  "Remember me": "Recordarme",
  "Forgot your password?": "¿Olvidaste tu contraseña?",
  "Login": "Iniciar sesión",
  "Logout": "Cerrar sesión",
  "Success": "Éxito",
  "Error": "Error",
  "Warning": "Advertencia",
  "Save": "Guardar",
  "Cancel": "Cancelar",
  "Confirm": "Confirmar",
  "Delete": "Eliminar",
  "Edit": "Editar",
  "Create": "Crear",
  "Update": "Actualizar",
  "Loading...": "Cargando...",
  "Users": "Usuarios",
  "Create user": "Crear usuario",
  "User created successfully": "Usuario creado con éxito",
  "User updated successfully": "Usuario actualizado con éxito",
  "User deleted successfully": "Usuario eliminado con éxito",
  "Databases": "Bases de datos",
  "Create database": "Crear base de datos",
  "Database created successfully": "Base de datos creada con éxito",
  "Database is being created": "La base de datos está siendo creada",
  "Features": "Características",
  "Enable feature": "Activar característica",
  "Disable feature": "Desactivar característica",
  "Feature enabled successfully": "Característica activada con éxito",
  "Feature disabled successfully": "Característica desactivada con éxito",
  "Profile": "Perfil",
  "Profile Information": "Información del Perfil",
  "Update Profile": "Actualizar Perfil",
  "Language": "Idioma",
  "Language updated successfully": "Idioma actualizado con éxito",
  "The :attribute field is required.": "El campo :attribute es obligatorio.",
  "The :attribute must be a valid email address.": "El campo :attribute debe ser una dirección de correo válida.",
  "The :attribute must be at least :min characters.": "El campo :attribute debe tener al menos :min caracteres."
}
EOF
```

- [ ] **Step 4: Verify files were created**

Run: `ls -la lang/`

Expected output showing: `en.json`, `es.json`, `pt.json`

- [ ] **Step 5: Commit**

```bash
git add lang/
git commit -m "feat: add JSON translation files for PT, EN, ES"
```

---

## Task 2: Add Locale Column to Users Table

**Files:**
- Create: `database/migrations/2026_04_05_000001_add_locale_to_users_table.php`

- [ ] **Step 1: Create migration file**

```bash
php artisan make:migration add_locale_to_users_table
```

- [ ] **Step 2: Edit the migration file**

Open the newly created migration file (most recent in `database/migrations/`) and replace the content with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->default('pt')->index()->after('denied_permissions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['locale']);
            $table->dropColumn('locale');
        });
    }
};
```

- [ ] **Step 3: Run migration**

Run: `php artisan migrate`

Expected output: `Migration succeeded` or similar

- [ ] **Step 4: Verify column exists**

Run: `php artisan db:table users`

Expected output: Shows `locale` column with default value `pt`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat: add locale column to users table"
```

---

## Task 3: Update User Model

**Files:**
- Modify: `app/Models/User.php`

- [ ] **Step 1: Add locale to fillable array**

Find the `$fillable` property and add `'locale'` to the array:

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'is_admin',
    'password_changed_at',
    'active',
    'denied_permissions',
    'locale', // Add this line
];
```

- [ ] **Step 2: Add locale to casts array**

Find the `casts()` method and add `'locale' => 'string'` to the return array:

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'password_changed_at' => 'datetime',
        'active' => 'boolean',
        'denied_permissions' => 'array',
        'locale' => 'string', // Add this line
    ];
}
```

- [ ] **Step 3: Verify syntax with PHP linter**

Run: `php -l app/Models/User.php`

Expected output: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Models/User.php
git commit -m "feat: add locale to User model fillable and casts"
```

---

## Task 4: Create SetLocaleMiddleware

**Files:**
- Create: `app/Http/Middleware/SetLocaleMiddleware.php`

- [ ] **Step 1: Create middleware**

```bash
php artisan make:middleware SetLocaleMiddleware
```

- [ ] **Step 2: Write the middleware implementation**

Open the newly created file and replace with:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // For authenticated users, use their preference from database
        if ($request->user()?->locale) {
            $locale = $request->user()->locale;
        }
        // For guests, check cookie
        elseif ($request->hasCookie('locale')) {
            $cookieLocale = $request->cookie('locale');
            if (in_array($cookieLocale, ['pt', 'en', 'es'], true)) {
                $locale = $cookieLocale;
            }
        }

        // Fallback to app config
        if ($locale === null) {
            $locale = config('app.locale', 'pt');
        }

        // Validate locale is supported
        if (!in_array($locale, ['pt', 'en', 'es'], true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
```

- [ ] **Step 3: Verify syntax**

Run: `php -l app/Http/Middleware/SetLocaleMiddleware.php`

Expected output: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Http/Middleware/SetLocaleMiddleware.php
git commit -m "feat: create SetLocaleMiddleware"
```

---

## Task 5: Register SetLocaleMiddleware

**Files:**
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Find the middleware section**

Look for the section that defines web middleware group in `bootstrap/app.php`

- [ ] **Step 2: Add SetLocaleMiddleware to web group**

Add the middleware to the web middleware group:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
        \App\Http\Middleware\SetLocaleMiddleware::class, // Add this line
    ]);
})
```

Note: Adjust the exact placement based on your existing middleware configuration. The key is to add `SetLocaleMiddleware` early in the stack.

- [ ] **Step 3: Verify syntax**

Run: `php -l bootstrap/app.php`

Expected output: `No syntax errors detected`

- [ ] **Step 4: Test application still works**

Run: `php artisan serve --port=8001` (in background) and visit `http://localhost:8001`

Expected output: Application loads without errors

- [ ] **Step 5: Commit**

```bash
git add bootstrap/app.php
git commit -m "feat: register SetLocaleMiddleware in web group"
```

---

## Task 6: Update HandleInertiaRequests to Share Translations

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

- [ ] **Step 1: Add getTranslations method**

Add this method to the HandleInertiaRequests class:

```php
/**
 * Get translations for the current locale.
 */
protected function getTranslations(): array
{
    $langFile = lang_path(App::currentLocale() . '.json');

    if (!file_exists($langFile)) {
        return [];
    }

    $translations = json_decode(file_get_contents($langFile), true);

    return $translations ?? [];
}
```

- [ ] **Step 2: Update share method to include locale and translations**

Find the `share` method and add locale and translations to the returned array:

```php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        // ... existing shared data ...
        'locale' => App::currentLocale(),
        'translations' => $this->getTranslations(),
    ]);
}
```

The exact placement depends on your existing share method structure. Add these two keys to the array returned.

- [ ] **Step 3: Verify syntax**

Run: `php -l app/Http/Middleware/HandleInertiaRequests.php`

Expected output: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: share locale and translations via Inertia"
```

---

## Task 7: Create UpdateLocaleRequest FormRequest

**Files:**
- Create: `app/Http/Requests/Profile/UpdateLocaleRequest.php`

- [ ] **Step 1: Create FormRequest**

```bash
mkdir -p app/Http/Requests/Profile
php artisan make:request Profile/UpdateLocaleRequest
```

- [ ] **Step 2: Implement validation rules**

Replace the content of the newly created file with:

```php
<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocaleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => 'required|string|in:pt,en,es',
        ];
    }
}
```

- [ ] **Step 3: Verify syntax**

Run: `php -l app/Http/Requests/Profile/UpdateLocaleRequest.php`

Expected output: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Http/Requests/Profile/UpdateLocaleRequest.php
git commit -m "feat: create UpdateLocaleRequest"
```

---

## Task 8: Create LocaleController

**Files:**
- Create: `app/Http/Controllers/Profile/LocaleController.php`

- [ ] **Step 1: Create controller directory and file**

```bash
mkdir -p app/Http/Controllers/Profile
```

Create the file `app/Http/Controllers/Profile/LocaleController.php` with:

```php
<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    /**
     * Update the user's locale preference.
     */
    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        // Set locale immediately for current request
        App::setLocale($request->validated('locale'));

        return redirect()
            ->back()
            ->with('toast', [
                'type' => 'success',
                'message' => __('Language updated successfully'),
            ]);
    }
}
```

- [ ] **Step 2: Verify syntax**

Run: `php -l app/Http/Controllers/Profile/LocaleController.php`

Expected output: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Profile/LocaleController.php
git commit -m "feat: create LocaleController"
```

---

## Task 9: Add Locale Update Route

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add the locale update route**

Add this route to your routes file (find the profile routes section):

```php
Route::patch('/profile/locale', [App\Http\Controllers\Profile\LocaleController::class, 'update'])
    ->name('profile.locale.update')
    ->middleware(['auth', 'verified']);
```

- [ ] **Step 2: Verify routes are cached correctly**

Run: `php artisan route:list | grep locale`

Expected output: Shows the `profile.locale.update` route

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: add profile locale update route"
```

---

## Task 10: Create Frontend useLang Composable

**Files:**
- Create: `resources/js/composables/useLang.ts`

- [ ] **Step 1: Create the composable**

Create `resources/js/composables/useLang.ts` with:

```typescript
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

export interface LocaleOption {
  code: string
  label: string
  flag: string
}

export function useLang() {
  const page = usePage()

  const locale = computed(() => (page.props.locale as string) || 'pt')

  const availableLocales: LocaleOption[] = [
    { code: 'pt', label: 'Português', flag: '🇧🇷' },
    { code: 'en', label: 'English', flag: '🇺🇸' },
    { code: 'es', label: 'Español', flag: '🇪🇸' },
  ]

  const setLocale = (newLocale: string) => {
    router.patch(route('profile.locale.update'), { locale })
  }

  const currentLocale = computed(() =>
    availableLocales.find((l) => l.code === locale.value)
  )

  return {
    locale,
    availableLocales,
    currentLocale,
    setLocale,
  }
}
```

- [ ] **Step 2: Verify TypeScript compiles**

Run: `npm run build` or check for TypeScript errors

Expected output: No TypeScript errors related to `useLang.ts`

- [ ] **Step 3: Commit**

```bash
git add resources/js/composables/useLang.ts
git commit -m "feat: add useLang composable"
```

---

## Task 11: Create Frontend Translation Helpers

**Files:**
- Create: `resources/js/utils/lang.ts`

- [ ] **Step 1: Create the translation helpers**

Create `resources/js/utils/lang.ts` with:

```typescript
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export type TranslationParams = Record<string, string | number>

const translations = computed(() => {
  const page = usePage()
  return (page.props.translations || {}) as Record<string, string>
})

/**
 * Translate a key using the current locale's translations.
 * @param key - The translation key (original string)
 * @param params - Optional parameters to replace in the translation
 * @returns The translated string or the key if not found
 */
export function __(key: string, params?: TranslationParams): string {
  const translated = translations.value[key]

  if (!translated) {
    console.warn(`[Translation] Missing key: ${key}`)
    return key
  }

  // Replace parameters :name → actual value
  if (params) {
    return translated.replace(/:(\w+)/g, (_match, paramKey) => {
      return params[paramKey]?.toString() || `:${paramKey}`
    })
  }

  return translated
}

/**
 * Translate a key with pluralization support.
 * @param key - The translation key
 * @param count - The count to determine pluralization
 * @param params - Optional parameters to replace
 * @returns The translated string for the count
 */
export function transChoice(key: string, count: number, params?: TranslationParams): string {
  const translated = translations.value[key]

  if (!translated) {
    console.warn(`[Translation] Missing key: ${key}`)
    return key
  }

  const parts = translated.split('|')

  // Simple pluralization: first part is singular, second is plural
  if (parts.length === 1) {
    return parts[0]
  }

  // Handle count-based pluralization: {0}|{1}|[2,*]
  const singular = parts[0]
  const plural = parts[1] || parts[0]

  if (count === 1) {
    return replaceParams(singular, params)
  }
  return replaceParams(plural, params)
}

function replaceParams(text: string, params?: TranslationParams): string {
  if (!params) return text

  return text.replace(/:(\w+)/g, (_match, paramKey) => {
    return params[paramKey]?.toString() || `:${paramKey}`
  })
}
```

- [ ] **Step 2: Verify TypeScript compiles**

Run: `npm run build` or check for TypeScript errors

Expected output: No TypeScript errors related to `lang.ts`

- [ ] **Step 3: Commit**

```bash
git add resources/js/utils/lang.ts
git commit -m "feat: add frontend translation helpers"
```

---

## Task 12: Register Translation Helpers Globally

**Files:**
- Modify: `resources/js/app.ts`

- [ ] **Step 1: Find where app is initialized**

Look for the main app initialization in `resources/js/app.ts`

- [ ] **Step 2: Import and register translation helpers**

Add this import at the top:

```typescript
import {__, transChoice} from './utils/lang'
```

Add this after the app is created (make helpers globally available):

```typescript
// Make translation helpers globally available for use in Vue components
declare global {
  var __: typeof __
  var transChoice: typeof transChoice
}

window.__ = __
window.transChoice = transChoice
```

- [ ] **Step 3: Verify TypeScript compiles**

Run: `npm run build` or check for TypeScript errors

Expected output: No TypeScript errors

- [ ] **Step 4: Commit**

```bash
git add resources/js/app.ts
git commit -m "feat: register translation helpers globally"
```

---

## Task 13: Add Locale Selector to Profile Page

**Files:**
- Modify: `resources/js/Pages/Profile/Edit.vue`

- [ ] **Step 1: Read the current Profile/Edit.vue file**

Run: `cat resources/js/Pages/Profile/Edit.vue` to understand current structure

- [ ] **Step 2: Add locale selector component**

Add the locale selector to the profile form. Insert this in the appropriate section (likely where other form fields are):

```vue
<script setup lang="ts">
import { useLang } from '@/composables/useLang'

// ... existing imports and code

const { locale, availableLocales, setLocale } = useLang()

const form = reactive({
  // ... existing form fields
  locale: locale.value,
})

// Watch for locale changes from useLang
watch(locale, (newLocale) => {
  form.locale = newLocale
})

function updateLocale() {
  setLocale(form.locale)
}
</script>

<template>
  <!-- Add this section in the form, likely after other profile fields -->
  <div class="space-y-6">
    <div>
      <label for="locale">{{ __('Language') }}</label>
      <select
        id="locale"
        v-model="form.locale"
        @change="updateLocale"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
      >
        <option
          v-for="lang in availableLocales"
          :key="lang.code"
          :value="lang.code"
        >
          {{ lang.flag }} {{ lang.label }}
        </option>
      </select>
    </div>
  </div>
</template>
```

Note: The exact placement depends on your current `Profile/Edit.vue` structure. Integrate this selector naturally into the existing form.

- [ ] **Step 3: Verify the build**

Run: `npm run build`

Expected output: Build succeeds without errors

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Profile/Edit.vue
git commit -m "feat: add locale selector to profile page"
```

---

## Task 14: Write Unit Tests for Translation Helpers

**Files:**
- Create: `tests/Unit/Lang/LangHelperTest.php`

- [ ] **Step 1: Create test directory and file**

```bash
mkdir -p tests/Unit/Lang
touch tests/Unit/Lang/LangHelperTest.php
```

- [ ] **Step 2: Write the failing test**

```php
<?php

use Illuminate\Support\Facades\App;

beforeEach(function () {
    App::setLocale('pt');
});

test('it translates a key correctly', function () {
    expect(__('User updated successfully'))->toBe('Usuário atualizado com sucesso');
});

test('it returns key when translation missing', function () {
    expect(__('non.existent.key'))->toBe('non.existent.key');
});

test('it replaces parameters in translation', function () {
    // This would need a translation with parameters
    // For now, test the basic __ function works
    expect(__('Login'))->toBe('Entrar');
});

test('it switches locale correctly', function () {
    App::setLocale('en');
    expect(__('Login'))->toBe('Login');

    App::setLocale('es');
    expect(__('Login'))->toBe('Iniciar sesión');
});
```

- [ ] **Step 3: Run test to verify it fails (or passes if translations exist)**

Run: `php artisan test tests/Unit/Lang/LangHelperTest.php`

Expected output: Tests should PASS if translation files exist

- [ ] **Step 4: If tests pass, commit**

```bash
git add tests/Unit/Lang/LangHelperTest.php
git commit -m "test: add LangHelper unit tests"
```

---

## Task 15: Write Feature Tests for SetLocaleMiddleware

**Files:**
- Create: `tests/Feature/Middleware/SetLocaleMiddlewareTest.php`

- [ ] **Step 1: Create test file**

```bash
mkdir -p tests/Feature/Middleware
touch tests/Feature/Middleware/SetLocaleMiddlewareTest.php
```

- [ ] **Step 2: Write the failing test**

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\App;
use function Pest\Laravel\{actingAs, get};

beforeEach(function () {
    App::setLocale('pt'); // Reset to default
});

test('it sets locale from authenticated user', function () {
    $user = User::factory()->create(['locale' => 'es']);

    actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200); // Or appropriate status

    expect(App::currentLocale())->toBe('es');
});

test('it sets locale from cookie for guests', function () {
    get('/')->withCookie('locale', 'en')->assertStatus(200);

    expect(App::currentLocale())->toBe('en');
});

test('it uses default locale when no preference set', function () {
    $user = User::factory()->create(['locale' => null]);

    actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200);

    expect(App::currentLocale())->toBe('pt');
});

test('it validates locale value', function () {
    // Cookie with invalid locale should fall back
    get('/')
        ->withCookie('locale', 'fr')
        ->assertStatus(200);

    expect(App::currentLocale())->toBeIn(['pt', 'en', 'es']);
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test tests/Feature/Middleware/SetLocaleMiddlewareTest.php`

Expected output: Some tests may fail initially

- [ ] **Step 4: Debug and fix any failing tests**

Expected output: All tests should pass after SetLocaleMiddleware is properly registered

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Middleware/SetLocaleMiddlewareTest.php
git commit -m "test: add SetLocaleMiddleware feature tests"
```

---

## Task 16: Write Feature Tests for Locale Update

**Files:**
- Create: `tests/Feature/Profile/LocaleUpdateTest.php`

- [ ] **Step 1: Create test file**

```bash
mkdir -p tests/Feature/Profile
touch tests/Feature/Profile/LocaleUpdateTest.php
```

- [ ] **Step 2: Write the failing test**

```php
<?php

use App\Models\User;
use function Pest\Laravel\{actingAs, patch};

test('it updates user locale', function () {
    $user = User::factory()->create(['locale' => 'pt']);

    actingAs($user)
        ->patch('/profile/locale', ['locale' => 'en'])
        ->assertRedirect();

    expect($user->fresh()->locale)->toBe('en');
});

test('it validates locale value', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->patch('/profile/locale', ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

test('it requires authentication', function () {
    patch('/profile/locale', ['locale' => 'en'])
        ->assertRedirect('/login');
});

test('it sets locale immediately after update', function () {
    $user = User::factory()->create(['locale' => 'pt']);

    actingAs($user)
        ->patch('/profile/locale', ['locale' => 'es'])
        ->assertRedirect()
        ->assertSessionHas('toast');

    expect($user->fresh()->locale)->toBe('es');
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test tests/Feature/Profile/LocaleUpdateTest.php`

Expected output: Tests should fail initially

- [ ] **Step 4: Ensure all tests pass**

Run: `php artisan test tests/Feature/Profile/LocaleUpdateTest.php`

Expected output: All tests pass

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Profile/LocaleUpdateTest.php
git commit -m "test: add LocaleUpdate feature tests"
```

---

## Task 17: Write Translation Keys Validation Test

**Files:**
- Create: `tests/Feature/Lang/TranslationKeysTest.php`

- [ ] **Step 1: Create test file**

```bash
mkdir -p tests/Feature/Lang
touch tests/Feature/Lang/TranslationKeysTest.php
```

- [ ] **Step 2: Write the test that validates all keys exist in all languages**

```php
<?php

test('it has all translation keys in all supported languages', function () {
    $languages = ['pt', 'en', 'es'];
    $translations = [];

    foreach ($languages as $lang) {
        $langFile = lang_path("{$lang}.json");
        expect($langFile)->toBeFile("Language file {$lang}.json must exist");

        $translations[$lang] = json_decode(file_get_contents($langFile), true);
    }

    // Get all unique keys across all languages
    $allKeys = array_unique(array_merge(...array_map('array_keys', $translations)));

    foreach ($languages as $lang) {
        foreach ($allKeys as $key) {
            expect(isset($translations[$lang][$key]))->toBeTrue(
                "Missing translation key '{$key}' in {$lang}.json"
            );
        }
    }
});

test('it has valid JSON in all language files', function () {
    $languages = ['pt', 'en', 'es'];

    foreach ($languages as $lang) {
        $langFile = lang_path("{$lang}.json");
        $content = file_get_contents($langFile);

        $decoded = json_decode($content, true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE, "Invalid JSON in {$lang}.json");
        expect($decoded)->toBeArray("{$lang}.json must contain an array");
    }
});
```

- [ ] **Step 3: Run test to verify it passes**

Run: `php artisan test tests/Feature/Lang/TranslationKeysTest.php`

Expected output: All tests pass

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Lang/TranslationKeysTest.php
git commit -m "test: add translation keys validation test"
```

---

## Task 18: Update Configuration Defaults

**Files:**
- Modify: `config/app.php`

- [ ] **Step 1: Update locale configuration**

Find the locale section in `config/app.php` and update:

```php
'locale' => env('APP_LOCALE', 'pt'),

'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

'faker_locale' => env('APP_FAKER_LOCALE', 'pt_BR'),
```

- [ ] **Step 2: Verify syntax**

Run: `php -l config/app.php`

Expected output: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add config/app.php
git commit -m "feat: update default locale configuration"
```

---

## Task 19: Update Environment Example

**Files:**
- Modify: `.env.example`

- [ ] **Step 1: Add locale environment variables**

Add these lines to `.env.example`:

```env
# Localization
APP_LOCALE=pt
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR
```

- [ ] **Step 2: Commit**

```bash
git add .env.example
git commit -m "docs: add locale environment variables to .env.example"
```

---

## Task 20: Run Full Test Suite

**Files:**
- No file changes

- [ ] **Step 1: Run full test suite**

Run: `php artisan test`

Expected output: All tests pass

- [ ] **Step 2: Run TypeScript type check**

Run: `npm run type-check` (if available) or `npm run build`

Expected output: No TypeScript errors

- [ ] **Step 3: Test the application manually**

1. Start server: `php artisan serve`
2. Visit the application
3. Go to Profile page
4. Change locale
5. Verify UI language changes
6. Logout and test guest locale (if landing page selector added)

Expected output: Application works, locale changes persist

- [ ] **Step 4: Final verification**

Run: `git status`

Expected output: Clean working directory (or only uncommitted changes to documentation)

---

## Completion Criteria

- [x] All 3 JSON translation files created (PT, EN, ES)
- [x] Users table has `locale` column with default value `pt`
- [x] User model supports `locale` attribute
- [x] SetLocaleMiddleware sets locale from authenticated user or cookie
- [x] HandleInertiaRequests shares locale and translations to frontend
- [x] LocaleController handles locale updates
- [x] Frontend useLang composable provides locale management
- [x] Frontend `__()` helper translates strings
- [x] Profile page has locale selector
- [x] All tests pass (unit + feature)
- [x] Translation keys validated across all languages

## Notes

- Translation keys use original English (or most common) string as key
- Toast messages from backend are translated using `__()` before sending
- Frontend can also use `__()` for client-only strings
- Guest locale is stored in cookie (`locale`)
- User locale is persisted in database

## Next Steps (Optional Enhancements)

- Add landing page language selector (guest users)
- Add translation management UI for admins
- Add pluralization tests for complex rules
- Add date/time localization
- Add currency localization
