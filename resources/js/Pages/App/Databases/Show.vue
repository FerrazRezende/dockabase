<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { __ } from '@/composables/useLang';
import { ref, onMounted, onUnmounted, computed, reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import PvTabs from '@/components/ui/pv-tabs/PvTabs.vue';
import PvTabsContent from '@/components/ui/pv-tabs/PvTabsContent.vue';
import SchemaBrowser from '@/components/schema/SchemaBrowser.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import CreationTimeline from '@/components/CreationTimeline.vue';
import UserAvatarWithStatus from '@/components/user/UserAvatarWithStatus.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Database, DatabaseStatus, CreationStep } from '@/types/database';
import type { Credential } from '@/types/credential';
import { ArrowLeft, Server, Database as DatabaseIcon, Calendar, AlertCircle, Loader2, Plus, Trash2, Key, Mail, Pencil } from 'lucide-vue-next';
import { useEcho } from '@/composables/useEcho';
import { useEchoChannels } from '@/composables/useEchoChannels';
import type { UserStatusChangedEvent, UserStatus } from '@/types/user-status';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';

interface Props {
    database: Database;
    availableCredentials: { id: string; name: string; permission: string }[];
}

const props = defineProps<Props>();

const page = usePage();
const toast = useToast();
const { canEdit } = usePermissions();
const activeFeatures = computed(() => page.props.activeFeatures as string[] | undefined);

const tabs = computed(() => {
    const baseTabs = [
        { value: 'info', label: __('Information'), icon: 'Server' },
    ];

    if (activeFeatures.value?.includes('schema-builder')) {
        baseTabs.push({ value: 'schema', label: __('Schema'), icon: 'Database' });
    }

    return baseTabs;
});

const currentStep = ref<CreationStep | null>(props.database.current_step);
const progress = ref(props.database.progress);
const status = ref<DatabaseStatus>(props.database.status);
const errorMessage = ref(props.database.error_message);
const activeTab = ref('info');

const { subscribeToDatabase } = useEcho();

let channel: ReturnType<typeof subscribeToDatabase> | null = null;

const addCredentialDialogOpen = ref(false);
const selectedCredentialId = ref<string>('');
const attaching = ref(false);

const detaching = ref<string | null>(null);
const credentialToDelete = ref<{ id: string; name: string } | null>(null);
const detachDialogOpen = ref(false);

const userStatuses = reactive<Record<string, UserStatus>>({});
const { connect: connectPresence, listenToPresenceChannel, disconnect: disconnectPresence } = useEchoChannels();

onMounted(async () => {
    const message = page.props.flash?.message as string | undefined;
    const messageType = page.props.flash?.messageType as string | undefined;

    if (message) {
        if (messageType === 'warning') {
            toast.warning(message);
        } else if (messageType === 'success') {
            toast.success(message);
        } else if (messageType === 'error') {
            toast.error(message);
        } else {
            toast.info(message);
        }
    }

    channel = subscribeToDatabase(props.database.id, {
        onStepUpdated: (data) => {
            currentStep.value = data.step;
            progress.value = data.progress;
            status.value = 'processing';
        },
        onDatabaseCreated: () => {
            status.value = 'ready';
            currentStep.value = 'ready';
            progress.value = 100;
            toast.success(__('Database created successfully!'));
        },
        onDatabaseFailed: (data) => {
            status.value = 'failed';
            errorMessage.value = data.error;
            toast.error(__('Error creating database'));
        },
    });

    const userIds = credentials.value.flatMap(c => c.users?.map(u => u.id) || []);
    if (userIds.length > 0) {
        try {
            const { data } = await axios.post(route('api.user.statuses.batch'), { user_ids: [...new Set(userIds)] });
            Object.assign(userStatuses, data.statuses);
        } catch { /* ignore */ }
    }

    try {
        await connectPresence();
        listenToPresenceChannel((event: UserStatusChangedEvent) => {
            userStatuses[event.user_id] = event.status as UserStatus;
        });
    } catch {
        // Non-blocking
    }
});

onUnmounted(() => {
    try { disconnectPresence(); } catch { /* ignore */ }
});

const getStatusBadge = () => {
    switch (status.value) {
        case 'pending':
            return { variant: 'outline', class: 'bg-yellow-500/10 text-yellow-500', label: __('Pending') };
        case 'processing':
            return { variant: 'outline', class: 'bg-blue-500/10 text-blue-500', label: __('Processing') };
        case 'ready':
            return { variant: 'default', class: 'badge-success', label: __('Ready') };
        case 'failed':
            return { variant: 'destructive', class: '', label: __('Failed') };
        default:
            return { variant: 'outline', class: '', label: status.value };
    }
};

const openAddCredentialDialog = () => {
    selectedCredentialId.value = '';
    addCredentialDialogOpen.value = true;
};

const attachCredential = async () => {
    if (!selectedCredentialId.value) return;

    attaching.value = true;
    router.post(
        route('app.databases.credentials.attach', props.database.id),
        { credential_id: selectedCredentialId.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                addCredentialDialogOpen.value = false;
                selectedCredentialId.value = '';
                toast.success(__('Credential added successfully!'));
            },
            onError: () => {
                toast.error(__('Error adding credential'));
            },
            onFinish: () => {
                attaching.value = false;
            },
        }
    );
};

