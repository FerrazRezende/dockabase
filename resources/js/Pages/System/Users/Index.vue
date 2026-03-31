<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
import {
    Eye,
    Pencil,
    Key,
    Users,
    Trash2,
    Plus,
    Search,
} from 'lucide-vue-next';

interface Role {
    id: number;
    name: string;
}

interface Permission {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    active: boolean;
    roles: string[];
    permissions: string[];
    password_changed_at: string | null;
    created_at: string;
}

interface Props {
    users: {
        data: User[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
    };
    roles: Role[];
    permissions: Permission[];
    filters: { search: string | null };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const showCreateDialog = ref(false);
const showPermissionsDialog = ref(false);
const selectedUser = ref<User | null>(null);

// Form state
const form = ref({
    name: '',
    email: '',
    roles: [] as number[],
    permissions: [] as number[],
});

const permissionsForm = ref({
    roles: [] as number[],
    permissions: [] as number[],
});

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const searchUsers = (): void => {
    router.get(
        route('system.users.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true }
    );
};

const openCreateDialog = (): void => {
    form.value = { name: '', email: '', roles: [], permissions: [] };
    showCreateDialog.value = true;
};

const createUser = (): void => {
    router.post(route('system.users.store'), form.value, {
        onSuccess: () => {
            showCreateDialog.value = false;
        },
    });
};

const openPermissionsDialog = (user: User): void => {
    selectedUser.value = user;
    permissionsForm.value = {
        roles: props.roles
            .filter((r) => user.roles.includes(r.name))
            .map((r) => r.id),
        permissions: props.permissions
            .filter((p) => user.permissions.includes(p.name))
            .map((p) => p.id),
    };
    showPermissionsDialog.value = true;
};

const syncPermissions = (): void => {
    if (!selectedUser.value) return;

    router.post(
        route('system.users.permissions.sync', selectedUser.value.id),
        permissionsForm.value,
        {
            onSuccess: () => {
                showPermissionsDialog.value = false;
            },
        }
    );
};

const impersonate = (userId: number): void => {
    if (confirm('Tem certeza que deseja entrar como este usuário?')) {
        router.post(route('system.users.impersonate.start', userId));
    }
};

const deactivateUser = (userId: number): void => {
    if (confirm('Tem certeza que deseja desativar este usuário?')) {
        router.delete(route('system.users.destroy', userId));
    }
};
</script>

<template>
    <Head title="Usuários" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        Usuários
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Gerencie os usuários do sistema
                    </p>
                </div>
                <Button @click="openCreateDialog">
                    <Plus class="w-4 h-4 mr-2" />
                    Novo Usuário
                </Button>
            </div>
        </template>

        <div class="mb-4">
            <div class="relative max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar usuários..."
                    class="pl-9"
                    @keyup.enter="searchUsers"
                />
            </div>
        </div>

        <div class="bg-card shadow-sm rounded-lg border border-border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nome</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Roles</TableHead>
                        <TableHead class="w-[100px]">Status</TableHead>
                        <TableHead class="w-[200px]">Ações</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="user in users.data"
                        :key="user.id"
                    >
                        <TableCell class="font-medium">
                            {{ user.name }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ user.email }}
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-if="user.is_admin"
                                    variant="default"
                                    class="bg-primary"
                                >
                                    Admin
                                </Badge>
                                <Badge
                                    v-for="role in user.roles"
                                    :key="role"
                                    variant="secondary"
                                >
                                    {{ role }}
                                </Badge>
                                <span
                                    v-if="!user.is_admin && user.roles.length === 0"
                                    class="text-muted-foreground text-sm"
                                >
                                    -
                                </span>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="user.active ? 'default' : 'outline'"
                                :class="user.active ? 'bg-green-500' : 'text-muted-foreground'"
                            >
                                {{ user.active ? 'Ativo' : 'Inativo' }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-1">
                                <Link :href="route('system.users.show', user.id)">
                                    <Button variant="ghost" size="icon" title="Ver perfil">
                                        <Eye class="w-4 h-4" />
                                    </Button>
                                </Link>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Permissões"
                                    @click="openPermissionsDialog(user)"
                                >
                                    <Key class="w-4 h-4" />
                                </Button>
                                <Button
                                    v-if="!user.is_admin"
                                    variant="ghost"
                                    size="icon"
                                    title="Impersonate"
                                    @click="impersonate(user.id)"
                                >
                                    <Users class="w-4 h-4" />
                                </Button>
                                <Button
                                    v-if="!user.is_admin"
                                    variant="ghost"
                                    size="icon"
                                    title="Desativar"
                                    @click="deactivateUser(user.id)"
                                >
                                    <Trash2 class="w-4 h-4 text-destructive" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Create User Dialog -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Novo Usuário</DialogTitle>
                    <DialogDescription>
                        Crie um novo usuário. A senha padrão será "password123".
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Nome</label>
                        <Input v-model="form.name" placeholder="Nome do usuário" />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Email</label>
                        <Input v-model="form.email" type="email" placeholder="email@exemplo.com" />
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Roles</label>
                        <Select v-model="form.roles" multiple>
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione as roles" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.id"
                                >
                                    {{ role.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Permissões Diretas</label>
                        <Select v-model="form.permissions" multiple>
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione permissões extras" />
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
                    <Button @click="createUser">Criar Usuário</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Permissions Dialog -->
        <Dialog v-model:open="showPermissionsDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Permissões: {{ selectedUser?.name }}</DialogTitle>
                    <DialogDescription>
                        Gerencie as roles e permissões diretas do usuário.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Roles</label>
                        <Select v-model="permissionsForm.roles" multiple>
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione as roles" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.id"
                                >
                                    {{ role.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Permissões Diretas</label>
                        <Select v-model="permissionsForm.permissions" multiple>
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione permissões extras" />
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
    </AuthenticatedLayout>
</template>
