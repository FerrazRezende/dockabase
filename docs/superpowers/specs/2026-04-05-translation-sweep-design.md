# Translation Sweep - Design Spec

**Date:** 2026-04-05
**Status:** Draft
**Feature:** Comprehensive translation sweep for all hardcoded text in DockaBase

## Overview

Execute a systematic sweep of the entire DockaBase codebase to identify and translate all hardcoded text strings into Portuguese (PT), English (EN), and Spanish (ES). The work is organized by functional modules to ensure complete coverage while maintaining code quality.

## Translation Files

All translations are added to the existing JSON files in `/lang/`:
- `lang/pt.json` - Portuguese (Brazil)
- `lang/en.json` - English
- `lang/es.json` - Spanish

## Module Execution Order

1. **Auth** - Authentication system (login, register, password reset)
2. **Users** - User management (CRUD, list, edit)
3. **Databases** - Database management (create, list, show, status)
4. **Credentials** - Credential management
5. **Features** - Feature flags
6. **Permissions** - Permissions management
7. **Roles** - Roles management

## Process per Module

### 1. Identification Phase

**Backend Files:**
- Controllers: `app/Http/Controllers/{Module}/**/*Controller.php`
- Requests: `app/Http/Requests/{Module}/**/*Request.php`
- Resources: `app/Http/Resources/{Module}/**/*Resource.php`
- Services: `app/Services/**/*Service.php`
- Jobs: `app/Jobs/**/*Job.php`
- Notifications: `app/Notifications/**/*Notification.php`

**Frontend Files:**
- Pages: `resources/js/Pages/{Module}/**/*.vue`
- Components: `resources/js/components/**/*{Module}*.vue`
- Partials: `resources/js/Pages/{Module}/Partials/*.vue`

### 2. Collection Phase

For each file:
1. Read the file completely
2. Extract all user-facing strings:
   - Toast/notification messages
   - UI labels and button text
   - Validation messages
   - Error messages
   - Success messages
   - Titles and headings
   - Form labels
   - Modal/dialog text

### 3. Translation Phase

For each collected string:
1. Use the English (or most common) version as the key
2. Add Portuguese translation to `lang/pt.json`
3. Add English translation to `lang/en.json`
4. Add Spanish translation to `lang/es.json`
5. Ensure identical keys across all 3 files

### 4. Replacement Phase

**Backend:**
- Replace hardcoded strings with `__('key')`
- For parameters: `__('key with :param', ['param' => $value])`

**Frontend:**
- Replace hardcoded strings with `__('key')`
- For parameters: `__('key with :param', { param: value })`

### 5. Validation Phase

After completing a module:
1. Run grep to find remaining hardcoded strings
2. Run `php artisan test tests/Feature/Lang/TranslationKeysTest.php`
3. Test the functionality in browser with all 3 languages

## Laravel Validation Messages

Add ALL Laravel default validation messages to the translation files:

**Required:**
- accepted
- active_url
- after
- after_or_equal
- alpha
- alpha_dash
- alpha_num
- array
- before
- before_or_equal
- between
- boolean
- confirmed
- current_password
- date
- date_equals
- date_format
- declined
- different
- digits
- digits_between
- dimensions
- distinct
- does_not_end_with
- does_not_start_with
- email
- ends_with
- enum
- exists
- file
- filled
- gt
- gte
- image
- in
- in_array
- integer
- ip
- ipv4
- ipv6
- json
- lt
- lte
- max
- max_digits
- mimes
- mimetypes
- min
- min_digits
- missing
- missing_if
- missing_unless
- missing_with
- missing_with_all
- multiple_of
- not_in
- not_regex
- numeric
- password
- present
- present_if
- present_unless
- present_with
- present_with_all
- prohibited
- prohibited_if
- prohibited_unless
- prohibited_with
- prohibited_with_all
- prosehibited
- regex
- required
- required_if
- required_unless
- required_with
- required_with_all
- required_array_keys
- starts_with
- string
- timezone
- unique
- uploaded
- url
- ulid
- uuid

## Grep Patterns for Validation

After manual sweep, use these grep patterns to find remaining strings:

```bash
# PHP files with strings
grep -r '"[^"]*"' --include="*.php" app/ | grep -v "//"
grep -r "'[^']*'" --include="*.php" app/ | grep -v "//"

# Vue files with strings
grep -r '">[^"]*<' --include="*.vue" resources/js/
grep -r "'[^']*'" --include="*.vue" resources/js/

# Specific patterns
grep -r "message\|error\|success\|warning" --include="*.php" app/
grep -r "toast\|notification\|alert" --include="*.vue" resources/js/
grep -r "Create\|Update\|Delete\|Edit\|Save\|Cancel" --include="*.php" app/
```

## CLAUDE.md Updates

