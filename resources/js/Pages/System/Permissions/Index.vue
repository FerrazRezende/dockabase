<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { __ } from '@/composables/useLang';
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
import { Plus, Pencil, Trash2, Search, Info } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

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
    permissions?: { id: number; name: string }[];
    users_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    permissions: Permission[];
    roles: Role[];
}

const props = defineProps<Props>();

const toast = useToast();

const searchRoles = ref('');

// Dialog - Delete Role
const showDeleteRoleDialog = ref(false);
const selectedRole = ref<Role | null>(null);

const allPermissions = computed(() => props.permissions ?? []);

const filteredRoles = computed(() => {
    const roles = props.roles ?? [];
    if (!searchRoles.value) return roles;
    return roles.filter((role) =>
        role.name.toLowerCase().includes(searchRoles.value.toLowerCase())
    );
});

const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};

    for (const permission of allPermissions.value) {
        if (!permission.name) continue; // Skip if name is undefined
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
        databases: __('Databases'),
        schemas: 'Schemas',
        credentials: __('Credentials'),
        tables: 'Tables',
        users: __('Users'),
    };
    return labels[category] || category.charAt(0).toUpperCase() + category.slice(1);
};

// Delete Role
const openDeleteRoleDialog = (role: Role): void => {
    selectedRole.value = role;
    showDeleteRoleDialog.value = true;
};

const deleteRole = (): void => {
    if (!selectedRole.value) return;
    router.delete(route('system.roles.destroy', selectedRole.value.id), {
        onSuccess: () => {
            showDeleteRoleDialog.value = false;
            toast.success(__('Role deleted successfully'));
        },
        onError: () => {
            toast.error(__('Error deleting role. Please try again.'));
        },
    });
};

const pageTitle = computed(() => __('Permissions'));
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-2xl font-semibold text-foreground">
                {{ __('Permissions') }}
            </h2>
            <p class="text-sm text-muted-foreground mt-1">
                {{ __('Manage system roles and permissions') }}
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
                            :placeholder="__('Search roles...')"
                            class="pl-9"
                        />
                    </div>
                    <Link :href="route('system.roles.create')">
                        <Button>
                            <Plus class="w-4 h-4 mr-2" />
                            {{ __('New Role') }}
                        </Button>
                    </Link>
                </div>

                <div class="bg-card shadow-sm rounded-lg border border-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ __('Name') }}</TableHead>
                                <TableHead>{{ __('Permissions') }}</TableHead>
                                <TableHead class="w-[100px]">{{ __('Users') }}</TableHead>
                                <TableHead class="w-[150px]">{{ __('Actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="role in filteredRoles"
                                :key="role.id"
                            >
                                <TableCell class="font-medium">
                                    <Link :href="route('system.roles.edit', role.id)" class="hover:underline">
                                        {{ role.name }}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1 max-w-md">
                                        <Badge
                                            v-for="permission in (role.permissions ?? [])"
                                            :key="permission.id"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            {{ permission.name }}
                                        </Badge>
                                        <span
                                            v-if="(role.permissions ?? []).length === 0"
                                            class="text-muted-foreground text-sm"
                                        >
                                            {{ __('No permissions') }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ role.users_count }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1">
                                        <Link :href="route('system.roles.edit', role.id)">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                :title="__('Edit')"
                                            >
                                                <Pencil class="w-4 h-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            :title="__('Delete')"
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
                                    {{ __('No role found') }}
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
                            {{ __('Available Permissions') }}
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            {{ __('These permissions are predefined and cannot be modified.') }}
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
                        {{ __('No permission available') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Role Dialog -->
        <Dialog v-model:open="showDeleteRoleDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ __('Delete Role') }}</DialogTitle>
                    <DialogDescription>
                        {{ __('Are you sure you want to delete the role ":name"?', { name: selectedRole?.name }) }}
                        {{ __('This action cannot be undone.') }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteRoleDialog = false">{{ __('Cancel') }}</Button>
                    <Button variant="destructive" @click="deleteRole">{{ __('Delete') }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
