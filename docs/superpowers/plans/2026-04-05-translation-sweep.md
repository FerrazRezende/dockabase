# Translation Sweep Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Comprehensive sweep of all hardcoded text in DockaBase, translating to PT/EN/ES organized by functional module.

**Architecture:** Manual file-by-file sweep per module → collect strings → add to JSON files → replace with __() → validate with grep.

**Tech Stack:** Laravel 13, Vue 3, TypeScript, JSON translation files

---

## File Structure

### Files to Modify

| Path | Changes |
|------|---------|
| `lang/pt.json` | Add ~300-500 new translation keys |
| `lang/en.json` | Add ~300-500 new translation keys |
| `lang/es.json` | Add ~300-500 new translation keys |
| `app/Http/Controllers/**/*.php` | Replace strings with `__()` |
| `app/Http/Requests/**/*.php` | Replace strings with `__()` |
| `app/Http/Resources/**/*.php` | Replace strings with `__()` |
| `app/Services/**/*.php` | Replace strings with `__()` |
| `app/Jobs/**/*.php` | Replace strings with `__()` |
| `app/Notifications/**/*.php` | Replace strings with `__()` |
| `resources/js/Pages/**/*.vue` | Replace strings with `__()` |
| `resources/js/components/**/*.vue` | Replace strings with `__()` |
| `CLAUDE.md` | Add localization rules section |

---

## Task 1: Add Laravel Validation Messages (Foundation)

**Files:**
- Modify: `lang/pt.json`
- Modify: `lang/en.json`
- Modify: `lang/es.json`

- [ ] **Step 1: Read current translation files**

Run: `cat lang/pt.json | head -20` to see current structure

- [ ] **Step 2: Add all Laravel validation messages to pt.json**

Append these validation messages to `lang/pt.json`:

