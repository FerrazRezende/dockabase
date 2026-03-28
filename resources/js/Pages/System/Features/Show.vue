<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, Play, Square, History, Settings } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { Feature, FeatureCollection } from '@/types/feature';

interface HistoryItem {
    id: string;
    action: string;
    actor: string;
    previous_state: Record<string, unknown> | null;
    new_state: Record<string, unknown> | null;
    created_at: string;
}

interface Props {
    feature: Feature;
    history: HistoryItem[];
}

const props = defineProps<Props>();

const showActivateDialog = ref(false);
const showDeactivateDialog = ref(false);
const activating = ref(false);

// Form state for activation
const strategy = ref<'all' | 'percentage' | 'users'>('all');
const percentage = ref(50);

const actionLabel = computed(() => {
    switch (props.feature.strategy) {
        case 'all':
            return 'Liberado para todos';
        case 'percentage':
            return `${props.feature.percentage}% dos usuários`;
        case 'users':
            return 'Usuários específicos';
        default:
            return 'Desativado';
    }
});

const activateFeature = async () => {
    activating.value = true;
    try {
        const body: Record<string, unknown> = {
            strategy: strategy.value,
        };

        if (strategy.value === 'percentage') {
            body.percentage = percentage.value;
        }

        const response = await fetch(route('system.features.activate', props.feature.name), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify(body),
        });

        if (response.ok) {
            showActivateDialog.value = false;
            router.reload();
        }
    } catch (error) {
        console.error('Failed to activate feature:', error);
    } finally {
        activating.value = false;
    }
};

const deactivateFeature = async () => {
    activating.value = true;
    try {
        const response = await fetch(route('system.features.deactivate', props.feature.name), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
        });

        if (response.ok) {
            showDeactivateDialog.value = false;
            router.reload();
        }
    } catch (error) {
        console.error('Failed to deactivate feature:', error);
    } finally {
        activating.value = false;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getActionBadge = (action: string) => {
    switch (action) {
        case 'activated':
            return { variant: 'default', label: 'Ativado', class: 'bg-green-500/10 text-green-500' };
        case 'deactivated':
            return { variant: 'outline', label: 'Desativado', class: '' };
        case 'updated':
            return { variant: 'secondary', label: 'Atualizado', class: '' };
        default:
            return { variant: 'outline', label: action, class: '' };
    }
};
</script>

<template>
    <Head :title="`${feature.display_name} - Feature Flags`" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('system.features.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        {{ feature.display_name }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ feature.description }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Status Card -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>Status</CardTitle>
                        <CardDescription>Configuração atual da feature</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <Badge
                                    :variant="feature.is_active ? 'default' : 'outline'"
                                    :class="feature.is_active ? 'bg-green-500/10 text-green-500' : ''"
                                    class="text-base px-4 py-1"
                                >
                                    {{ feature.is_active ? 'Ativo' : 'Inativo' }}
                                </Badge>
                                <span class="text-muted-foreground">{{ actionLabel }}</span>
                            </div>

                            <div class="flex gap-2">
                                <!-- Activate Dialog -->
                                <Dialog v-model:open="showActivateDialog">
                                    <DialogTrigger as-child>
                                        <Button
                                            v-if="!feature.is_active"
                                            variant="default"
                                            class="gap-2"
                                        >
                                            <Play class="h-4 w-4" />
                                            Ativar
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Ativar Feature</DialogTitle>
                                            <DialogDescription>
                                                Escolha como deseja liberar esta feature.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div class="space-y-4 py-4">
                                            <div class="space-y-2">
                                                <Label>Estratégia de Rollout</Label>
                                                <div class="grid grid-cols-3 gap-2">
                                                    <Button
                                                        :variant="strategy === 'all' ? 'default' : 'outline'"
                                                        @click="strategy = 'all'"
                                                        class="h-auto py-3 flex-col"
                                                    >
                                                        <span class="font-semibold">Todos</span>
                                                        <span class="text-xs opacity-70">100%</span>
                                                    </Button>
                                                    <Button
                                                        :variant="strategy === 'percentage' ? 'default' : 'outline'"
                                                        @click="strategy = 'percentage'"
                                                        class="h-auto py-3 flex-col"
                                                    >
                                                        <span class="font-semibold">Percentual</span>
                                                        <span class="text-xs opacity-70">Gradual</span>
                                                    </Button>
                                                    <Button
                                                        :variant="strategy === 'users' ? 'default' : 'outline'"
                                                        @click="strategy = 'users'"
                                                        class="h-auto py-3 flex-col"
                                                    >
                                                        <span class="font-semibold">Usuários</span>
                                                        <span class="text-xs opacity-70">Específicos</span>
                                                    </Button>
                                                </div>
                                            </div>

                                            <div v-if="strategy === 'percentage'" class="space-y-2">
                                                <Label>Percentual</Label>
                                                <Input
                                                    type="number"
                                                    v-model="percentage"
                                                    min="0"
                                                    max="100"
                                                    placeholder="50"
                                                />
                                                <p class="text-xs text-muted-foreground">
                                                    Usuários serão selecionados de forma determinística baseada no ID.
                                                </p>
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" @click="showActivateDialog = false">
                                                Cancelar
                                            </Button>
                                            <Button @click="activateFeature" :disabled="activating">
                                                {{ activating ? 'Ativando...' : 'Ativar' }}
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <!-- Deactivate Dialog -->
                                <Dialog v-model:open="showDeactivateDialog">
                                    <DialogTrigger as-child>
                                        <Button
                                            v-if="feature.is_active"
                                            variant="destructive"
                                            class="gap-2"
                                        >
                                            <Square class="h-4 w-4" />
                                            Desativar
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Desativar Feature</DialogTitle>
                                            <DialogDescription>
                                                Tem certeza que deseja desativar esta feature? Todos os usuários perderão acesso.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <Button variant="outline" @click="showDeactivateDialog = false">
                                                Cancelar
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                @click="deactivateFeature"
                                                :disabled="activating"
                                            >
                                                {{ activating ? 'Desativando...' : 'Desativar' }}
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- History Card -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <History class="h-5 w-5 text-muted-foreground" />
                            <CardTitle>Histórico</CardTitle>
                        </div>
                        <CardDescription>Histórico de mudanças desta feature</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table v-if="history.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Data</TableHead>
                                    <TableHead>Ação</TableHead>
                                    <TableHead>Responsável</TableHead>
                                    <TableHead>Detalhes</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in history" :key="item.id">
                                    <TableCell class="text-muted-foreground">
                                        {{ formatDate(item.created_at) }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="getActionBadge(item.action).variant"
                                            :class="getActionBadge(item.action).class"
                                        >
                                            {{ getActionBadge(item.action).label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ item.actor }}</TableCell>
                                    <TableCell class="text-muted-foreground text-sm">
                                        <template v-if="item.new_state">
                                            <span v-if="item.new_state.strategy">
                                                Estratégia: {{ item.new_state.strategy }}
                                            </span>
                                            <span v-if="item.new_state.percentage">
                                                · {{ item.new_state.percentage }}%
                                            </span>
                                        </template>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <div v-else class="text-center py-8 text-muted-foreground">
                            Nenhum histórico disponível
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
