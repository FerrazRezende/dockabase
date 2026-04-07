# RBAC Permissions Read-Only Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transform permissions management from full CRUD to read-only, with roles as the primary management interface.

**Architecture:** Remove permission CRUD operations (store, update, destroy) from backend and frontend. Keep permissions as read-only reference data displayed when creating/editing roles. Simplify UI by removing the Permissions tab.

**Tech Stack:** Laravel 13, Spatie Permission, Vue 3, Inertia.js

---

## File Structure

```
app/Http/Controllers/System/
├── PermissionController.php (modify: remove store, update, destroy)
├── RoleController.php (no changes)
├── UserController.php (no changes)

routes/system.php (modify - remove permission CRUD routes)
database/seeders/RolePermissionSeeder.php (modify - keep existing permissions)
resources/js/Pages/System/Permissions/Index.vue (modify - simplify UI)
```

## Tasks

### Task 1: Update PermissionController to read-only
**Files:**
- Modify: `app/Http/Controllers/System/PermissionController.php`
- Modify: `routes/system.php`

**- [ ] **Step 1: Remove store method**
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

- [ ] **Step 2: Remove update method**
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

- [ ] **Step 3: Remove destroy method**
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

- [ ] **Step 4: Remove routes from routes file**

```php
// Remove these routes from routes/system.php:
Route::post('permissions', [PermissionController::class, 'store'])->name('system.permissions.store');
Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('system.permissions.update');
Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('system.permissions.destroy');
```

- [ ] **Step 5: Run tests to verify no regressions**
```bash
php artisan test --filter=Permission
```
Expected: 404 or route not found for POST/PUT/DELETE /permissions

</test>

- [ ] **Step 6: Commit backend changes**
```bash
git add app/Http/Controllers/System/PermissionController.php routes/system.php
git commit -m "refactor: remove permission CRUD operations"
```

### Task 2: Update Frontend - Simplify Permissions UI
**Files:**
- Modify: `resources/js/Pages/System/Permissions/Index.vue`

**- [ ] **Step 1: Remove tabs and permissions management dialogs**

Remove the tabs component and keep only roles management.
Remove unused permission dialogs: create/edit/delete permission dialogs.
Keep the permissions reference section (read-only).