```json
{
  "Accepted": "O campo :attribute deve ser aceito.",
  "The :attribute must be accepted.": "O campo :attribute deve ser aceito.",
  "Active URL": "Esta não é uma URL válida.",
  "The :attribute is not a valid URL.": "Esta não é uma URL válida.",
  "After": "A data deve ser uma data posterior a :date.",
  "The :attribute must be a date after :date.": "A data deve ser uma data posterior a :date.",
  "After or equal": "A data deve ser igual ou posterior a :date.",
  "The :attribute must be a date after or equal to :date.": "A data deve ser igual ou posterior a :date.",
  "Alpha": "O campo :attribute só pode conter letras.",
  "The :attribute may only contain letters.": "O campo :attribute só pode conter letras.",
  "Alpha dash": "O campo :attribute só pode conter letras, números e traços.",
  "The :attribute may only contain letters, numbers, dashes and underscores.": "O campo :attribute só pode conter letras, números e traços.",
  "Alpha num": "O campo :attribute só pode conter letras e números.",
  "The :attribute may only contain letters and numbers.": "O campo :attribute só pode conter letras e números.",
  "Array": "O campo :attribute deve ser um array.",
  "The :attribute must be an array.": "O campo :attribute deve ser um array.",
  "Before": "A data deve ser uma data anterior a :date.",
  "The :attribute must be a date before :date.": "A data deve ser uma data anterior a :date.",
  "Before or equal": "A data deve ser igual ou anterior a :date.",
  "The :attribute must be a date before or equal to :date.": "A data deve ser igual ou anterior a :date.",
  "Between": "O campo :attribute deve estar entre :min e :max.",
  "The :attribute must be between :min and :max.": "O campo :attribute deve estar entre :min e :max.",
  "Boolean": "O campo :attribute deve ser verdadeiro ou falso.",
  "The :attribute field must be true or false.": "O campo :attribute deve ser verdadeiro ou falso.",
  "Confirmed": "A confirmação do :attribute não corresponde.",
  "The :attribute confirmation does not match.": "A confirmação do :attribute não corresponde.",
  "Current password": "A senha está incorreta.",
  "The password is incorrect.": "A senha está incorreta.",
  "Date": "O campo :attribute não é uma data válida.",
  "The :attribute is not a valid date.": "O campo :attribute não é uma data válida.",
  "Date equals": "O campo :attribute deve ser uma data igual a :date.",
  "The :attribute must be a date equal to :date.": "O campo :attribute deve ser uma data igual a :date.",
  "Date format": "O formato do :attribute não corresponde a :format.",
  "The :attribute must match the format :format.": "O formato do :attribute não corresponde a :format.",
  "Declined": "O campo :attribute deve ser rejeitado.",
  "The :attribute must be declined.": "O campo :attribute deve ser rejeitado.",
  "Different": "O campo :attribute e :other devem ser diferentes.",
  "The :attribute and :other must be different.": "O campo :attribute e :other devem ser diferentes.",
  "Digits": "O campo :attribute deve ter :digits dígitos.",
  "The :attribute must be :digits digits.": "O campo :attribute deve ter :digits dígitos.",
  "Digits between": "O campo :attribute deve estar entre :min e :max dígitos.",
  "The :attribute must be between :min and :max digits.": "O campo :attribute deve estar entre :min e :max dígitos.",
  "Dimensions": "A imagem tem dimensões inválidas.",
  "The :attribute has invalid image dimensions.": "A imagem tem dimensões inválidas.",
  "Distinct": "O campo :attribute tem um valor duplicado.",
  "The :attribute field has a duplicate value.": "O campo :attribute tem um valor duplicado.",
  "Email": "O campo :attribute deve ser um endereço de e-mail válido.",
  "The :attribute must be a valid email address.": "O campo :attribute deve ser um endereço de e-mail válido.",
  "Ends with": "O campo :attribute deve terminar com um dos seguintes: :values.",
  "The :attribute must end with one of the following: :values.": "O campo :attribute deve terminar com um dos seguintes: :values.",
  "Enum": "O :attribute selecionado é inválido.",
  "The selected :attribute is invalid.": "O :attribute selecionado é inválido.",
  "Exists": "O :attribute selecionado é inválido.",
  "The selected :attribute is invalid.": "O :attribute selecionado é inválido.",
  "File": "O campo :attribute deve ser um arquivo.",
  "The :attribute must be a file.": "O campo :attribute deve ser um arquivo.",
  "Filled": "O campo :attribute é obrigatório.",
  "The :attribute field is required.": "O campo :attribute é obrigatório.",
  "Greater than": "O campo :attribute deve ser maior que :value.",
  "The :attribute must be greater than :value.": "O campo :attribute deve ser maior que :value.",
  "Greater than or equal": "O campo :attribute deve ser maior ou igual a :value.",
  "The :attribute must be greater than or equal to :value.": "O campo :attribute deve ser maior ou igual a :value.",
  "Image": "O campo :attribute deve ser uma imagem.",
  "The :attribute must be an image.": "O campo :attribute deve ser uma imagem.",
  "In": "O campo :attribute selecionado é inválido.",
  "The selected :attribute is invalid.": "O :attribute selecionado é inválido.",
  "In array": "O campo :attribute não existe em :other.",
  "The :attribute field does not exist in :other.": "O campo :attribute não existe em :other.",
  "Integer": "O campo :attribute deve ser um número inteiro.",
  "The :attribute must be an integer.": "O campo :attribute deve ser um número inteiro.",
  "IP address": "O campo :attribute deve ser um endereço IP válido.",
  "The :attribute must be a valid IP address.": "O campo :attribute deve ser um endereço IP válido.",
  "IPv4 address": "O campo :attribute deve ser um endereço IPv4 válido.",
  "The :attribute must be a valid IPv4 address.": "O campo :attribute deve ser um endereço IPv4 válido.",
  "IPv6 address": "O campo :attribute deve ser um endereço IPv6 válido.",
  "The :attribute must be a valid IPv6 address.": "O campo :attribute deve ser um endereço IPv6 válido.",
  "JSON": "O campo :attribute deve ser uma string JSON válida.",
  "The :attribute must be a valid JSON string.": "O campo :attribute deve ser uma string JSON válida.",
  "Less than": "O campo :attribute deve ser menor que :value.",
  "The :attribute must be less than :value.": "O campo :attribute deve ser menor que :value.",
  "Less than or equal": "O campo :attribute deve ser menor ou igual a :value.",
  "The :attribute must be less than or equal to :value.": "O campo :attribute deve ser menor ou igual a :value.",
  "Max": "O campo :attribute não pode ter mais de :max caracteres.",
  "The :attribute must not be greater than :max.": "O campo :attribute não pode ter mais de :max caracteres.",
  "Mimes": "O arquivo deve ser do tipo: :values.",
  "The :attribute must be a file of type: :values.": "O arquivo deve ser do tipo: :values.",
  "Mimetypes": "O arquivo deve ser do tipo: :values.",
  "The :attribute must be a file of type: :values.": "O arquivo deve ser do tipo: :values.",
  "Min": "O campo :attribute deve ter pelo menos :min caracteres.",
  "The :attribute must be at least :min characters.": "O campo :attribute deve ter pelo menos :min caracteres.",
  "Multiple of": "O campo :attribute deve ser um múltiplo de :value.",
  "The :attribute must be a multiple of :value.": "O campo :attribute deve ser um múltiplo de :value.",
  "Not in": "O campo :attribute selecionado é inválido.",
  "The selected :attribute is invalid.": "O campo :attribute selecionado é inválido.",
  "Not regex": "O formato do :attribute é inválido.",
  "The :attribute format is invalid.": "O formato do :attribute é inválido.",
  "Numeric": "O campo :attribute deve ser um número.",
  "The :attribute must be a number.": "O campo :attribute deve ser um número.",
  "Password": "A senha está incorreta.",
  "The password is incorrect.": "A senha está incorreta.",
  "Present": "O campo :attribute deve estar presente quando :values é presente.",
  "The :attribute field is required when :values is present.": "O campo :attribute deve estar presente quando :values é presente.",
  "Prohibited": "O campo :attribute é proibido.",
  "The :attribute field is prohibited.": "O campo :attribute é proibido.",
  "Regex": "O formato do :attribute é inválido.",
  "The :attribute format is invalid.": "O formato do :attribute é inválido.",
  "Required": "O campo :attribute é obrigatório.",
  "The :attribute field is required.": "O campo :attribute é obrigatório.",
  "Same": "O campo :attribute e :other devem ser diferentes.",
  "The :attribute and :other must match.": "O campo :attribute e :other devem ser diferentes.",
  "Size": "O campo :attribute deve ter :size caracteres.",
  "The :attribute must be :size characters.": "O campo :attribute deve ter :size caracteres.",
  "Starts with": "O campo :attribute deve começar com um dos seguintes: :values.",
  "The :attribute must start with one of the following: :values.": "O campo :attribute deve começar com um dos seguintes: :values.",
  "String": "O campo :attribute deve ser uma string.",
  "The :attribute must be a string.": "O campo :attribute deve ser uma string.",
  "Timezone": "O campo :attribute deve ser um fuso horário válido.",
  "The :attribute must be a valid timezone.": "O campo :attribute deve ser um fuso horário válido.",
  "Unique": "O :attribute já está sendo usado.",
  "The :attribute has already been taken.": "O :attribute já está sendo usado.",
  "Uploaded": "O arquivo falhou ao carregar.",
  "The file failed to upload.": "O arquivo falhou ao carregar.",
  "URL": "O formato do :attribute é inválido.",
  "The :attribute format is invalid.": "O formato do :attribute é inválido.",
  "UUID": "O :attribute deve ser um UUID válido.",
  "The :attribute must be a valid UUID.": "O :attribute deve ser um UUID válido."
}
```

- [ ] **Step 3: Add validation messages to en.json**

