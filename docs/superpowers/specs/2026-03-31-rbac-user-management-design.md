# RBAC & User Management - Design Spec

## Metadata

| Field | Value |
|-------|-------|
| Status | Approved |
| Priority | P0 |
| Created | 2026-03-31 |
| Dependencies | Spatie Permission (installed) |

## Overview

Sistema de RBAC usando Spatie Permission para controle de acesso ao painel do DockaBase.

### Separação de Conceitos

| Sistema | Propósito | Escopo |
|---------|-----------|--------|
| `is_admin` (God Admin) | Gerenciar o sistema DockaBase | `/system/*` routes |
| Spatie RBAC | Controle de acesso ao painel | Ações dentro da aplicação |
| Credentials | Acesso à API dinâmica | `read/write/read-write` |

## Permissões Disponíveis

| Recurso | Permissões | Descrição |
|---------|------------|-----------|
| Databases | `view`, `create`, `edit`, `delete` | Gerenciar databases |
| Schemas | `view`, `create`, `edit`, `delete` | Gerenciar schemas/tabelas |
| Credentials | `view`, `create`, `edit`, `delete` | Gerenciar credentials |
| Features | `view`, `manage` | Ver/gerenciar feature flags |

Formato: `{resource}.{action}` (ex: `databases.view`, `schemas.create`)

## Roles

- **Sem roles padrão** - Admin cria roles customizadas
- Cada role tem um conjunto de permissões
- Usuários podem ter múltiplas roles

## Modelo de Permissões

**Aditivo**: Role permissions + Direct permissions = Permissão final

```
Usuário "Carlos"
├── Role: "Developer"
│   ├── databases.view ✓
│   ├── databases.create ✓
│   └── schemas.view ✓
│
└── Direct: credentials.view ✓  (exceção)
```

## User Stories

### US1: Criar Role
**Como** admin god-like
**Quero** criar roles com permissões customizadas
**Para** definir perfis de acesso para os usuários

### US2: Gerenciar Permissões de Usuário
**Como** admin god-like
**Quero** atribuir roles e permissões diretas aos usuários
**Para** controlar o que cada um pode fazer no sistema

### US3: Forçar Troca de Senha
**Como** admin god-like
**Quero** que novos usuários troquem a senha padrão no primeiro login
**Para** garantir segurança

### US4: Impersonate
**Como** admin god-like
**Quero** acessar o sistema como outro usuário
**Para** debugar e ajudar usuários

## Arquitetura

### Rotas

```
/system/permissions
├── GET    /               → lista
├── POST   /               → criar
├── PUT    /{id}           → editar
└── DELETE /{id}           → excluir

/system/roles
├── GET    /               → lista
├── POST   /               → criar
├── PUT    /{id}           → editar
├── DELETE /{id}           → excluir
└── POST   /{id}/permissions → sincronizar permissões

/system/users
├── GET    /               → lista
├── POST   /               → criar (com roles/perms)
├── GET    /{id}           → perfil completo
├── PUT    /{id}           → editar
├── DELETE /{id}           → excluir
├── POST   /{id}/permissions → sincronizar roles/perms
├── POST   /{id}/impersonate → iniciar impersonate
└── POST   /stop-impersonating → parar impersonate
```

### Modificações no User Model

```php
// Novos campos
$password_changed_at // nullable, null = precisa trocar
$active              // boolean, para desativar sem excluir
```

### Middleware

| Middleware | Função |
|------------|--------|
| `EnsurePasswordChanged` | Bloqueia acesso se `password_changed_at` é null |
| `HandleImpersonation` | Gerencia sessão de impersonate |

### Controllers

```
app/Http/Controllers/System/
├── PermissionController.php
├── RoleController.php
├── UserController.php
└── ImpersonateController.php
```

### Views (Inertia)

```
resources/js/Pages/System/
├── Permissions/Index.vue
├── Roles/Index.vue
├── Users/
│   ├── Index.vue
│   ├── Create.vue
│   ├── Edit.vue
│   ├── Permissions.vue
│   └── Show.vue (perfil)
└── Auth/ForcePasswordChange.vue
```

## Fluxos

### Criação de Usuário

1. Admin preenche: nome, email, roles, permissões diretas
2. Senha padrão: `password123`
3. `password_changed_at` = null
4. Usuário criado

### Primeiro Login

1. Middleware detecta `password_changed_at` = null
2. Redireciona para `/force-password-change`
3. Usuário troca a senha
4. `password_changed_at` = now()
5. Acesso liberado

### Impersonate

1. Admin clica "Impersonate"
2. Sessão salva `original_user_id` e `impersonating_id`
3. Admin navega como o usuário
4. Banner mostra "Você está como {nome}. [Sair]"
5. Clica "Sair" → volta para conta original

## UI

### Listagem de Usuários

| Nome | Email | Roles | Ações |
|------|-------|-------|-------|
| João | joao@email.com | Developer | [👁] [✏️] [🔑] [🎭] [🗑️] |

Legenda:
- 👁 Ver perfil
- ✏️ Editar
- 🔑 Permissões
- 🎭 Impersonate
- 🗑️ Excluir

### Perfil do Usuário (página inteira)

- Informações básicas
- Roles
- Permissões (herdadas + diretas, com origem)
- Features visíveis
- Credentials
- Databases

### Banner de Impersonate

```
⚠ Você está acessando como João Silva. [Sair]
```

## Segurança

- Apenas `is_admin = true` pode gerenciar RBAC
- Apenas `is_admin = true` pode iniciar impersonate
- Não pode impersonate outro admin
- Senha padrão deve ser trocada no primeiro login
