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
import { Plus, Pencil, Key, Trash2, Search, Info } from 'lucide-vue-next';

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

// Dialogs - Roles
const showCreateRoleDialog = ref(false);
const showEditRoleDialog = ref(false);
const showRolePermissionsDialog = ref(false);
const showDeleteRoleDialog = ref(false);
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

const getCategoryLabel = (category: string): string => {
    const labels: Record<string, string> = {
        databases: 'Databases',
        schemas: 'Schemas',
        credentials: 'Credentials',
        tables: 'Tables',
        users: 'Users',
    };
    return labels[category] || category.charAt(0).toUpperCase() + category.slice(1);
};

// Role actions
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
        permissions: role.permissions.map((p) => p.id),
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
        onSuccess: () => { showDeleteRoleDialog.value = false; },
    });
};
</script>

<template>
    <Head title="Permissões" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-2xl font-semibold text-foreground">
                Permissões
            </h2>
            <p class="text-sm text-muted-foreground mt-1">
                Gerencie roles e permissões do sistema
            </p>
        </template>

        <div class="space-y-6">
            <!-- Roles Section -->
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input
                            v-model="searchRoles"
                            type="search"
                            placeholder="Buscar roles..."
                            class="pl-9"
                        />
                    </div>
                    <Button @click="openCreateRoleDialog">
                        <Plus class="w-4 h-4 mr-2" />
                        Nova Role
                    </Button>
                </div>

                <div class="bg-card shadow-sm rounded-lg border border-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>Permissões</TableHead>
                                <TableHead class="w-[100px]">Usuários</TableHead>
                                <TableHead class="w-[150px]">Ações</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="role in filteredRoles"
                                :key="role.id"
                            >
                                <TableCell class="font-medium">
                                    {{ role.name }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1 max-w-md">
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
                                            class="text-muted-foreground text-sm"
                                        >
                                            Nenhuma permissão
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ role.users_count }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Editar"
                                            @click="openEditRoleDialog(role)"
                                        >
                                            <Pencil class="w-4 h-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Permissões"
                                            @click="openRolePermissionsDialog(role)"
                                        >
                                            <Key class="w-4 h-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Excluir"
                                            :disabled="role.users_count > 0"
                                            @click="openDeleteRoleDialog(role)"
                                        >
                                            <Trash2 class="w-4 h-4 text-destructive" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="filteredRoles.length === 0">
                                <TableCell colspan="4" class="text-center text-muted-foreground py-8">
                                    Nenhuma role encontrada
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Permissions Reference Section -->
            <div class="space-y-4">
                <div class="flex items-start gap-2">
                    <div class="space-y-1">
                        <h3 class="text-lg font-semibold text-foreground">
                            Permissões Disponíveis
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Essas permissões são predefinidas e não podem ser modificadas.
                        </p>
                    </div>
                    <Info class="w-4 h-4 text-muted-foreground mt-1" />
                </div>

                <div class="bg-card shadow-sm rounded-lg border border-border p-6">
                    <div
                        v-for="(permissions, category) in groupedPermissions"
                        :key="category"
                        class="mb-6 last:mb-0"
                    >
                        <h4 class="text-sm font-semibold text-foreground mb-3">
                            {{ getCategoryLabel(category) }}
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
                    <div v-if="Object.keys(groupedPermissions).length === 0" class="text-center text-muted-foreground py-4">
                        Nenhuma permissão disponível
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Role Dialog -->
        <Dialog v-model:open="showCreateRoleDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Nova Role</DialogTitle>
                    <DialogDescription>
                        Crie uma nova role e defina suas permissões.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Nome</label>
                        <Input v-model="roleForm.name" placeholder="ex: Developer" />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Permissões</label>
                        <Select v-model="roleForm.permissions" multiple>
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione as permissões" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="permission in allPermissions"
                                    :key="permission.id"
                                    :value="permission.id"
                                >
                                    {{ permission.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCreateRoleDialog = false">Cancelar</Button>
                    <Button @click="createRole">Criar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Role Dialog -->
        <Dialog v-model:open="showEditRoleDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Editar Role</DialogTitle>
                    <DialogDescription>Altere o nome da role.</DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Nome</label>
                        <Input v-model="roleForm.name" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showEditRoleDialog = false">Cancelar</Button>
                    <Button @click="updateRole">Salvar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Role Permissions Dialog -->
        <Dialog v-model:open="showRolePermissionsDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Permissões: {{ selectedRole?.name }}</DialogTitle>
                    <DialogDescription>Gerencie as permissões desta role.</DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Permissões</label>
                        <Select v-model="roleForm.permissions" multiple>
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione as permissões" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="permission in allPermissions"
                                    :key="permission.id"
                                    :value="permission.id"
                                >
                                    {{ permission.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showRolePermissionsDialog = false">Cancelar</Button>
                    <Button @click="syncRolePermissions">Salvar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Role Dialog -->
        <Dialog v-model:open="showDeleteRoleDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Excluir Role</DialogTitle>
                    <DialogDescription>
                        Tem certeza que deseja excluir a role "{{ selectedRole?.name }}"?
                        Esta ação não pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteRoleDialog = false">Cancelar</Button>
                    <Button variant="destructive" @click="deleteRole">Excluir</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