Add to `lang/en.json` (these are typically already the default, add any missing):

```json
{
  "Accepted": "The :attribute field must be accepted.",
  "Active URL": "The :attribute is not a valid URL.",
  "After": "The :attribute must be a date after :date.",
  "After or equal": "The :attribute must be a date after or equal to :date.",
  "Alpha": "The :attribute may only contain letters.",
  "Alpha dash": "The :attribute may only contain letters, numbers, dashes and underscores.",
  "Alpha num": "The :attribute may only contain letters and numbers.",
  "Array": "The :attribute must be an array.",
  "Before": "The :attribute must be a date before :date.",
  "Before or equal": "The :attribute must be a date before or equal to :date.",
  "Between": "The :attribute must be between :min and :max.",
  "Boolean": "The :attribute field must be true or false.",
  "Confirmed": "The :attribute confirmation does not match.",
  "Current password": "The password is incorrect.",
  "Date": "The :attribute is not a valid date.",
  "Date equals": "The :attribute must be a date equal to :date.",
  "Date format": "The :attribute must match the format :format.",
  "Declined": "The :attribute must be declined.",
  "Different": "The :attribute and :other must be different.",
  "Digits": "The :attribute must be :digits digits.",
  "Digits between": "The :attribute must be between :min and :max digits.",
  "Dimensions": "The :attribute has invalid image dimensions.",
  "Distinct": "The :attribute field has a duplicate value.",
  "Email": "The :attribute must be a valid email address.",
  "Ends with": "The :attribute must end with one of the following: :values.",
  "Enum": "The selected :attribute is invalid.",
  "Exists": "The selected :attribute is invalid.",
  "File": "The :attribute must be a file.",
  "Filled": "The :attribute field is required.",
  "Greater than": "The :attribute must be greater than :value.",
  "Greater than or equal": "The :attribute must be greater than or equal to :value.",
  "Image": "The :attribute must be an image.",
  "In": "The selected :attribute is invalid.",
  "In array": "The :attribute field does not exist in :other.",
  "Integer": "The :attribute must be an integer.",
  "IP address": "The :attribute must be a valid IP address.",
  "IPv4 address": "The :attribute must be a valid IPv4 address.",
  "IPv6 address": "The :attribute must be a valid IPv6 address.",
  "JSON": "The :attribute must be a valid JSON string.",
  "Less than": "The :attribute must be less than :value.",
  "Less than or equal": "The :attribute must be less than or equal to :value.",
  "Max": "The :attribute must not be greater than :max.",
  "Mimes": "The :attribute must be a file of type: :values.",
  "Mimetypes": "The :attribute must be a file of type: :values.",
  "Min": "The :attribute must be at least :min characters.",
  "Multiple of": "The :attribute must be a multiple of :value.",
  "Not in": "The selected :attribute is invalid.",
  "Not regex": "The :attribute format is invalid.",
  "Numeric": "The :attribute must be a number.",
  "Password": "The password is incorrect.",
  "Present": "The :attribute field is required when :values is present.",
  "Prohibited": "The :attribute field is prohibited.",
  "Regex": "The :attribute format is invalid.",
  "Required": "The :attribute field is required.",
  "Same": "The :attribute and :other must match.",
  "Size": "The :attribute must be :size characters.",
  "Starts with": "The :attribute must start with one of the following: :values.",
  "String": "The :attribute must be a string.",
  "Timezone": "The :attribute must be a valid timezone.",
  "Unique": "The :attribute has already been taken.",
  "Uploaded": "The file failed to upload.",
  "URL": "The :attribute format is invalid.",
  "UUID": "The :attribute must be a valid UUID."
}
```

- [ ] **Step 4: Add validation messages to es.json**

Add to `lang/es.json`:

