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
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import type { DatabaseCollection } from '@/types/database';
import { MoreHorizontal, Plus, Database, Server, Eye, Trash2 } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { usePermissions } from '@/composables/usePermissions';

defineProps<{
    databases: DatabaseCollection;
}>();

const { canCreate, canDelete } = usePermissions();
const toast = useToast();
const deleting = ref<string | null>(null);
const deleteDialogOpen = ref(false);
const databaseToDelete = ref<{ id: string; name: string } | null>(null);

const openDeleteDialog = (database: { id: string; name: string }) => {
    databaseToDelete.value = database;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!databaseToDelete.value) return;

    deleting.value = databaseToDelete.value.id;
    router.delete(route('app.databases.destroy', databaseToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(__('Database deleted successfully!'));
            deleteDialogOpen.value = false;
            databaseToDelete.value = null;
        },
        onError: (errors) => {
            toast.error(__('Error deleting database'));
        },
        onFinish: () => {
            deleting.value = null;
        },
    });
};
</script>

<template>
    <Head :title="__('Databases')" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-2xl font-semibold text-foreground">
                {{ __('Databases') }}
            </h2>
            <p class="text-sm text-muted-foreground mt-1">
                {{ __('Manage your PostgreSQL databases') }}
            </p>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <Link v-if="canCreate('databases')" :href="route('app.databases.create')">
                    <Button>
                        <Plus class="h-4 w-4 mr-2" />
                        {{ __('New Database') }}
                    </Button>
                </Link>
            </div>

            <div class="bg-card shadow-sm rounded-lg border border-border">
                <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[200px]">{{ __('Name') }}</TableHead>
                        <TableHead>{{ __('Display Name') }}</TableHead>
                        <TableHead class="w-[150px]">{{ __('Host') }}</TableHead>
                        <TableHead class="w-[100px]">{{ __('Port') }}</TableHead>
                        <TableHead class="w-[100px]">{{ __('Status') }}</TableHead>
                        <TableHead class="w-[100px]">{{ __('Credentials') }}</TableHead>
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
                                {{ database.is_active ? __('Active') : __('Inactive') }}
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
                                            {{ __('View') }}
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="canDelete('databases')"
                                        @click="openDeleteDialog(database)"
                                        :disabled="deleting === database.id"
                                        class="text-destructive focus:text-destructive"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        {{ __('Exclude') }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="databases.data.length === 0">
                        <TableCell colspan="7" class="text-center text-muted-foreground py-8">
                            {{ __('No database registered') }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <ConfirmDialog
            v-model:open="deleteDialogOpen"
            :title="__('Exclude Database')"
            :description="__('This action cannot be undone. This will permanently delete the database \':name\' and all associated data.', { name: databaseToDelete?.name })"
            :confirm-text="__('Exclude Database')"
            :confirm-name="databaseToDelete?.name"
            :loading="deleting === databaseToDelete?.id"
            variant="danger"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
