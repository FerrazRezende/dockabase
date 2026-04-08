# RBAC Permissions Read-only - Design Spec

## Metadata

| Field | Value |
|-------|-------|
| Status | Approved |
| Priority | P0 |
| Created | 2026-04-03 |
| Dependencies | Spatie Permission (installed) |

## Overview

Mudança no modelo de gerenciamento de permissões: ao invés de **permissões imutáveis** definidas via seed, com foco em **gerenciamento de roles**.

### Principais

1. **Permissões são imutáveis** - Não podem criar, editar ou excluir permissões
2. **Roles são gerenciáveis** - Admin cria roles e atribui permissões às roles
3. **Permissões pré-definidas via seed** - Garantidas consistência e segurança
4. **UI simplific** - Menos tabs, menos dialogs, menos clutter

### Permissões Disponíveis

| Recurso | Permissões |
|---------|------------|
| Databases | `view`, `create`, `update`, `delete` |
| Schemas | `view`, `create`, `update`, `delete` |
| Credentials | `view`, `create`, `update`, `delete` |
| Tables | `view`, `create`, `update`, `delete` |

## Arquivos Afetados

- `app/Http/Controllers/System/PermissionController.php`
- `routes/system.php`
- `resources/js/Pages/System/Permissions/Index.vue`
- `database/seeders/RolePermissionSeeder.php` (optional)

## Tasks

### Task 1: Update PermissionController to read-only
**Files:**
- Modify: `app/Http/Controllers/System/PermissionController.php`
- Modify: `routes/system.php`

**- [ ] Step 1: Remove store method**
```php
public function store(StorePermissionRequest $request)
{
    abort_unless($request->user()->is_admin, 403);

    $permission = Permission::create($request->validated());

    if ($request->wantsJson()) {
        return new PermissionResource($permission);
    }

    return redirect()->back()->with('success', 'Permission created successfully.');
}
```

- [ ] Step 2: Remove update method**
```php
public function update(UpdatePermissionRequest $request, Permission $permission)
{
    abort_unless($request->user()->is_admin, 403);

    $permission->update($request->validated());

    if ($request->wantsJson()) {
        return new PermissionResource($permission);
    }

    return redirect()->back()->with('success', 'Permission updated successfully.');
}
```

- [ ] Step 3: Remove destroy method**
```php
public function destroy(Request $request, Permission $permission)
{
    abort_unless($request->user()->is_admin, 403);

    if ($permission->roles()->exists()) {
        return redirect()->back()->withErrors([
            'error' => 'Cannot delete permission that is assigned to roles.',
        ]);
    }

    $permission->delete();

    if ($request->wantsJson()) {
        return response()->json(['message' => 'Permission deleted successfully.']);
    }

    return redirect()->back()->with('success', 'Permission deleted successfully.');
}
```

- [ ] Step 4: Remove routes from routes file**
```php
// Remove these routes from routes/system.php:
Route::post('permissions', [PermissionController::class, 'store'])->name('system.permissions.store');
Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('system.permissions.update');
Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('system.permissions.destroy');
```

- [ ] Step 5: Run tests to verify no regressions**
```bash
php artisan test --filter=Permission
```
Expected: 404 or route not found for POST/PUT/DELETE /permissions
</test>

- [ ] Step 6: Commit backend changes**
```bash
git add app/Http/Controllers/System/PermissionController.php routes/system.php
git commit -m "refactor: remove permission CRUD operations"
```

### Task 2: Update Frontend - Simplify Permissions UI
**Files:**
- Modify: `resources/js/Pages/System/Permissions/Index.vue`

**- [ ] Step 1: Remove tabs and permissions management dialogs**

Remove the tabs component and keep only roles management.
Remove unused permission dialogs: create/edit/delete permission dialogs.
Keep the permissions reference section (read-only).

**- [ ] Step 2: Remove unused dialog refs and variables**
```typescript
// Remove these unused refs
const showCreatePermissionDialog = ref(false);
const showEditPermissionDialog = ref(false);
const showDeletePermissionDialog = ref(false);
const searchPermissions = ref('');
const selectedPermission = ref<Permission | null>(null);
const permissionForm = ref({
    name: '',
});
```

- [ ] Step 3: Test the application manually**
```bash
npm run build
```
Verify no compilation errors.

- [ ] Step 4: Commit frontend changes**
```bash
git add resources/js/Pages/System/Permissions/Index.vue
git commit -m "refactor: simplify RBAC UI - remove permissions tab"
```

### Task 3: Update Seeder (Optional Enhancement)
**Files:**
- Modify: `database/seeders/RolePermissionSeeder.php`

**- [ ] **Step 1: Add user management permissions (optional)**
```php
// Add to the permissions array if needed:
'users.view',
'users.create',
'users.update',
'users.delete',
```

- [ ] **Step 2: Run seeder to verify no errors**
```bash
php artisan db:seed --class=RolePermissionSeeder
```

- [ ] **Step 3: Commit seeder changes (if modified)**
```bash
git add database/seeders/RolePermissionSeeder.php
git commit -m "feat: add user management permissions to seeder"
```

### Task 4: Update Tests (If Exists)
**Files:**
- Check: `tests/` directory for permission-related tests
- Update or create tests as needed

**- [ ] **Step 1: Check for existing permission tests**
```bash
grep -r "permissions" tests/ --include="*.php"
```

- [ ] **Step 2: Update tests to reflect read-only permissions**
Modify any tests that use permission CRUD operations to test only read functionality.