```json
{
  "Accepted": "El campo :attribute debe ser aceptado.",
  "Active URL": "Esta no es una URL válida.",
  "After": "La fecha debe ser posterior a :date.",
  "The :attribute must be a date after :date.": "La fecha debe ser posterior a :date.",
  "After or equal": "La fecha debe ser igual o posterior a :date.",
  "The :attribute must be a date after or equal to :date.": "La fecha debe ser igual o posterior a :date.",
  "Alpha": "El campo :attribute solo puede contener letras.",
  "The :attribute may only contain letters.": "El campo :attribute solo puede contener letras.",
  "Alpha dash": "El campo :attribute solo puede contener letras, números y guiones.",
  "The :attribute may only contain letters, numbers, dashes and underscores.": "El campo :attribute solo puede contener letras, números y guiones.",
  "Alpha num": "El campo :attribute solo puede contener letras y números.",
  "The :attribute may only contain letters and numbers.": "El campo :attribute solo puede contener letras y números.",
  "Array": "El campo :attribute debe ser un array.",
  "The :attribute must be an array.": "El campo :attribute debe ser un array.",
  "Before": "La fecha debe ser anterior a :date.",
  "The :attribute must be a date before :date.": "La fecha debe ser anterior a :date.",
  "Before or equal": "La fecha debe ser igual o anterior a :date.",
  "The :attribute must be a date before or equal to :date.": "La fecha debe ser igual o anterior a :date.",
  "Between": "El campo :attribute debe estar entre :min y :max.",
  "The :attribute must be between :min and :max.": "El campo :attribute debe estar entre :min y :max.",
  "Boolean": "El campo :attribute debe ser verdadero o falso.",
  "The :attribute field must be true or false.": "El campo :attribute debe ser verdadero o falso.",
  "Confirmed": "La confirmación del :attribute no coincide.",
  "The :attribute confirmation does not match.": "La confirmación del :attribute no coincide.",
  "Current password": "La contraseña es incorrecta.",
  "The password is incorrect.": "La contraseña es incorrecta.",
  "Date": "El campo :attribute no es una fecha válida.",
  "The :attribute is not a valid date.": "El campo :attribute no es una fecha válida.",
  "Date equals": "El campo :attribute debe ser una fecha igual a :date.",
  "The :attribute must be a date equal to :date.": "El campo :attribute debe ser una fecha igual a :date.",
  "Date format": "El formato del :attribute no corresponde a :format.",
  "The :attribute must match the format :format.": "El formato del :attribute no corresponde a :format.",
  "Declined": "El campo :attribute debe ser rechazado.",
  "The :attribute must be declined.": "El campo :attribute debe ser rechazado.",
  "Different": "El campo :attribute y :other deben ser diferentes.",
  "The :attribute and :other must be different.": "El campo :attribute y :other deben ser diferentes.",
  "Digits": "El campo :attribute debe tener :digits dígitos.",
  "The :attribute must be :digits digits.": "El campo :attribute debe tener :digits dígitos.",
  "Digits between": "El campo :attribute debe estar entre :min y :max dígitos.",
  "The :attribute must be between :min and :max digits.": "El campo :attribute debe estar entre :min y :max dígitos.",
  "Dimensions": "La imagen tiene dimensiones inválidas.",
  "The :attribute has invalid image dimensions.": "La imagen tiene dimensiones inválidas.",
  "Distinct": "El campo :attribute tiene un valor duplicado.",
  "The :attribute field has a duplicate value.": "El campo :attribute tiene un valor duplicado.",
  "Email": "El campo :attribute debe ser una dirección de correo válida.",
  "The :attribute must be a valid email address.": "El campo :attribute debe ser una dirección de correo válida.",
  "Ends with": "El campo :attribute debe terminar con uno de los siguientes: :values.",
  "The :attribute must end with one of the following: :values.": "El campo :attribute debe terminar con uno de los siguientes: :values.",
  "Enum": "El :attribute seleccionado es inválido.",
  "The selected :attribute is invalid.": "El :attribute seleccionado es inválido.",
  "Exists": "El :attribute seleccionado es inválido.",
  "The selected :attribute is invalid.": "El :attribute seleccionado es inválido.",
  "File": "El campo :attribute debe ser un archivo.",
  "The :attribute must be a file.": "El campo :attribute debe ser un archivo.",
  "Filled": "El campo :attribute es obligatorio.",
  "The :attribute field is required.": "El campo :attribute es obligatorio.",
  "Greater than": "El campo :attribute debe ser mayor que :value.",
  "The :attribute must be greater than :value.": "El campo :attribute debe ser mayor que :value.",
  "Greater than or equal": "El campo :attribute debe ser mayor o igual a :value.",
  "The :attribute must be greater than or equal to :value.": "El campo :attribute debe ser mayor o igual a :value.",
  "Image": "El campo :attribute debe ser una imagen.",
  "The :attribute must be an image.": "El campo :attribute debe ser una imagen.",
  "In": "El :attribute seleccionado es inválido.",
  "The selected :attribute is invalid.": "El :attribute seleccionado es inválido.",
  "In array": "El campo :attribute no existe en :other.",
  "The :attribute field does not exist in :other.": "El campo :attribute no existe en :other.",
  "Integer": "El campo :attribute debe ser un número entero.",
  "The :attribute must be an integer.": "El campo :attribute debe ser un número entero.",
  "IP address": "El campo :attribute debe ser una dirección IP válida.",
  "The :attribute must be a valid IP address.": "El campo :attribute debe ser una dirección IP válida.",
  "IPv4 address": "El campo :attribute debe ser una dirección IPv4 válida.",
  "The :attribute must be a valid IPv4 address.": "El campo :attribute debe ser una dirección IPv4 válida.",
  "IPv6 address": "El campo :attribute debe ser una dirección IPv6 válida.",
  "The :attribute must be a valid IPv6 address.": "El campo :attribute debe ser una dirección IPv6 válida.",
  "JSON": "El campo :attribute debe ser una cadena JSON válida.",
  "The :attribute must be a valid JSON string.": "El campo :attribute debe ser una cadena JSON válida.",
  "Less than": "El campo :attribute debe ser menor que :value.",
  "The :attribute must be less than :value.": "El campo :attribute debe ser menor que :value.",
  "Less than or equal": "El campo :attribute debe ser menor o igual a :value.",
  "The :attribute must be less than or equal to :value.": "El campo :attribute debe ser menor o igual a :value.",
  "Max": "El campo :attribute no puede tener más de :max caracteres.",
  "The :attribute must not be greater than :max.": "El campo :attribute no puede tener más de :max caracteres.",
  "Mimes": "El archivo debe ser del tipo: :values.",
  "The :attribute must be a file of type: :values.": "El archivo debe ser del tipo: :values.",
  "Mimetypes": "El archivo debe ser del tipo: :values.",
  "The :attribute must be a file of type: :values.": "El archivo debe ser del tipo: :values.",
  "Min": "El campo :attribute debe tener al menos :min caracteres.",
  "The :attribute must be at least :min characters.": "El campo :attribute debe tener al menos :min caracteres.",
  "Multiple of": "El campo :attribute debe ser un múltiplo de :value.",
  "The :attribute must be a multiple of :value.": "El campo :attribute debe ser un múltiplo de :value.",
  "Not in": "El :attribute seleccionado es inválido.",
  "The selected :attribute is invalid.": "El :attribute seleccionado es inválido.",
  "Not regex": "El formato del :attribute es inválido.",
  "The :attribute format is invalid.": "El formato del :attribute es inválido.",
  "Numeric": "El campo :attribute debe ser un número.",
  "The :attribute must be a number.": "El campo :attribute debe ser un número.",
  "Password": "La contraseña es incorrecta.",
  "The password is incorrect.": "La contraseña es incorrecta.",
  "Present": "El campo :attribute debe estar presente cuando :values está presente.",
  "The :attribute field is required when :values is present.": "El campo :attribute debe estar presente cuando :values está presente.",
  "Prohibited": "El campo :attribute está prohibido.",
  "The :attribute field is prohibited.": "El campo :attribute está prohibido.",
  "Regex": "El formato del :attribute es inválido.",
  "The :attribute format is invalid.": "El formato del :attribute es inválido.",
  "Required": "El campo :attribute es obligatorio.",
  "The :attribute field is required.": "El campo :attribute es obligatorio.",
  "Same": "El campo :attribute y :other deben ser diferentes.",
  "The :attribute and :other must match.": "El campo :attribute y :other deben ser diferentes.",
  "Size": "El campo :attribute debe tener :size caracteres.",
  "The :attribute must be :size characters.": "El campo :attribute debe tener :size caracteres.",
  "Starts with": "El campo :attribute debe comenzar con uno de los siguientes: :values.",
  "The :attribute must start with one of the following: :values.": "El campo :attribute debe comenzar con uno de los siguientes: :values.",
  "String": "El campo :attribute debe ser una cadena.",
  "The :attribute must be a string.": "El campo :attribute debe ser una cadena.",
  "Timezone": "El campo :attribute debe ser una zona horaria válida.",
  "The :attribute must be a valid timezone.": "El campo :attribute debe ser una zona horaria válida.",
  "Unique": "El :attribute ya está siendo usado.",
  "The :attribute has already been taken.": "El :attribute ya está siendo usado.",
  "Uploaded": "El archivo falló al cargar.",
  "The file failed to upload.": "El archivo falló al cargar.",
  "URL": "El formato del :attribute es inválido.",
  "The :attribute format is invalid.": "El formato del :attribute es inválido.",
  "UUID": "El :attribute debe ser un UUID válido.",
  "The :attribute must be a valid UUID.": "El :attribute debe ser un UUID válido."
}
```

