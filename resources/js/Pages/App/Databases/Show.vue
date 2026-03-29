<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreationTimeline from '@/components/CreationTimeline.vue';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/composables/useToast';
import type { Database, StepUpdatePayload, DatabaseCreatedPayload, DatabaseFailedPayload, DatabaseStatus, CreationStep } from '@/types/database';
import { ArrowLeft, Server, Database as DatabaseIcon, Calendar, Link2, AlertCircle, CheckCircle2, Loader2 } from 'lucide-vue-next';
import echo from '@/composables/echo';

const props = defineProps<{
    database: Database;
}>();

const { success, error } = useToast();

const currentStep = ref<CreationStep | null>(props.database.current_step);
const progress = ref(props.database.progress);
const status = ref<DatabaseStatus>(props.database.status);
const errorMessage = ref(props.database.error_message);

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

let channel: ReturnType<typeof echo['private']>;

onMounted(() => {
    channel = echo.private(`database.${props.database.id}`);

    channel.listen('.step.updated', (data: StepUpdatePayload) => {
        currentStep.value = data.step;
        progress.value = data.progress;
        status.value = data.database.status;
    });

    channel.listen('.database.created', (data: DatabaseCreatedPayload) => {
        status.value = 'ready';
        progress.value = 100;
        success('Database criado!', `O database ${data.database.name} está pronto para uso.`);
    });

    channel.listen('.database.failed', (data: DatabaseFailedPayload) => {
        status.value = 'failed';
        errorMessage.value = data.error;
        error('Falha na criação', data.error);
    });
});

onUnmounted(() => {
    if (channel) {
        echo.leave(`database.${props.database.id}`);
    }
});
</script>

<template>
    <Head :title="`Database: ${database.name}`" />
    <Toaster />

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
                        Acompanhe o progresso da criação em tempo real
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

                <Card>
                    <CardHeader>
                        <CardTitle>Credentials</CardTitle>
                        <CardDescription>Credenciais com acesso a este database</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Total de credenciais</span>
                            <Badge variant="secondary">
                                <Link2 class="h-3 w-3 mr-1" />
                                {{ database.credentials_count ?? 0 }}
                            </Badge>
                        </div>
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
    </AuthenticatedLayout>
</template>
