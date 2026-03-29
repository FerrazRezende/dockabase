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
import type { DatabaseCollection } from '@/types/database';
import { MoreHorizontal, Plus, Database, Server, Eye, Trash2 } from 'lucide-vue-next';

defineProps<{
    databases: DatabaseCollection;
}>();

const deleting = ref<string | null>(null);

const getCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    return meta?.content || '';
};

const deleteDatabase = async (databaseId: string): Promise<void> => {
    if (!confirm('Tem certeza que deseja excluir este database?')) return;

    deleting.value = databaseId;
    try {
        const response = await fetch(route('app.databases.destroy', databaseId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        router.reload({ only: ['databases'] });
    } catch (error) {
        console.error('Failed to delete database:', error);
    } finally {
        deleting.value = null;
    }
};
</script>

<template>
    <Head title="Databases" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-2xl font-semibold text-foreground">
                Databases
            </h2>
            <p class="text-sm text-muted-foreground mt-1">
                Gerencie os databases PostgreSQL da sua instância
            </p>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <Link :href="route('app.databases.create')">
                    <Button>
                        <Plus class="h-4 w-4 mr-2" />
                        Novo Database
                    </Button>
                </Link>
            </div>

            <div class="bg-card shadow-sm rounded-lg border border-border">
                <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[200px]">Nome</TableHead>
                        <TableHead>Display Name</TableHead>
                        <TableHead class="w-[150px]">Host</TableHead>
                        <TableHead class="w-[100px]">Port</TableHead>
                        <TableHead class="w-[100px]">Status</TableHead>
                        <TableHead class="w-[100px]">Credentials</TableHead>
                        <TableHead class="w-[80px]"></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="database in databases.data"
                        :key="database.id"
                    >
                        <TableCell class="font-medium">
                            <Link
                                :href="route('app.databases.show', database.id)"
                                class="hover:underline flex items-center gap-2"
                            >
                                <Database class="h-4 w-4 text-muted-foreground" />
                                {{ database.name }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ database.display_name || '-' }}
                        </TableCell>
                        <TableCell>
                            <span class="flex items-center gap-1">
                                <Server class="h-3 w-3 text-muted-foreground" />
                                {{ database.host }}
                            </span>
                        </TableCell>
                        <TableCell>{{ database.port }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="database.is_active ? 'default' : 'outline'"
                                :class="database.is_active ? 'bg-green-500/10 text-green-500 hover:bg-green-500/20' : ''"
                            >
                                {{ database.is_active ? 'Ativo' : 'Inativo' }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <Badge variant="secondary">
                                {{ database.credentials_count ?? 0 }}
                            </Badge>
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
                                            :href="route('app.databases.show', database.id)"
                                            class="flex items-center w-full"
                                        >
                                            <Eye class="mr-2 h-4 w-4" />
                                            Visualizar
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="deleteDatabase(database.id)"
                                        :disabled="deleting === database.id"
                                        class="text-destructive"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Excluir
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="databases.data.length === 0">
                        <TableCell colspan="7" class="text-center text-muted-foreground py-8">
                            Nenhum database cadastrado
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