</script>
```
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, Pencil, Key, Trash2, Search } from 'lucide-vue-next';

interface Permission {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
}

interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions: { id: number; name: string }[];
    users_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    permissions: {
        data: Permission[];
    };
    roles: {
        data: Role[];
    };
}

const props = defineProps<Props>();

const searchRoles = ref('');
const selectedRole = ref<Role | null>(null);
const roleForm = ref({
    name: '',
    permissions: [] as number[],
});

const allPermissions = computed(() => props.permissions?.data ?? []);

const filteredRoles = computed(() => {
    const roles = props.roles?.data ?? [];
    if (!searchRoles.value) return roles;
    return roles.filter((role) =>
        role.name.toLowerCase().includes(searchRoles.value.toLowerCase())
    );
});

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const openCreateRoleDialog = (): void => {
    roleForm.value = { name: '', permissions: [] };
    showCreateRoleDialog.value = true;
};

const createRole = (): void => {
    router.post(route('system.roles.store'), roleForm.value, {
        onSuccess: () => {
            showCreateRoleDialog.value = false;
        },
    });
};

const openEditRoleDialog = (role: Role): void => {
    selectedRole.value = role;
    roleForm.value = { name: role.name, permissions: [] };
    showEditRoleDialog.value = true;
};

const updateRole = (): void => {
    if (!selectedRole.value) return;
    router.put(
        route('system.roles.update', selectedRole.value.id),
        { name: roleForm.value.name },
        { onSuccess: () => { showEditRoleDialog.value = false; } }
    );
};

const openRolePermissionsDialog = (role: Role): void => {
    selectedRole.value = role;
    roleForm.value = {
        name: role.name,
        permissions: role.permissions.map(p => p.id),
    };
    showRolePermissionsDialog.value = true;
};

const syncRolePermissions = (): void => {
    if (!selectedRole.value) return;
    router.post(
        route('system.roles.permissions.sync', selectedRole.value.id),
        { permissions: roleForm.value.permissions },
        { onSuccess: () => { showRolePermissionsDialog.value = false; } }
    );
};

const openDeleteRoleDialog = (role: Role): void => {
    selectedRole.value = role;
    showDeleteRoleDialog.value = true;
};

const deleteRole = (): void => {
    if (!selectedRole.value) return;
    router.delete(route('system.roles.destroy', selectedRole.value.id), {
        onSuccess: () => {
            showDeleteRoleDialog.value = false;
            selectedRole.value = null;
        },
    });
};

const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};

    for (const permission of allPermissions.value) {
        const category = permission.name.split('.')[0];
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(permission);
    }

    return groups;
});
</script>

<template>
    <Head title="Roles & Permissions" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Roles & Permissions
            </h2>
        </template>

        <div class="py-12">
            <div class="flex items-center justify-between mb-6">
                <div class="relative flex-1 max-w-md">
                    <Search class="absolute left-3 top-3 h-5 w-4 text-muted" />
                    <Input
                        v-model="searchRoles"
                        type="search"
                        placeholder="Search roles..."
                        class="pl-10 pr-4"
                    />
                </div>
                <Button @click="openCreateRoleDialog">
                    <Plus class="w-4 h-4 mr-2" />
                    Add Role
                </Button>
            </div>

            <!-- Roles Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Permissions</TableHead>
                            <TableHead>Users</TableHead>
                            <TableHead class="w-[120px]">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="role in filteredRoles" :key="role.id">
                            <TableCell class="font-medium">
                                {{ role.name }}
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="permission in role.permissions"
                                        :key="permission.id"
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ permission.name }}
                                    </Badge>
                                    <span
                                        v-if="role.permissions.length === 0"
                                        class="text-muted text-sm"
                                    >
                                        No permissions
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                {{ role.users_count }}
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="openEditRoleDialog(role)"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="openRolePermissionsDialog(role)"
                                    >
                                        <Key class="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="openDeleteRoleDialog(role)"
                                        :disabled="role.users_count > 0"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Permissions Reference Section -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                    Available Permissions
                </h3>
                <p class="text-sm text-muted mb-4">
                    These permissions are predefined and cannot be modified.
                </p>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div
                        v-for="(permissions, category) in groupedPermissions"
                        :key="category"
                        class="mb-4"
                    >
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 capitalize">
                            {{ category }}
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="permission in permissions"
                                :key="permission.id"
                                variant="outline"
                                class="text-sm"
                            >
                                {{ permission.name }}
                            </Badge>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 2: Remove unused dialog refs and variables**
```typescript
// Remove these unused refs
const showCreatePermissionDialog = ref(false);
const showEditPermissionDialog = ref(false);
const showDeletePermissionDialog = ref(false);
const showRolePermissionsDialog = ref(false);
const showDeleteRoleDialog = ref(false);
const searchPermissions = ref('');
const selectedPermission = ref<Permission | null>(null);
const permissionForm = ref({
    name: '',
});
```

- [ ] **Step 3: Test the application manually**
```bash
npm run build
```
Verify no compilation errors.

- [ ] **Step 4: Commit frontend changes**
```bash
git add resources/js/Pages/System/Permissions/Index.vue
git commit -m "refactor: simplify RBAC UI - remove permissions tab"
```

</script>
```
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, Pencil, Key, Trash2, Search } from 'lucide-vue-next';

interface Permission {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
}

interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions: { id: number; name: string }[];
    users_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    permissions: {
        data: Permission[];
    };
    roles: {
        data: Role[];
    };
}

const props = defineProps<Props>();

const searchRoles = ref('');
const selectedRole = ref<Role | null>(null);
const roleForm = ref({
    name: '',
    permissions: [] as number[],
});

const allPermissions = computed(() => props.permissions?.data ?? []);

const filteredRoles = computed(() => {
    const roles = props.roles?.data ?? [];
    if (!searchRoles.value) return roles;
    return roles.filter((role) =>
        role.name.toLowerCase().includes(searchRoles.value.toLowerCase())
    );
});

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const openCreateRoleDialog = (): void => {
    roleForm.value = { name: '', permissions: [] };
    showCreateRoleDialog.value = true;
};

