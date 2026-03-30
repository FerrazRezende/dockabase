<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import CreationTimeline from '@/components/CreationTimeline.vue';
import type { Database, DatabaseStatus, CreationStep } from '@/types/database';
import type { Credential } from '@/types/credential';
import { ArrowLeft, Server, Database as DatabaseIcon, Calendar, Link2, AlertCircle, CheckCircle2, Loader2, Plus, Trash2, Key } from 'lucide-vue-next';
import { useEcho } from '@/composables/useEcho';
import { useToast } from 'vue-toastification';
import { usePage } from '@inertiajs/vue3';

interface Props {
    database: Database;
    availableCredentials: { id: string; name: string; permission: string }[];
}

const props = defineProps<Props>();

const page = usePage();
const toast = useToast();

const currentStep = ref<CreationStep | null>(props.database.current_step);
const progress = ref(props.database.progress);
const status = ref<DatabaseStatus>(props.database.status);
const errorMessage = ref(props.database.error_message);

const { subscribeToDatabase } = useEcho();

let channel: ReturnType<typeof subscribeToDatabase> | null = null;

// Add credential dialog
const addCredentialDialogOpen = ref(false);
const selectedCredentialId = ref<string>('');
const attaching = ref(false);

// Detach credential
const detaching = ref<string | null>(null);
const credentialToDelete = ref<{ id: string; name: string } | null>(null);
const deleteDialogOpen = ref(false);

// Flash message on mount
onMounted(() => {
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

    // Subscribe to database updates
    channel = subscribeToDatabase(props.database.id, {
        onStepUpdated: (data) => {
            currentStep.value = data.step;
            progress.value = data.progress;
            status.value = 'processing';
        },
        onDatabaseCreated: (data) => {
            status.value = 'ready';
            currentStep.value = 'ready';
            progress.value = 100;
            toast.success('Database criado com sucesso!');
        },
        onDatabaseFailed: (data) => {
            status.value = 'failed';
            errorMessage.value = data.error;
            toast.error('Erro na criação do database');
        },
    });
});

onUnmounted(() => {
    // Channel cleanup is handled by useEcho
});

const getStatusBadge = () => {
    switch (status.value) {
        case 'pending':
            return { variant: 'outline', class: 'bg-yellow-500/10 text-yellow-500', label: 'Pendente' };
        case 'processing':
            return { variant: 'outline', class: 'bg-blue-500/10 text-blue-500', label: 'Processando' };
        case 'ready':
            return { variant: 'default', class: 'bg-green-500/10 text-green-500', label: 'Pronto' };
        case 'failed':
            return { variant: 'destructive', class: '', label: 'Falhou' };
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
                toast.success('Credencial adicionada com sucesso!');
            },
            onError: () => {
                toast.error('Erro ao adicionar credencial');
            },
            onFinish: () => {
                attaching.value = false;
            },
        }
    );
};

const openDeleteCredentialDialog = (credential: { id: string; name: string }) => {
    credentialToDelete.value = credential;
    deleteDialogOpen.value = true;
};

const confirmDetachCredential = () => {
    if (!credentialToDelete.value) return;

    detaching.value = credentialToDelete.value.id;
    router.delete(
        route('app.databases.credentials.detach', [props.database.id, credentialToDelete.value.id]),
        {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialogOpen.value = false;
                credentialToDelete.value = null;
                toast.success('Credencial removida com sucesso!');
            },
            onError: () => {
                toast.error('Erro ao remover credencial');
            },
            onFinish: () => {
                detaching.value = null;
            },
        }
    );
};

const getPermissionBadgeClass = (permission: string): string => {
    if (permission === 'read-write') return 'bg-green-500/10 text-green-500';
    if (permission === 'write') return 'bg-blue-500/10 text-blue-500';
    return 'bg-gray-500/10 text-gray-500';
};

const credentials = computed(() => props.database.credentials || []);
</script>

