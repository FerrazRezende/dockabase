<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CredentialCollection } from '@/types/credential';
import { MoreHorizontal, Plus, Key, Users, Database, Eye, Trash2 } from 'lucide-vue-next';

defineProps<{
    credentials: CredentialCollection;
}>();

const deleting = ref<string | null>(null);

const getCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    return meta?.content || '';
};

const deleteCredential = async (credentialId: string): Promise<void> => {
    if (!confirm('Tem certeza que deseja excluir esta credencial?')) return;

    deleting.value = credentialId;
    try {
        const response = await fetch(route('app.credentials.destroy', credentialId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        router.reload({ only: ['credentials'] });
    } catch (error) {
        console.error('Failed to delete credential:', error);
    } finally {
        deleting.value = null;
    }
};

const getPermissionBadgeVariant = (permission: string): 'default' | 'secondary' | 'outline' => {
    if (permission === 'read-write') return 'default';
    if (permission === 'read') return 'outline';
    return 'secondary';
};

const getPermissionBadgeClass = (permission: string): string => {
    if (permission === 'read-write') return 'bg-green-500/10 text-green-500 hover:bg-green-500/20';
    if (permission === 'write') return 'bg-blue-500/10 text-blue-500 hover:bg-blue-500/20';
    return '';
};
</script>

<template>
    <Head title="Credentials" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-2xl font-semibold text-foreground">
                Credentials
            </h2>
            <p class="text-sm text-muted-foreground mt-1">
                Gerencie as credenciais de acesso aos databases
            </p>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <Link :href="route('app.credentials.create')">
                    <Button>
                        <Plus class="h-4 w-4 mr-2" />
                        Nova Credencial
                    </Button>
                </Link>
            </div>

            <div class="bg-card shadow-sm rounded-lg border border-border">
                <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[250px]">Nome</TableHead>
                        <TableHead class="w-[150px]">Permissão</TableHead>
                        <TableHead class="w-[100px]">Usuários</TableHead>
                        <TableHead class="w-[100px]">Databases</TableHead>
                        <TableHead class="w-[80px]"></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="credential in credentials.data"
                        :key="credential.id"
                    >
                        <TableCell class="font-medium">
                            <Link
                                :href="route('app.credentials.show', credential.id)"
                                class="hover:underline flex items-center gap-2"
                            >
                                <Key class="h-4 w-4 text-muted-foreground" />
                                {{ credential.name }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="getPermissionBadgeVariant(credential.permission)"
                                :class="getPermissionBadgeClass(credential.permission)"
                            >
                                {{ credential.permission_label }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <span class="flex items-center gap-1">
                                <Users class="h-3 w-3 text-muted-foreground" />
                                {{ credential.users_count ?? 0 }}
                            </span>
                        </TableCell>
                        <TableCell>
                            <span class="flex items-center gap-1">
                                <Database class="h-3 w-3 text-muted-foreground" />
                                {{ credential.databases_count ?? 0 }}
                            </span>
                        </TableCell>
                        <TableCell>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link
                                            :href="route('app.credentials.show', credential.id)"
                                            class="flex items-center w-full"
                                        >
                                            <Eye class="mr-2 h-4 w-4" />
                                            Visualizar
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="deleteCredential(credential.id)"
                                        :disabled="deleting === credential.id"
                                        class="text-destructive"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Excluir
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="credentials.data.length === 0">
                        <TableCell colspan="5" class="text-center text-muted-foreground py-8">
                            Nenhuma credencial cadastrada
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