- [ ] **Step 5: Verify JSON syntax**

Run: `php -l lang/pt.json && php -l lang/en.json && php -l lang/es.json`

Expected output: "No syntax errors detected" for all files

- [ ] **Step 6: Run translation keys test**

Run: `php artisan test tests/Feature/Lang/TranslationKeysTest.php`

Expected output: Tests pass (all validation keys exist in all 3 languages)

- [ ] **Step 7: Commit**

```bash
git add lang/*.json
git commit -m "feat: add Laravel validation messages to translation files"
```

---

## Task 2: Module Auth - Backend Controllers

**Files:**
- Modify: `app/Http/Controllers/Auth/LoginController.php`
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Modify: `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- Modify: `app/Http/Controllers/Auth/NewPasswordController.php`
- Modify: `app/Http/Controllers/Auth/ConfirmablePasswordController.php`
- Modify: `app/Http/Controllers/Auth/EmailVerificationNotificationController.php`

- [ ] **Step 1: Read LoginController and collect strings**

Read: `cat app/Http/Controllers/Auth/LoginController.php`

Collect all hardcoded strings that are user-facing (not comments, not technical messages).

- [ ] **Step 2: Add Auth translations to lang files**

Add to all 3 JSON files:

```json
{
  "Email address": "Endereço de e-mail",
  "Password": "Senha",
  "Remember me": "Lembrar-me",
  "Forgot your password?": "Esqueceu sua senha?",
  "Forgot your password?": "Esqueceu sua contraseña?",
  "Login": "Entrar",
  "Logout": "Sair",
  "Please verify your email address": "Por favor, verifique seu endereço de e-mail",
  "Before proceeding, please check your email for a verification link.": "Antes de prosseguir, verifique seu e-mail para um link de verificação.",
  "We have emailed a password reset link": "Enviamos um link de redefinição de senha para seu e-mail",
  "Please confirm your password before continuing.": "Por favor, confirme sua senha antes de continuar.",
  "Whoops! Something went wrong.": "Ops! Algo deu errado.",
  "Email": "E-mail",
  "Correo electrónico": "Correo electrónico",
  "Iniciar sesión": "Iniciar sesión",
  "Cerrar sesión": "Cerrar sesión",
  "¿Olvidaste tu contraseña?": "¿Olvidaste tu contraseña?",
  "Por favor, verifica tu dirección de correo": "Por favor, verifica tu dirección de correo",
  "Antes de continuar, verifica tu correo para un enlace de verificación.": "Antes de continuar, verifica tu correo para un enlace de verificación.",
  "Hemos enviado un enlace de restablecimiento de contraseña": "Hemos enviado un enlace de restablecimiento de contraseña"
}
```

(Add to both pt.json, en.json, es.json with appropriate translations)

- [ ] **Step 3: Replace strings in LoginController**

Find and replace hardcoded strings with `__()`.

Example (adjust based on actual file content):

```php
// Before
return redirect()->intended(RouteServiceProvider::HOME);