<template>
    <Head :title="`Database: ${database.name}`" />

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
                        Detalhes do database
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Timeline Card (only for pending/processing databases) -->
            <Card v-if="status === 'pending' || status === 'processing'">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Loader2 v-if="status === 'processing'" class="h-5 w-5 animate-spin text-primary" />
                        Criação do Database
                    </CardTitle>
                    <CardDescription>
                        Acompanhe o progresso da criação
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
                <AlertTitle>Erro na criação</AlertTitle>
                <AlertDescription>
                    {{ errorMessage }}
                </AlertDescription>
            </Alert>

            <!-- Success Alert -->
            <Alert v-if="status === 'ready'" class="border-green-500/50 bg-green-500/10">
                <CheckCircle2 class="h-4 w-4 text-green-500" />
                <AlertTitle class="text-green-500">Database pronto</AlertTitle>
                <AlertDescription>
                    O database está criado e disponível para uso.
                </AlertDescription>
            </Alert>

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Informações</CardTitle>
                        <CardDescription>Detalhes do database</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Nome</span>
                            <span class="font-medium">{{ database.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Database Name</span>
                            <span class="font-medium font-mono text-sm">{{ database.database_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Status</span>
                            <Badge
                                :variant="getStatusBadge().variant"
                                :class="getStatusBadge().class"
                            >
                                {{ getStatusBadge().label }}
                            </Badge>
                        </div>
                        <div v-if="database.description" class="pt-2 border-t">
                            <span class="text-muted-foreground text-sm">Descrição</span>
                            <p class="mt-1">{{ database.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Conexão</CardTitle>
                        <CardDescription>Configurações de conexão</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground flex items-center gap-2">
                                <Server class="h-4 w-4" />
                                Host
                            </span>
                            <span class="font-medium font-mono text-sm">{{ database.host }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Port</span>
                            <span class="font-medium">{{ database.port }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Credentials Card -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Key class="h-5 w-5" />
                                    Credenciais
                                </CardTitle>
                                <CardDescription>Credenciais com acesso a este database</CardDescription>
                            </div>
                            <Button size="sm" @click="openAddCredentialDialog" :disabled="availableCredentials.length === 0">
                                <Plus class="h-4 w-4 mr-2" />
                                Adicionar
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="credentials.length > 0" class="space-y-3">
                            <div
                                v-for="credential in credentials"
                                :key="credential.id"
                                class="flex items-center justify-between p-3 rounded-lg border bg-card"
                            >
                                <div class="flex items-center gap-3">
                                    <Key class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="font-medium">{{ credential.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ credential.users?.length || 0 }} usuário(s)
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge :class="getPermissionBadgeClass(credential.permission)">
                                        {{ credential.permission_label || credential.permission }}
                                    </Badge>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        @click="openDeleteCredentialDialog(credential)"
                                        :disabled="detaching === credential.id"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-muted-foreground text-center py-4">
                            Nenhuma credencial vinculada
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Metadados</CardTitle>
                        <CardDescription>Informações de criação</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                Criado em
                            </span>
                            <span class="text-sm">{{ new Date(database.created_at).toLocaleString('pt-BR') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                Atualizado em
                            </span>
                            <span class="text-sm">{{ new Date(database.updated_at).toLocaleString('pt-BR') }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Add Credential Dialog -->
        <Dialog v-model:open="addCredentialDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Adicionar Credencial</DialogTitle>
                    <DialogDescription>
                        Selecione uma credencial para adicionar a este database.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <Select v-model="selectedCredentialId">
                        <SelectTrigger>
                            <SelectValue placeholder="Selecione uma credencial" />
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
                        Todas as credenciais já estão vinculadas ou não há credenciais disponíveis.
                    </p>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="addCredentialDialogOpen = false">
                        Cancelar
                    </Button>
                    <Button @click="attachCredential" :disabled="!selectedCredentialId || attaching">
                        <span v-if="attaching">Adicionando...</span>
                        <span v-else>Adicionar</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Detach Credential Dialog -->
        <ConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Remover Credencial"
            :description="`Tem certeza que deseja remover a credencial '${credentialToDelete?.name}' deste database?`"
            confirm-text="Remover"
            :loading="detaching === credentialToDelete?.id"
            variant="danger"
            @confirm="confirmDetachCredential"
        />
    </AuthenticatedLayout>
</template>
