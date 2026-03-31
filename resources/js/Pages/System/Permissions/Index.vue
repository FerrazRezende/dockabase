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
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';

interface Permission {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    permissions: {
        data: Permission[];
    };
}

const props = defineProps<Props>();

const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedPermission = ref<Permission | null>(null);

const form = ref({
    name: '',
});

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const openCreateDialog = (): void => {
    form.value = { name: '' };
    showCreateDialog.value = true;
};

const createPermission = (): void => {
    router.post(route('system.permissions.store'), form.value, {
        onSuccess: () => {
            showCreateDialog.value = false;
        },
    });
};

const openEditDialog = (permission: Permission): void => {
    selectedPermission.value = permission;
    form.value = { name: permission.name };
    showEditDialog.value = true;
};

const updatePermission = (): void => {
    if (!selectedPermission.value) return;

    router.put(
        route('system.permissions.update', selectedPermission.value.id),
        form.value,
        {
            onSuccess: () => {
                showEditDialog.value = false;
            },
        }
    );
};

const openDeleteDialog = (permission: Permission): void => {
    selectedPermission.value = permission;
    showDeleteDialog.value = true;
};

const deletePermission = (): void => {
    if (!selectedPermission.value) return;

    router.delete(route('system.permissions.destroy', selectedPermission.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
};
</script>

<template>
    <Head title="Permissões" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        Permissões
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Gerencie as permissões do sistema
                    </p>
                </div>
                <Button @click="openCreateDialog">
                    <Plus class="w-4 h-4 mr-2" />
                    Nova Permissão
                </Button>
            </div>
        </template>

        <div class="bg-card shadow-sm rounded-lg border border-border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nome</TableHead>
                        <TableHead>Guard</TableHead>
                        <TableHead>Criado em</TableHead>
                        <TableHead class="w-[120px]">Ações</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="permission in permissions.data"
                        :key="permission.id"
                    >
                        <TableCell class="font-medium">
                            {{ permission.name }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ permission.guard_name }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(permission.created_at) }}
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Editar"
                                    @click="openEditDialog(permission)"
                                >
                                    <Pencil class="w-4 h-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Excluir"
                                    @click="openDeleteDialog(permission)"
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
                    <DialogTitle>Nova Permissão</DialogTitle>
                    <DialogDescription>
                        Crie uma nova permissão. Use o formato "resource.action" (ex: databases.view).
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Nome</label>
                        <Input v-model="form.name" placeholder="ex: databases.view" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showCreateDialog = false">
                        Cancelar
                    </Button>
                    <Button @click="createPermission">Criar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Editar Permissão</DialogTitle>
                    <DialogDescription>
                        Altere o nome da permissão.
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
                    <Button @click="updatePermission">Salvar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Excluir Permissão</DialogTitle>
                    <DialogDescription>
                        Tem certeza que deseja excluir a permissão "{{ selectedPermission?.name }}"?
                        Esta ação não pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancelar
                    </Button>
                    <Button variant="destructive" @click="deletePermission">
                        Excluir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
