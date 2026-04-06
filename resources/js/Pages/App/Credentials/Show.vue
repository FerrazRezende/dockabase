<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Credential, CredentialPermission } from '@/types/credential';
import type { User } from '@/types/user';
import { ArrowLeft, Key, Shield, Users, Database, Calendar, Mail, Plus, Trash2, UserPlus } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const props = defineProps<{
    credential: Credential;
}>();

const toast = useToast();
const isAddUserDialogOpen = ref(false);
const selectedUserId = ref<string>('');
const availableUsers = ref<User[]>([]);
const loadingUsers = ref(false);
const attaching = ref(false);
const detaching = ref<number | null>(null);

const getPermissionBadgeVariant = (permission: CredentialPermission): 'default' | 'secondary' | 'outline' => {
    if (permission === 'read-write') return 'default';
    if (permission === 'read') return 'outline';
    return 'secondary';
};

const getPermissionBadgeClass = (permission: CredentialPermission): string => {
    if (permission === 'read-write') return 'bg-green-500/10 text-green-500';
    if (permission === 'write') return 'bg-blue-500/10 text-blue-500';
    return '';
};

const fetchAvailableUsers = async () => {
    loadingUsers.value = true;
    try {
        const response = await fetch(route('users.index'), {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();
        // Filter out users already attached
        const attachedIds = props.credential.users?.map(u => u.id) || [];
        availableUsers.value = (data.data || []).filter((u: User) => !attachedIds.includes(u.id));
    } catch (error) {
        toast.error(__('Error loading users'));
    } finally {
        loadingUsers.value = false;
    }
};

const attachUser = async () => {
    if (!selectedUserId.value) return;

    attaching.value = true;
    try {
        await router.post(
            route('app.credentials.users.attach', props.credential.id),
            { user_id: selectedUserId.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isAddUserDialogOpen.value = false;
                    selectedUserId.value = '';
                    toast.success(__('User added successfully!'));
                },
                onError: (errors) => {
                    toast.error(__('Error adding user'));
                },
            }
        );
    } catch (error) {
        toast.error(__('Error adding user'));
    } finally {
        attaching.value = false;
    }
};

const detachUser = async (userId: number) => {
    if (!confirm(__('Remove this user from the credential?'))) return;

    detaching.value = userId;
    try {
        await router.delete(
            route('app.credentials.users.detach', [props.credential.id, userId]),
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(__('User removed successfully!'));
                },
                onError: () => {
                    toast.error(__('Error removing user'));
                },
            }
        );
    } catch (error) {
        toast.error(__('Error removing user'));
    } finally {
        detaching.value = null;
    }
};

const openAddUserDialog = () => {
    fetchAvailableUsers();
    isAddUserDialogOpen.value = true;
};
</script>

<template>
    <Head :title="__('Credential: :name', { name: credential.name })" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('app.credentials.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground flex items-center gap-2">
                        <Key class="h-6 w-6 text-muted-foreground" />
                        {{ credential.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ __('Credential details') }}
                    </p>
                </div>
            </div>
        </template>

        <div class="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>{{ __('Information') }}</CardTitle>
                    <CardDescription>{{ __('Credential details') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">{{ __('Name') }}</span>
                        <span class="font-medium">{{ credential.name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Shield class="h-4 w-4" />
                            {{ __('Permission') }}
                        </span>
                        <Badge
                            :variant="getPermissionBadgeVariant(credential.permission)"
                            :class="getPermissionBadgeClass(credential.permission)"
                        >
                            {{ credential.permission_label }}
                        </Badge>
                    </div>
                    <div v-if="credential.description" class="pt-2 border-t">
                        <span class="text-muted-foreground text-sm">{{ __('Description') }}</span>
                        <p class="mt-1">{{ credential.description }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ __('Statistics') }}</CardTitle>
                    <CardDescription>{{ __('Usage summary') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Users class="h-4 w-4" />
                            {{ __('Users') }}
                        </span>
                        <Badge variant="secondary">{{ credential.users_count ?? credential.users?.length ?? 0 }}</Badge>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Database class="h-4 w-4" />
                            {{ __('Databases') }}
                        </span>
                        <Badge variant="secondary">{{ credential.databases_count ?? credential.databases?.length ?? 0 }}</Badge>
                    </div>
                </CardContent>
            </Card>

            <Card class="md:col-span-2">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>{{ __('Users') }}</CardTitle>
                            <CardDescription>{{ __('Users with this credential') }}</CardDescription>
                        </div>
                        <Dialog v-model:open="isAddUserDialogOpen">
                            <DialogTrigger as-child>
                                <Button size="sm" @click="openAddUserDialog">
                                    <UserPlus class="h-4 w-4 mr-2" />
                                    {{ __('Add User') }}
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>{{ __('Add User') }}</DialogTitle>
                                    <DialogDescription>
                                        {{ __('Select a user to add to this credential.') }}
                                    </DialogDescription>
                                </DialogHeader>
                                <div class="py-4">
                                    <Select v-model="selectedUserId">
                                        <SelectTrigger>
                                            <SelectValue :placeholder="__('Select a user')" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="user in availableUsers"
                                                :key="user.id"
                                                :value="String(user.id)"
                                            >
                                                {{ user.name }} ({{ user.email }})
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="availableUsers.length === 0 && !loadingUsers" class="text-sm text-muted-foreground mt-2">
                                        {{ __('All users are already linked or no users available.') }}
                                    </p>
                                </div>
                                <DialogFooter>
                                    <Button variant="outline" @click="isAddUserDialogOpen = false">
                                        {{ __('Cancel') }}
                                    </Button>
                                    <Button
                                        @click="attachUser"
                                        :disabled="!selectedUserId || attaching"
                                    >
                                        <span v-if="attaching">{{ __('Adding...') }}</span>
                                        <span v-else>{{ __('Add') }}</span>
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table v-if="credential.users && credential.users.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ __('Name') }}</TableHead>
                                <TableHead>{{ __('Email') }}</TableHead>
                                <TableHead class="w-[80px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in credential.users" :key="user.id">
                                <TableCell class="font-medium">{{ user.name }}</TableCell>
                                <TableCell>
                                    <span class="flex items-center gap-1">
                                        <Mail class="h-3 w-3 text-muted-foreground" />
                                        {{ user.email }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="detachUser(user.id)"
                                        :disabled="detaching === user.id"
                                        class="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <p v-else class="text-muted-foreground text-center py-4">
                        {{ __('No user linked') }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ __('Metadata') }}</CardTitle>
                    <CardDescription>{{ __('Creation information') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Calendar class="h-4 w-4" />
                            {{ __('Created at') }}
                        </span>
                        <span class="text-sm">{{ new Date(credential.created_at).toLocaleString('pt-BR') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Calendar class="h-4 w-4" />
                            {{ __('Updated at') }}
                        </span>
                        <span class="text-sm">{{ new Date(credential.updated_at).toLocaleString('pt-BR') }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
