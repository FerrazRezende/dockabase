<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { Plus, Pencil, Key, Trash2 } from 'lucide-vue-next';

interface Permission {
    id: number;
    name: string;
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
    roles: {
        data: Role[];
    };
    permissions: Permission[];
}

const props = defineProps<Props>();

const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const showPermissionsDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedRole = ref<Role | null>(null);

const form = ref({
    name: '',
    permissions: [] as number[],
});

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const openCreateDialog = (): void => {
    form.value = { name: '', permissions: [] };
    showCreateDialog.value = true;
};

const createRole = (): void => {
    router.post(route('system.roles.store'), form.value, {
        onSuccess: () => {
            showCreateDialog.value = false;
        },
    });
};

const openEditDialog = (role: Role): void => {
    selectedRole.value = role;
    form.value = { name: role.name, permissions: [] };
    showEditDialog.value = true;
};

const updateRole = (): void => {
    if (!selectedRole.value) return;

    router.put(
        route('system.roles.update', selectedRole.value.id),
        { name: form.value.name },
        {
            onSuccess: () => {
                showEditDialog.value = false;
            },
        }
    );
};

const openPermissionsDialog = (role: Role): void => {
    selectedRole.value = role;
    form.value = {
        name: role.name,
        permissions: role.permissions.map((p) => p.id),
    };
    showPermissionsDialog.value = true;
};

const syncPermissions = (): void => {
    if (!selectedRole.value) return;

    router.post(
        route('system.roles.permissions.sync', selectedRole.value.id),
        { permissions: form.value.permissions },
        {
            onSuccess: () => {
                showPermissionsDialog.value = false;
            },
        }
    );
};

const openDeleteDialog = (role: Role): void => {
    selectedRole.value = role;
    showDeleteDialog.value = true;
};

const deleteRole = (): void => {
    if (!selectedRole.value) return;

    router.delete(route('system.roles.destroy', selectedRole.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
};
</script>

<template>
    <Head title="Roles" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-2xl font-semibold text-foreground">
                Roles
            </h2>
            <p class="text-sm text-muted-foreground mt-1">
                Gerencie as roles do sistema
            </p>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <Button @click="openCreateDialog">
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
                        v-for="role in roles.data"
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
                                    @click="openEditDialog(role)"
                                >
                                    <Pencil class="w-4 h-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Permissões"
                                    @click="openPermissionsDialog(role)"
                                >
                                    <Key class="w-4 h-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Excluir"
                                    :disabled="role.users_count > 0"
                                    @click="openDeleteDialog(role)"
                                >
                                    <Trash2 class="w-4 h-4 text-destructive" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Create Dialog -->
        <Dialog v-model:open="showCreateDialog">
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
                        <Input v-model="form.name" placeholder="ex: Developer" />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Permissões</label>
                        <Select v-model="form.permissions" multiple>
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione as permissões" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="permission in permissions"
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
                    <Button variant="outline" @click="showCreateDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="createRole">Criar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Editar Role</DialogTitle>
                    <DialogDescription>
                        Altere o nome da role.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Nome</label>
                        <Input v-model="form.name" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showEditDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="updateRole">Salvar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Permissions Dialog -->
        <Dialog v-model:open="showPermissionsDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Permissões: {{ selectedRole?.name }}</DialogTitle>
                    <DialogDescription>
                        Gerencie as permissões desta role.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Permissões</label>
                        <Select v-model="form.permissions" multiple>
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione as permissões" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="permission in permissions"
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
                    <Button variant="outline" @click="showPermissionsDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="syncPermissions">Salvar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Excluir Role</DialogTitle>
                    <DialogDescription>
                        Tem certeza que deseja excluir a role "{{ selectedRole?.name }}"?
                        Esta ação não pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancelar
                    </Button>
                    <Button variant="destructive" @click="deleteRole">
                        Excluir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