const openDeleteCredentialDialog = (credential: { id: string; name: string }) => {
    credentialToDelete.value = credential;
    detachDialogOpen.value = true;
};

const confirmDetachCredential = () => {
    if (!credentialToDelete.value) return;

    detaching.value = credentialToDelete.value.id;
    router.delete(
        route('app.databases.credentials.detach', [props.database.id, credentialToDelete.value.id]),
        {
            preserveScroll: true,
            onSuccess: () => {
                detachDialogOpen.value = false;
                credentialToDelete.value = null;
                toast.success(__('Credential removed successfully!'));
            },
            onError: () => {
                toast.error(__('Error removing credential'));
            },
            onFinish: () => {
                detaching.value = null;
            },
        }
    );
};

const getPermissionBadgeClass = (permission: string): string => {
    if (permission === 'read-write') return 'badge-success';
    if (permission === 'write') return 'bg-blue-500/10 text-blue-500';
    return 'bg-gray-500/10 text-gray-500';
};

const credentials = computed(() => props.database.credentials || []);

const editDialogOpen = ref(false);
const editForm = ref({
    display_name: '',
    description: '',
    host: '',
    port: '',
    is_active: false,
});
const saving = ref(false);

const openEditDialog = () => {
    editForm.value = {
        display_name: props.database.display_name || '',
        description: props.database.description || '',
        host: props.database.host || '',
        port: String(props.database.port || ''),
        is_active: props.database.is_active ?? true,
    };
    editDialogOpen.value = true;
};

const saveDatabase = () => {
    saving.value = true;
    const data: Record<string, unknown> = { ...editForm.value };
    data.port = data.port ? Number(data.port) : undefined;
    router.patch(
        route('app.databases.update', props.database.id),
        data,
        {
            preserveScroll: true,
            onSuccess: () => {
                editDialogOpen.value = false;
                toast.success(__('Database updated successfully'));
            },
            onError: () => {
                toast.error(__('Error updating database'));
            },
            onFinish: () => {
                saving.value = false;
            },
        }
    );
};
</script>