const createRole = (): void => {
    router.post(route('system.roles.store'), roleForm.value, {
        onSuccess: () => {
            showCreateRoleDialog.value = false;
        },
    });
};

const openEditRoleDialog = (role: Role): void => {
    selectedRole.value = role;
    roleForm.value = { name: role.name, permissions: [] };
    showEditRoleDialog.value = true;
};

const updateRole = (): void => {
    if (!selectedRole.value) return;
    router.put(
        route('system.roles.update', selectedRole.value.id),
        { name: roleForm.value.name },
        { onSuccess: () => { showEditRoleDialog.value = false; } }
    );
};

const openRolePermissionsDialog = (role: Role): void => {
    selectedRole.value = role;
    roleForm.value = {
        name: role.name,
        permissions: role.permissions.map(p => p.id),
    };
    showRolePermissionsDialog.value = true;
};

const syncRolePermissions = (): void => {
    if (!selectedRole.value) return;
    router.post(
        route('system.roles.permissions.sync', selectedRole.value.id),
        { permissions: roleForm.value.permissions },
        { onSuccess: () => { showRolePermissionsDialog.value = false; } }
    );
};

const openDeleteRoleDialog = (role: Role): void => {
    selectedRole.value = role;
    showDeleteRoleDialog.value = true;
};

const deleteRole = (): void => {
    if (!selectedRole.value) return;
    router.delete(route('system.roles.destroy', selectedRole.value.id), {
        onSuccess: () => {
            showDeleteRoleDialog.value = false;
            selectedRole.value = null;
        },
    });
};

const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};

    for (const permission of allPermissions.value) {
        const category = permission.name.split('.')[0];
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(permission);
    }

    return groups;
});
</script>

<template>
    <Head title="Roles & Permissions" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Roles & Permissions
            </h2>
        </template>

        <div class="py-12">
            <div class="flex items-center justify-between mb-6">
                <div class="relative flex-1 max-w-md">
                    <Search class="absolute left-3 top-3 h-5 w-4 text-muted" />
                    <Input
                        v-model="searchRoles"
                        type="search"
                        placeholder="Search roles..."
                        class="pl-10 pr-4"
                    />
                </div>
                <Button @click="openCreateRoleDialog">
                    <Plus class="w-4 h-4 mr-2" />
                    Add Role
                </Button>
            </div>

            <!-- Roles Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Permissions</TableHead>
                            <TableHead>Users</TableHead>
                            <TableHead class="w-[120px]">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="role in filteredRoles" :key="role.id">
                            <TableCell class="font-medium">
                                {{ role.name }}
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="permission in role.permissions"
                                        :key="permission.id"
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ permission.name }}
                                    </Badge>
                                    <span
                                        v-if="role.permissions.length === 0"
                                        class="text-muted text-sm"
                                    >
                                        No permissions
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                {{ role.users_count }}
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="openEditRoleDialog(role)"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="openRolePermissionsDialog(role)"
                                    >
                                        <Key class="w-4 h-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="openDeleteRoleDialog(role)"
                                        :disabled="role.users_count > 0"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Permissions Reference Section -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                    Available Permissions
                </h3>
                <p class="text-sm text-muted mb-4">
                    These permissions are predefined and cannot be modified.
                </p>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div
                        v-for="(permissions, category) in groupedPermissions"
                        :key="category"
                        class="mb-4"
                    >
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 capitalize">
                            {{ category }}
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="permission in permissions"
                                :key="permission.id"
                                variant="outline"
                                class="text-sm"
                            >
                                {{ permission.name }}
                            </Badge>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 2: Remove unused dialog refs and variables**
```typescript
// Remove these unused refs
const showCreatePermissionDialog = ref(false);
const showEditPermissionDialog = ref(false);
const showDeletePermissionDialog = ref(false);
const showRolePermissionsDialog = ref(false);
const showDeleteRoleDialog = ref(false);
const searchPermissions = ref('');
const selectedPermission = ref<Permission | null>(null);
const permissionForm = ref({
    name: '',
});
```

- [ ] **Step 3: Test the application manually**
```bash
npm run build
```
Verify no compilation errors.

- [ ] **Step 4: Commit frontend changes**
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

</test>