Add the following section to `CLAUDE.md`:

```markdown
## Localização e Traduções

**REGRA:** Todas as funcionalidades novas DEVE usar traduções com `__()`. NÃO use texto hardcoded em PT, EN ou ES.

### Backend
- Use `__('Texto original')` para mensagens ao usuário
- Para validação, strings em FormRequest são traduzidas automaticamente pelo Laravel
- Em Resources, use `__()` para labels e valores de exibição
- Em Controllers, use `__()` para mensagens de toast, erro, sucesso
- Em Jobs e Notifications, use `__()` para mensagens

### Frontend
- Use `__('Texto original')` em componentes Vue
- O helper `__()` está disponível globalmente (não precisa importar)
- Para parâmetros: `__('Hello :name', { name: 'John' })`
- Use em templates: `{{ __('Text') }}`

### Adicionando Novas Traduções
1. Adicione a chave em inglês (ou texto mais comum) nos 3 arquivos: `lang/pt.json`, `lang/en.json`, `lang/es.json`
2. Sincronize as chaves - TODAS as chaves devem existir em TODOS os idiomas
3. Use a chave no código com `__('sua chave')`
4. Valide com `php artisan test tests/Feature/Lang/TranslationKeysTest.php`

### Mensagens de Validação
Todas as mensagens de validação do Laravel estão traduzidas. Não precisa usar `__()` em regras de validação - o Laravel traduz automaticamente.

### Testando
- Teste a funcionalidade em todos os 3 idiomas
- Verifique se não há texto hardcoded restante com grep
- Execute `php artisan test tests/Feature/Lang/TranslationKeysTest.php` antes de commitar
```

## Module Breakdown

### Module 1: Auth

**Files:**
- `app/Http/Controllers/Auth/*`
- `app/Http/Requests/Auth/*`
- `resources/js/Pages/Auth/*.vue`
- `resources/js/Pages/Auth/**/Partials/*.vue`

**Strings to translate:**
- Login form labels and buttons
- Registration form
- Password reset flow
- Email verification
- Password confirmation
- Force password change

### Module 2: Users

**Files:**
- `app/Http/Controllers/System/UserController.php`
- `app/Http/Requests/System/*UserRequest.php`
- `app/Http/Resources/SystemUserResource.php`
- `resources/js/Pages/System/Users/*.vue`
- `resources/js/Pages/System/Users/**/Partials/*.vue`

**Strings to translate:**
- User list page
- Create user form
- Edit user form
- Delete user confirmation
- User show page
- Toast messages

### Module 3: Databases

**Files:**
- `app/Http/Controllers/App/DatabaseController.php`
- `app/Http/Requests/App/*DatabaseRequest.php`
- `app/Http/Resources/DatabaseResource.php`
- `app/Jobs/CreateDatabaseJob.php`
- `app/Events/Database*`
- `app/Notifications/DatabaseCreatedNotification.php`
- `resources/js/Pages/App/Databases/*.vue`

**Strings to translate:**
- Database list
- Create database form
- Database status messages
- Creation steps
- Toast notifications

### Module 4: Credentials

**Files:**
- `app/Http/Controllers/App/CredentialController.php`
- `resources/js/Pages/App/Credentials/*.vue`

**Strings to translate:**
- Credential list
- Create credential form
- Credential details
- Toast messages

### Module 5: Features

**Files:**
- `app/Http/Controllers/System/FeatureController.php`
- `resources/js/Pages/System/Features/*.vue`

**Strings to translate:**
- Feature list
- Enable/disable actions
- Feature descriptions
- Toast messages

### Module 6: Permissions

**Files:**
- `app/Http/Controllers/System/PermissionController.php`
- `resources/js/Pages/System/Permissions/*.vue`

**Strings to translate:**
- Permission list
- Permission details
- Filter options

### Module 7: Roles

**Files:**
- `app/Http/Controllers/System/RoleController.php`
- `app/Http/Requests/System/*RoleRequest.php`
- `resources/js/Pages/System/Roles/*.vue`

**Strings to translate:**
- Role list
- Create/edit role form
- Permission selector
- Toast messages

## Success Criteria

- [ ] All 7 modules translated
- [ ] All Laravel validation messages translated
- [ ] No hardcoded strings remaining (verified via grep)
- [ ] TranslationKeysTest passes
- [ ] CLAUDE.md updated with localization rules
- [ ] All functionality tested in PT, EN, ES

## Testing Strategy

After each module completion:
1. Run `php artisan test tests/Feature/Lang/TranslationKeysTest.php`
2. Run grep validation for that module
3. Manual test in browser with language switching
4. Verify no regressions in existing functionality

## Dependencies

- Existing localization system (Tasks 1-17 completed)
- `__()` helper available globally
- `useLang` composable available
- Translation files exist with initial keys
