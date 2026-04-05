<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from 'vue-toastification';
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
    User,
    Trash2,
    Plus,
    Search,
} from 'lucide-vue-next';

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    active: boolean;
    roles?: string[];
    password_changed_at: string | null;
    created_at: string;
}

interface Props {
    users?: User[];
    allRoles?: Role[];
    filters?: { search: string | null };
}

const props = defineProps<Props>();

const toast = useToast();
const search = ref(props.filters?.search || '');
const showCreateDialog = ref(false);
const showImpersonateDialog = ref(false);
const showDeactivateDialog = ref(false);
const selectedUserId = ref<number | null>(null);
const selectedUserName = ref('');

// Form state
const form = ref({
    name: '',
    email: '',
    role_id: '' as number | string,
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
    // Verificar se há roles disponíveis
    if (!props.allRoles || props.allRoles.length === 0) {
        toast.error('Não é possível criar um usuário sem roles, crie uma role primeiro');
        return;
    }
    form.value = { name: '', email: '', role_id: '' };
    showCreateDialog.value = true;
};

const createUser = (): void => {
    // Validar se uma role foi selecionada
    if (!form.value.role_id) {
        toast.error('Selecione uma role para o usuário');
        return;
    }

    const payload = {
        name: form.value.name,
        email: form.value.email,
        role_id: Number(form.value.role_id),
    };

    router.post(route('system.users.store'), payload, {
        onSuccess: () => {
            showCreateDialog.value = false;
            toast.success('Usuário criado com sucesso');
        },
        onError: () => {
            toast.error('Erro ao criar usuário. Tente novamente.');
        },
    });
};

const impersonate = (userId: number): void => {
    const user = props.users?.find(u => u.id === userId);
    if (user) {
        selectedUserName.value = user.name;
        selectedUserId.value = userId;
        showImpersonateDialog.value = true;
    }
};

const confirmImpersonate = (): void => {
    if (selectedUserId.value !== null) {
        router.post(route('system.users.impersonate.start', selectedUserId.value));
        showImpersonateDialog.value = false;
        selectedUserId.value = null;
        selectedUserName.value = '';
    }
};

const deactivateUser = (userId: number): void => {
    const user = props.users?.find(u => u.id === userId);
    if (user) {
        selectedUserName.value = user.name;
        selectedUserId.value = userId;
        showDeactivateDialog.value = true;
    }
};

const confirmDeactivate = (): void => {
    if (selectedUserId.value !== null) {
        router.delete(route('system.users.destroy', selectedUserId.value), {
            onSuccess: () => {
                showDeactivateDialog.value = false;
                selectedUserId.value = null;
                selectedUserName.value = '';
                toast.success('Usuário desativado com sucesso');
            },
            onError: () => {
                toast.error('Erro ao desativar usuário. Tente novamente.');
            },
        });
    }
};
</script>

<template>
    <Head title="Usuários" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-2xl font-semibold text-foreground">
                Usuários
            </h2>
            <p class="text-sm text-muted-foreground mt-1">
                Gerencie os usuários do sistema
            </p>
        </template>

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Buscar usuários..."
                        class="pl-9"
                        @keyup.enter="searchUsers"
                    />
                </div>
                <Button @click="openCreateDialog">
                    <Plus class="w-4 h-4 mr-2" />
                    Novo Usuário
                </Button>
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
                        v-for="user in (users ?? [])"
                        :key="user.id"
                    >
                        <TableCell class="font-medium">
                            <Link :href="route('system.users.show', user.id)" class="hover:underline">
                                {{ user.name }}
                            </Link>
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
                                    v-if="!user.is_admin && (!user.roles || user.roles.length === 0)"
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
                                    v-if="$page.props.auth.user.is_admin && !user.is_admin"
                                    variant="ghost"
                                    size="icon"
                                    title="Impersonate"
                                    @click="impersonate(user.id)"
                                >
                                    <User class="w-4 h-4" />
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
                        <label class="text-sm font-medium">Role *</label>
                        <Select v-model="form.role_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione uma role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in allRoles"
                                    :key="role.id"
                                    :value="String(role.id)"
                                >
                                    {{ role.name }}
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

        <!-- Impersonate Confirmation Dialog -->
        <Dialog v-model:open="showImpersonateDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Impersonar Usuário</DialogTitle>
                    <DialogDescription>
                        Tem certeza que deseja entrar como {{ selectedUserName }}?
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" @click="showImpersonateDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="confirmImpersonate">Confirmar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Deactivate Confirmation Dialog -->
        <Dialog v-model:open="showDeactivateDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Desativar Usuário</DialogTitle>
                    <DialogDescription>
                        Tem certeza que deseja desativar {{ selectedUserName }}?
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" @click="showDeactivateDialog = false">
                        Cancelar
                    </Button>
                    <Button variant="destructive" @click="confirmDeactivate">Desativar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
