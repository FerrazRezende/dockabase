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
import type { CredentialCollection } from '@/types/credential';
import { MoreHorizontal, Plus, Key, Eye, Trash2 } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { usePermissions } from '@/composables/usePermissions';

defineProps<{
    credentials: CredentialCollection;
}>();

const { canCreate, canDelete } = usePermissions();
const toast = useToast();
const deleting = ref<string | null>(null);
const deleteDialogOpen = ref(false);
const credentialToDelete = ref<{ id: string; name: string } | null>(null);

const openDeleteDialog = (credential: { id: string; name: string }) => {
    credentialToDelete.value = credential;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!credentialToDelete.value) return;

    deleting.value = credentialToDelete.value.id;
    router.delete(route('app.credentials.destroy', credentialToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(__('Credential deleted successfully'));
            deleteDialogOpen.value = false;
            credentialToDelete.value = null;
        },
        onError: (errors) => {
            toast.error(__('Error deleting credential'));
        },
        onFinish: () => {
            deleting.value = null;
        },
    });
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
    <Head :title="__('Credentials')" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-2xl font-semibold text-foreground">
                {{ __('Credentials') }}
            </h2>
            <p class="text-sm text-muted-foreground mt-1">
                {{ __('Manage database access credentials') }}
            </p>
        </template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <Link v-if="canCreate('credentials')" :href="route('app.credentials.create')">
                    <Button>
                        <Plus class="h-4 w-4 mr-2" />
                        {{ __('New Credential') }}
                    </Button>
                </Link>
            </div>

            <div class="bg-card shadow-sm rounded-lg border border-border">
                <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[250px]">{{ __('Name') }}</TableHead>
                        <TableHead class="w-[150px]">{{ __('Permission') }}</TableHead>
                        <TableHead class="w-[100px]">{{ __('Users') }}</TableHead>
                        <TableHead class="w-[100px]">{{ __('Databases') }}</TableHead>
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
                            <span class="text-sm">
                                {{ credential.users_count ?? 0 }}
                            </span>
                        </TableCell>
                        <TableCell>
                            <span class="text-sm">
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
                                            {{ __('View') }}
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="canDelete('credentials')"
                                        @click="openDeleteDialog(credential)"
                                        :disabled="deleting === credential.id"
                                        class="text-destructive focus:text-destructive"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        {{ __('Delete') }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="credentials.data.length === 0">
                        <TableCell colspan="5" class="text-center text-muted-foreground py-8">
                            {{ __('No credential registered') }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <ConfirmDialog
            v-model:open="deleteDialogOpen"
            :title="__('Delete Credential')"
            :description="__('This action cannot be undone. This will permanently delete the credential \':name\'.', { name: credentialToDelete?.name })"
            :confirm-text="__('Delete Credential')"
            :confirm-name="credentialToDelete?.name"
            :loading="deleting === credentialToDelete?.id"
            variant="danger"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