<template>
    <Head :title="__('Database: :name', { name: database.name })" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('app.databases.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground flex items-center gap-2">
                        <DatabaseIcon class="h-6 w-6 text-muted-foreground" />
                        {{ database.display_name || database.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ __('Database details') }}
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-full overflow-hidden">
            <PvTabs v-model="activeTab" :tabs="tabs">
                <!-- Info Tab -->
                <PvTabsContent value="info" :active-tab="activeTab">
                    <div class="space-y-6">
                        <!-- Timeline Card (pending/processing) -->
                        <Card v-if="status === 'pending' || status === 'processing'">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Loader2 v-if="status === 'processing'" class="h-5 w-5 animate-spin text-primary" />
                                    {{ __('Database Creation') }}
                                </CardTitle>
                                <CardDescription>
                                    {{ __('Monitor the creation progress') }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <CreationTimeline
                                    :current-step="currentStep"
                                    :progress="progress"
                                    :status="status"
                                />
                            </CardContent>
                        </Card>

                        <!-- Error Alert -->
                        <Alert v-if="status === 'failed'" variant="destructive">
                            <AlertCircle class="h-4 w-4" />
                            <AlertTitle>{{ __('Creation error') }}</AlertTitle>
                            <AlertDescription>
                                {{ errorMessage }}
                            </AlertDescription>
                        </Alert>

                        <!-- Info + Connection Cards -->
                        <div class="grid gap-6 md:grid-cols-2">
                            <Card>
                                <CardHeader class="pb-4">
                                    <CardTitle class="flex items-center justify-between text-base">
                                        {{ __('Information') }}
                                        <Button v-if="canEdit('databases')" variant="outline" size="sm" @click="openEditDialog">
                                            <Pencil class="h-4 w-4 mr-2" />
                                            {{ __('Edit') }}
                                        </Button>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-sm text-muted-foreground">{{ __('Name') }}</span>
                                        <span class="text-sm font-medium">{{ database.name }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-sm text-muted-foreground">{{ __('Database Name') }}</span>
                                        <span class="text-sm font-medium font-mono bg-muted px-2 py-0.5 rounded">{{ database.database_name }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-sm text-muted-foreground">{{ __('Status') }}</span>
                                        <Badge
                                            :variant="getStatusBadge().variant"
                                            :class="getStatusBadge().class"
                                        >
                                            {{ getStatusBadge().label }}
                                        </Badge>
                                    </div>
                                    <div v-if="database.description" class="pt-3 mt-1 border-t">
                                        <span class="text-sm text-muted-foreground">{{ __('Description') }}</span>
                                        <p class="mt-1 text-sm">{{ database.description }}</p>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader class="pb-4">
                                    <CardTitle class="text-base">{{ __('Connection') }}</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-sm text-muted-foreground flex items-center gap-2">
                                            <Server class="h-3.5 w-3.5" />
                                            {{ __('Host') }}
                                        </span>
                                        <span class="text-sm font-medium font-mono bg-muted px-2 py-0.5 rounded">{{ database.host }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-sm text-muted-foreground">{{ __('Port') }}</span>
                                        <span class="text-sm font-medium">{{ database.port }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-sm text-muted-foreground flex items-center gap-2">
                                            <Calendar class="h-3.5 w-3.5" />
                                            {{ __('Created at') }}
                                        </span>
                                        <span class="text-sm">{{ new Date(database.created_at).toLocaleString(page.props.locale || 'pt-BR') }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-sm text-muted-foreground flex items-center gap-2">
                                            <Calendar class="h-3.5 w-3.5" />
                                            {{ __('Updated at') }}
                                        </span>
                                        <span class="text-sm">{{ new Date(database.updated_at).toLocaleString(page.props.locale || 'pt-BR') }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Credentials Card -->
                        <Card>
                            <CardHeader class="pb-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle class="flex items-center gap-2 text-base">
                                            <Key class="h-4 w-4" />
                                            {{ __('Credentials') }}
                                        </CardTitle>
                                    </div>
                                    <Button v-if="canEdit('databases')" size="sm" @click="openAddCredentialDialog" :disabled="availableCredentials.length === 0">
                                        <Plus class="h-4 w-4 mr-2" />
                                        {{ __('Add') }}
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div v-if="credentials.length > 0" class="space-y-4">
                                    <div
                                        v-for="credential in credentials"
                                        :key="credential.id"
                                        class="rounded-lg border bg-card"
                                    >
                                        <div class="flex items-center justify-between px-4 py-3 border-b">
                                            <div class="flex items-center gap-2">
                                                <Key class="h-4 w-4 text-muted-foreground" />
                                                <span class="font-medium text-sm">{{ credential.name }}</span>
                                                <Badge :class="getPermissionBadgeClass(credential.permission)" class="ml-1">
                                                    {{ credential.permission_label || credential.permission }}
                                                </Badge>
                                            </div>
                                            <Button
                                                v-if="canEdit('databases')"
                                                variant="ghost"
                                                size="icon"
                                                class="text-destructive hover:text-destructive h-8 w-8"
                                                @click="openDeleteCredentialDialog(credential)"
                                                :disabled="detaching === credential.id"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                        <Table v-if="credential.users && credential.users.length > 0">
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>{{ __('User') }}</TableHead>
                                                    <TableHead>{{ __('Email') }}</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow v-for="user in credential.users" :key="user.id">
                                                    <TableCell>
                                                        <div class="flex items-center gap-3">
                                                            <UserAvatarWithStatus
                                                                :user-id="user.id"
                                                                :user-name="user.name"
                                                                :avatar-url="user.avatar"
                                                                :status="userStatuses[user.id] || 'offline'"
                                                                size="sm"
                                                            />
                                                            <span class="font-medium text-sm">{{ user.name }}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span class="flex items-center gap-1 text-sm text-muted-foreground">
                                                            <Mail class="h-3 w-3" />
                                                            {{ user.email }}
                                                        </span>
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                        <p v-else class="text-sm text-muted-foreground text-center py-3">
                                            {{ __('No users in this credential') }}
                                        </p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground text-center py-8">
                                    {{ __('No credentials linked') }}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </PvTabsContent>

                <!-- Schema Tab -->
                <PvTabsContent value="schema" :active-tab="activeTab" v-if="activeFeatures?.includes('schema-builder')">
                    <div class="overflow-hidden">
                        <SchemaBrowser :database-id="database.id" />
                    </div>
                </PvTabsContent>
            </PvTabs>
        </div>

        <!-- Add Credential Dialog -->
        <Dialog v-if="canEdit('databases')" v-model:open="addCredentialDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ __('Add Credential') }}</DialogTitle>
                    <DialogDescription>
                        {{ __('Select a credential to add to this database.') }}
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <Select v-model="selectedCredentialId">
                        <SelectTrigger>
                            <SelectValue :placeholder="__('Select a credential')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="credential in availableCredentials"
                                :key="credential.id"
                                :value="credential.id"
                            >
                                {{ credential.name }} ({{ credential.permission }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="availableCredentials.length === 0" class="text-sm text-muted-foreground mt-2">
                        {{ __('All credentials are already linked or no credentials available.') }}
                    </p>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="addCredentialDialogOpen = false">
                        {{ __('Cancel') }}
                    </Button>
                    <Button @click="attachCredential" :disabled="!selectedCredentialId || attaching">
                        <span v-if="attaching">{{ __('Adding...') }}</span>
                        <span v-else>{{ __('Add') }}</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Detach Credential Dialog -->
        <ConfirmDialog
            v-model:open="detachDialogOpen"
            :title="__('Remove Credential')"
            :description="__('Are you sure you want to remove the credential \':name\' from this database?', { name: credentialToDelete?.name })"
            :confirm-text="__('Remove')"
            :loading="detaching === credentialToDelete?.id"
            variant="danger"
            @confirm="confirmDetachCredential"
        />

        <!-- Edit Database Dialog -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ __('Edit Database') }}</DialogTitle>
                    <DialogDescription>{{ __('Update database fields') }}</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="edit-display-name">{{ __('Display Name') }}</Label>
                        <Input id="edit-display-name" v-model="editForm.display_name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit-description">{{ __('Description') }}</Label>
                        <Textarea id="edit-description" v-model="editForm.description" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="edit-host">{{ __('Host') }}</Label>
                            <Input id="edit-host" v-model="editForm.host" />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit-port">{{ __('Port') }}</Label>
                            <Input id="edit-port" v-model="editForm.port" type="number" />
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Switch id="edit-active" v-model:model-value="editForm.is_active" />
                        <Label for="edit-active">{{ __('Active') }}</Label>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="editDialogOpen = false">
                        {{ __('Cancel') }}
                    </Button>
                    <Button @click="saveDatabase" :disabled="saving">
                        <Loader2 v-if="saving" class="h-4 w-4 mr-2 animate-spin" />
                        <span v-if="saving">{{ __('Saving...') }}</span>
                        <span v-else>{{ __('Save') }}</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