// After (if there was a message, replace it)
```

- [ ] **Step 4: Repeat for other Auth controllers**

Read each Auth controller file and replace strings with `__()`.

- [ ] **Step 5: Verify syntax**

Run: `php -l app/Http/Controllers/Auth/*Controller.php`

Expected output: No syntax errors

- [ ] **Step 6: Test Auth routes**

Run: `php artisan route:list | grep auth` to verify routes exist

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Auth/
git commit -m "feat(auth): translate controller messages"
```

---

## Task 3: Module Auth - Frontend Pages

**Files:**
- Modify: `resources/js/Pages/Auth/Login.vue`
- Modify: `resources/js/Pages/Auth/Register.vue`
- Modify: `resources/js/Pages/Auth/ForgotPassword.vue`
- Modify: `resources/js/Pages/Auth/ResetPassword.vue`
- Modify: `resources/js/Pages/Auth/ConfirmPassword.vue`
- Modify: `resources/js/Pages/Auth/ForcePasswordChange.vue`

- [ ] **Step 1: Read Login.vue**

Read: `cat resources/js/Pages/Auth/Login.vue`

- [ ] **Step 2: Collect all text strings**

Extract all template strings: labels, placeholders, button text, error messages.

- [ ] **Step 3: Add Auth frontend translations to lang files**

Add to all 3 JSON files:

```json
{
  "Sign in to your account": "Entre na sua conta",
  "Sign in to your account": "Inicia sesión en tu cuenta",
  "Don't have an account?": "Não tem uma conta?",
  "Sign up": "Cadastrar-se",
  "Forgot your password?": "Esqueceu sua senha?",
  "Remember me": "Lembrar-me",
  "Sign in": "Entrar"
}
```

- [ ] **Step 4: Replace strings in Login.vue**

Replace all hardcoded strings with `__()`.

Example:
```vue
<!-- Before -->
<h1 class="text-2xl">Sign in to your account</h1>

<!-- After -->
<h1 class="text-2xl">{{ __('Sign in to your account') }}</h1>
```

- [ ] **Step 5: Repeat for other Auth pages**

Read and replace strings in Register.vue, ForgotPassword.vue, ResetPassword.vue, ConfirmPassword.vue, ForcePasswordChange.vue.

- [ ] **Step 6: Verify TypeScript**

Run: `npm run build` (check for compilation errors)

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Auth/
git commit -m "feat(auth): translate auth pages"
```

---

## Task 4: Module Users - Backend

**Files:**
- Modify: `app/Http/Controllers/System/UserController.php`
- Modify: `app/Http/Requests/System/StoreUserRequest.php`
- Modify: `app/Http/Requests/System/UpdateUserRequest.php`
- Modify: `app/Http/Resources/SystemUserResource.php`

- [ ] **Step 1: Read UserController**

Read: `cat app/Http/Controllers/System/UserController.php`

- [ ] **Step 2: Collect strings**

Extract all user-facing strings: messages, toasts, responses.

- [ ] **Step 3: Add User translations to lang files**

Add to all 3 JSON files:

```json
{
  "Users": "Usuários",
  "User created successfully": "Usuário criado com sucesso",
  "User updated successfully": "Usuário atualizado com sucesso",
  "User deleted successfully": "Usuário excluído com sucesso",
  "Users": "Usuarios",
  "Usuario creado con éxito": "Usuario creado con éxito",
  "Usuario actualizado con éxito": "Usuario actualizado con éxito",
  "Usuario eliminado con éxito": "Usuario eliminado con éxito",
  "Delete user": "Excluir usuário",
  "Are you sure you want to delete this user?": "Tem certeza que deseja excluir este usuário?",
  "This action cannot be undone.": "Esta ação não pode ser desfeita.",
  "Cancel": "Cancelar",
  "Delete": "Excluir",
  "Edit user": "Editar usuário",
  "Create user": "Criar usuário",
  "Users": "Usuarios",
  "Eliminar usuario": "Eliminar usuario",
  "¿Estás seguro de que deseas eliminar este usuario?": "¿Estás seguro de que deseas eliminar este usuario?",
  "Esta acción no se puede deshacer.": "Esta acción no se puede deshacer.",
  "Editar usuario": "Editar usuario",
  "Crear usuario": "Crear usuario"
}
```

- [ ] **Step 4: Replace strings in UserController**

Find and replace with `__()`.

- [ ] **Step 5: Update UserResource**

Read and update `app/Http/Resources/SystemUserResource.php` to translate any display strings.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/System/ app/Http/Requests/System/ app/Http/Resources/
git commit -m "feat(users): translate user management backend"
```

---

## Task 5: Module Users - Frontend

**Files:**
- Modify: `resources/js/Pages/System/Users/Index.vue`
- Modify: `resources/js/Pages/System/Users/Show.vue`
- Modify: `resources/js/Pages/System/Users/**/Partials/*.vue`

- [ ] **Step 1: Read Users Index page**

Read: `cat resources/js/Pages/System/Users/Index.vue`

- [ ] **Step 2: Collect strings**

Extract table headers, button text, search placeholders, modal text.

- [ ] **Step 3: Add Users frontend translations**

Add to all 3 JSON files (extend with more keys as needed):

```json
{
  "Search users...": "Pesquisar usuários...",
  "Filter": "Filtro",
  "Name": "Nome",
  "Email": "E-mail",
  "Role": "Função",
  "Status": "Status",
  "Actions": "Ações",
  "View": "Ver",
  "Edit": "Editar",
  "Delete": "Excluir",
  "No users found.": "Nenhum usuário encontrado.",
  "Buscar usuarios...": "Buscar usuarios...",
  "Filtro": "Filtro",
  "Correo electrónico": "Correo electrónico",
  "Función": "Función",
  "Acciones": "Acciones",
  "Ver": "Ver",
  "Ningún usuario encontrado.": "Ningún usuario encontrado."
}
```

- [ ] **Step 4: Replace strings in Users pages**

Replace all hardcoded strings with `__()`.

- [ ] **Step 5: Verify and commit**

Run: `npm run build`

```bash
git add resources/js/Pages/System/Users/
git commit -m "feat(users): translate user management frontend"
```

---

## Task 6: Module Databases - Backend

**Files:**
- Modify: `app/Http/Controllers/App/DatabaseController.php`
- Modify: `app/Http/Requests/App/*DatabaseRequest.php`
- Modify: `app/Jobs/CreateDatabaseJob.php`
- Modify: `app/Notifications/DatabaseCreatedNotification.php`

- [ ] **Step 1: Read Database files**

Read controller, requests, job, notification files.

- [ ] **Step 2: Add Database translations**

Add to all 3 JSON files:

```json
{
  "Databases": "Bancos de dados",
  "Create database": "Criar banco de dados",
  "Database created successfully": "Banco de dados criado com sucesso",
  "Database is being created": "Banco de dados está sendo criado",
  "Database creation failed": "Falha ao criar banco de dados",
  "Database deleted successfully": "Banco de dados excluído com sucesso",
  "Connection string": "String de conexão",
  "Host": "Host",
  "Port": "Porta",
  "Database name": "Nome do banco",
  "Username": "Usuário",
  "Password": "Senha",
  "Creating...": "Criando...",
  "Validating": "Validando",
  "Configuring": "Configurando",
  "Migrating": "Migrando",
  "Testing": "Testando",
  "Ready": "Pronto",
  "Bases de datos": "Bases de datos",
  "Crear base de datos": "Crear base de datos",
  "Base de datos creada con éxito": "Base de datos creada con éxito",
  "La base de datos está siendo creada": "La base de datos está siendo creada",
  "Falló al crear la base de datos": "Falló al crear la base de datos",
  "Base de datos eliminada con éxito": "Base de datos eliminada con éxito",
  "Cadena de conexión": "Cadena de conexión",
  "Nombre de la base": "Nombre de la base",
  "Usuario": "Usuario",
  "Contraseña": "Contraseña",
  "Creando...": "Creando...",
  "Validando": "Validando",
  "Configurando": "Configurando",
  "Migrando": "Migrando",
  "Probando": "Probando",
  "Listo": "Listo"
}
```

- [ ] **Step 3: Replace strings in files**

Replace all hardcoded strings with `__()`.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/App/ app/Http/Requests/App/ app/Jobs/CreateDatabaseJob.php app/Notifications/
git commit -m "feat(databases): translate database management backend"
```

---

## Task 7: Module Databases - Frontend

**Files:**
- Modify: `resources/js/Pages/App/Databases/Index.vue`
- Modify: `resources/js/Pages/App/Databases/Show.vue`
- Modify: `resources/js/Pages/App/Databases/Create.vue`

- [ ] **Step 1: Read and collect strings**

Read all Database Vue files and collect strings.

- [ ] **Step 2: Add Database frontend translations**

Add to all 3 JSON files (extend as needed).

- [ ] **Step 3: Replace strings**

Replace all hardcoded strings with `__()`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/App/Databases/
git commit -m "feat(databases): translate database management frontend"
```

---

## Task 8: Module Credentials - Backend & Frontend

**Files:**
- Modify: `app/Http/Controllers/App/CredentialController.php`
- Modify: `resources/js/Pages/App/Credentials/*.vue`

- [ ] **Step 1: Read and collect strings**

Read CredentialController and Vue pages.

- [ ] **Step 2: Add Credential translations**

Add to all 3 JSON files:

```json
{
  "Credentials": "Credenciais",
  "Create credential": "Criar credencial",
  "Credential created successfully": "Credencial criada com sucesso",
  "Credential updated successfully": "Credencial atualizada com sucesso",
  "Credential deleted successfully": "Credencial excluída com sucesso",
  "Permission": "Permissão",
  "read": "leitura",
  "write": "escrita",
  "read-write": "leitura e escrita",
  "Select databases": "Selecione os bancos",
  "Select users": "Selecione os usuários",
  "Credenciales": "Credenciales",
  "Crear credencial": "Crear credencial",
  "Credencial creada con éxito": "Credencial creada con éxito",
  "Credencial actualizada con éxito": "Credencial actualizada con éxito",
  "Credencial eliminada con éxito": "Credencial eliminada con éxito",
  "Permiso": "Permiso",
  "lectura": "lectura",
  "escritura": "escritura",
  "lectura y escritura": "lectura y escritura",
  "Seleccionar bases de datos": "Seleccionar bases de datos",
  "Seleccionar usuarios": "Seleccionar usuarios"
}
```

- [ ] **Step 3: Replace strings**

Replace all hardcoded strings with `__()`.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/App/CredentialController.php resources/js/Pages/App/Credentials/
git commit -m "feat(credentials): translate credential management"
```

---

## Task 9: Module Features - Backend & Frontend

**Files:**
- Modify: `app/Http/Controllers/System/FeatureController.php`
- Modify: `resources/js/Pages/System/Features/*.vue`

- [ ] **Step 1: Add Feature translations**

```json
{
  "Features": "Funcionalidades",
  "Enable feature": "Ativar funcionalidade",
  "Disable feature": "Desativar funcionalidade",
  "Feature enabled successfully": "Funcionalidade ativada com sucesso",
  "Feature disabled successfully": "Funcionalidade desativada com sucesso",
  "Feature flags": "Flags de funcionalidade",
  "Active": "Ativo",
  "Inactive": "Inativo",
  "Scope": "Escopo",
  "Características": "Características",
  "Activar característica": "Activar característica",
  "Desactivar característica": "Desactivar característica",
  "Característica activada con éxito": "Característica activada con éxito",
  "Característica desactivada con éxito": "Característica desactivada con éxito"
}
```

- [ ] **Step 2: Replace strings**

Replace in controller and Vue files.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/System/FeatureController.php resources/js/Pages/System/Features/
git commit -m "feat(features): translate feature flags"
```

---

## Task 10: Module Permissions - Backend & Frontend

**Files:**
- Modify: `app/Http/Controllers/System/PermissionController.php`
- Modify: `resources/js/Pages/System/Permissions/Index.vue`

- [ ] **Step 1: Add Permission translations**

```json
{
  "Permissions": "Permissões",
  "Permission created successfully": "Permissão criada com sucesso",
  "Permission updated successfully": "Permissão atualizada com sucesso",
  "Permission deleted successfully": "Permissão excluída com sucesso",
  "Filter permissions": "Filtrar permissões",
  "Permission": "Permissão",
  "Permisos": "Permisos",
  "Permiso": "Permiso",
  "Permiso creado con éxito": "Permiso creado con éxito",
  "Permiso actualizado con éxito": "Permiso actualizado con éxito",
  "Permiso eliminado con éxito": "Permiso eliminado con éxito",
  "Filtrar permisos": "Filtrar permisos"
}
```

- [ ] **Step 2: Replace and commit**

Replace strings, then:

```bash
git add app/Http/Controllers/System/PermissionController.php resources/js/Pages/System/Permissions/
git commit -m "feat(permissions): translate permissions"
```

---

## Task 11: Module Roles - Backend & Frontend

**Files:**
- Modify: `app/Http/Controllers/System/RoleController.php`
- Modify: `resources/js/Pages/System/Roles/Form.vue`

- [ ] **Step 1: Add Role translations**

```json
{
  "Roles": "Funções",
  "Role created successfully": "Função criada com sucesso",
  "Role updated successfully": "Função atualizada com sucesso",
  "Role deleted successfully": "Função excluída com sucesso",
  "Role name": "Nome da função",
  "Select permissions": "Selecione as permissões",
  "Permissions": "Permissões",
  "Funções": "Funciones",
  "Función creada con éxito": "Función creada con éxito",
  "Función actualizada con éxito": "Función actualizada con éxito",
  "Función eliminada con éxito": "Función eliminada con éxito",
  "Nombre de la función": "Nombre de la función",
  "Seleccionar permisos": "Seleccionar permisos",
  "Permisos": "Permisos"
}
```

- [ ] **Step 2: Replace and commit**

```bash
git add app/Http/Controllers/System/RoleController.php resources/js/Pages/System/Roles/
git commit -m "feat(roles): translate roles"
```

---

## Task 12: Update CLAUDE.md

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Read current CLAUDE.md**

Read: `cat CLAUDE.md`

- [ ] **Step 2: Add Localization section**

Add this section after the relevant section (find the right place contextually):

```markdown
## Localização e Traduções

**REGRA CRÍTICA:** Todas as funcionalidades novas DEVEM usar traduções com `__()`. NÃO use texto hardcoded em PT, EN ou ES.

### Backend (Laravel)
- Use `__('Texto original')` para mensagens ao usuário
- Em Controllers: `return redirect()->back()->with('toast', ['message' => __('Success message')])`
- Em Resources: use `__()` para labels e valores
- Validações do Laravel são traduzidas automaticamente pelo arquivo de tradução
- Em Jobs e Notifications: use `__()` para mensagens

### Frontend (Vue)
- Use `__('Texto original')` em componentes Vue
- O helper `__()` está disponível globalmente (não precisa importar)
- Para parâmetros: `__('Hello :name', { name: 'John' })`
- Em templates: `{{ __('Texto') }}`

### Adicionando Novas Traduções
1. Adicione a chave (texto original ou mais comum) nos 3 arquivos: `lang/pt.json`, `lang/en.json`, `lang/es.json`
2. Sincronize as chaves - TODAS as chaves devem existir em TODOS os idiomas
3. Use no código: `__('sua chave')`
4. Valide: `php artisan test tests/Feature/Lang/TranslationKeysTest.php`

### Validação de Traduções
Execute o teste de validação antes de commitar:
```bash
php artisan test tests/Feature/Lang/TranslationKeysTest.php
```

Este teste verifica se todas as chaves existem em todos os idiomas (PT, EN, ES).

### Mensagens de Validação Laravel
Todas as mensagens de validação padrão do Laravel estão traduzidas. Não é necessário usar `__()` em regras de validação - o Laravel traduz automaticamente.
```

- [ ] **Step 3: Verify format**

Run: `cat CLAUDE.md | head -50` to ensure proper formatting

- [ ] **Step 4: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: add localization rules to CLAUDE.md"
```

---

## Task 13: Grep Validation - Find Remaining Strings

**Files:**
- No file changes (validation only)

- [ ] **Step 1: Run PHP grep validation**

Run: `grep -r '"[^"]*"' --include="*.php" app/ | grep -E "(message|error|success|warning|Toast)" | grep -v "//" | head -20`

Expected output: Any remaining hardcoded strings

- [ ] **Step 2: Run Vue grep validation**

Run: `grep -r '">[^"]*<' --include="*.vue" resources/js/ | grep -v "v-if\|v-for\|v-model" | head -20`

Expected output: Any remaining hardcoded strings

- [ ] **Step 3: Document findings**

Create a list of any remaining strings found (if any).

- [ ] **Step 4: If strings found, add to translation and replace**

If grep found remaining strings:
1. Add translations to lang files
2. Replace in source files
3. Commit changes

---

## Task 14: Final Validation

**Files:**
- No file changes (validation only)

- [ ] **Step 1: Run TranslationKeysTest**

Run: `php artisan test tests/Feature/Lang/TranslationKeysTest.php`

Expected output: All tests pass

- [ ] **Step 2: Run full test suite**

Run: `php artisan test`

Expected output: All tests pass

- [ ] **Step 3: Test language switching manually**

1. Start server: `php artisan serve`
2. Visit profile page
3. Change language to EN
4. Navigate through Auth, Users, Databases pages
5. Verify all text is translated
6. Change to ES and verify again

- [ ] **Step 4: Final commit if needed**

If any adjustments were made:

```bash
git add lang/ CLAUDE.md
git commit -m "feat: finalize translation sweep"
```

---

## Completion Criteria

- [ ] All Laravel validation messages added to translation files
- [ ] Module Auth fully translated (backend + frontend)
- [ ] Module Users fully translated (backend + frontend)
- [ ] Module Databases fully translated (backend + frontend)
- [ ] Module Credentials fully translated (backend + frontend)
- [ ] Module Features fully translated (backend + frontend)
- [ ] Module Permissions fully translated (backend + frontend)
- [ ] Module Roles fully translated (backend + frontend)
- [ ] CLAUDE.md updated with localization rules
- [ ] Grep validation finds no remaining hardcoded user-facing strings
- [ ] TranslationKeysTest passes
- [ ] Manual testing confirms all 3 languages work

## Notes

- Always add keys to ALL 3 language files (pt, en, es)
- Use the English or most common string as the key
- Replace strings systematically - don't miss any
- Test after each module completion
- Commit frequently with descriptive messages
