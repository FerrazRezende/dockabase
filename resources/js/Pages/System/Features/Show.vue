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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, Play, Square, History, UserPlus, X, Users } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { Feature } from '@/types/feature';

interface HistoryItem {
    id: string;
    action: string;
    actor: string;
    previous_state: Record<string, unknown> | null;
    new_state: Record<string, unknown> | null;
    created_at: string;
}

interface User {
    id: string;
    name: string;
    email: string;
}

interface Props {
    feature: Feature;
    history: HistoryItem[];
    users: User[];
}

const props = defineProps<Props>();

const showActivateDialog = ref(false);
const showDeactivateDialog = ref(false);
const activating = ref(false);

// Form state for activation
const strategy = ref<'all' | 'percentage' | 'users'>('all');
const percentage = ref(50);
const selectedUserIds = ref<string[]>([]);
const selectKey = ref(0); // Used to reset the select component

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

// Get selected users details
const selectedUsers = computed(() => {
    return props.users.filter(u => selectedUserIds.value.includes(u.id));
});

// Remove user from selection
const removeUser = (userId: string) => {
    selectedUserIds.value = selectedUserIds.value.filter(id => id !== userId);
};

// Add user to selection
const addUser = (userId: string) => {
    if (userId && !selectedUserIds.value.includes(userId)) {
        selectedUserIds.value.push(userId);
    }
    // Reset select by incrementing key
    selectKey.value++;
};

const activateFeature = () => {
    activating.value = true;

    const body: Record<string, unknown> = {
        strategy: strategy.value,
    };

    if (strategy.value === 'percentage') {
        body.percentage = percentage.value;
    }

    if (strategy.value === 'users') {
        body.user_ids = selectedUserIds.value;
    }

    router.post(route('system.features.activate', props.feature.name), body, {
        preserveScroll: true,
        onSuccess: () => {
            showActivateDialog.value = false;
        },
        onFinish: () => {
            activating.value = false;
        },
    });
};

const deactivateFeature = () => {
    activating.value = true;

    router.post(route('system.features.deactivate', props.feature.name), {}, {
        preserveScroll: true,
        onFinish: () => {
            activating.value = false;
            showDeactivateDialog.value = false;
        },
    });
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

// Reset form when dialog opens
const openActivateDialog = () => {
    strategy.value = 'all';
    percentage.value = 50;
    selectedUserIds.value = [];
    selectKey.value++;
    showActivateDialog.value = true;
};

// Deterministic percentage check (same as backend)
const checkPercentage = (userId: string, percentage: number): boolean => {
    // Simple hash function similar to crc32
    let hash = 0;
    for (let i = 0; i < userId.length; i++) {
        const char = userId.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return Math.abs(hash) % 100 < percentage;
};

// Get users who can see this feature
const usersWithAccess = computed(() => {
    if (!props.feature.is_active) {
        return [];
    }

    switch (props.feature.strategy) {
        case 'all':
            return props.users; // All users
        case 'percentage':
            // Use deterministic selection based on user ID
            return props.users.filter(u => checkPercentage(u.id, props.feature.percentage));
        case 'users':
            // Only selected users
            return props.users.filter(u => (props.feature.user_ids || []).includes(u.id));
        default:
            return [];
    }
});

// Get display info for access
const accessDisplay = computed(() => {
    if (!props.feature.is_active) {
        return { type: 'none', message: 'Nenhum usuário tem acesso' };
    }

    switch (props.feature.strategy) {
        case 'all':
            return { type: 'all', message: 'Todos os usuários estão vendo a feature' };
        case 'percentage':
            return { type: 'percentage', message: `${props.feature.percentage}% dos usuários (${usersWithAccess.value.length} de ${props.users.length})` };
        case 'users':
            return { type: 'users', message: `${usersWithAccess.value.length} usuário(s) selecionado(s)` };
        default:
            return { type: 'none', message: 'Nenhum usuário tem acesso' };
    }
});
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

        <div class="space-y-6">
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
                                        @click="openActivateDialog"
                                    >
                                        <Play class="h-4 w-4" />
                                        Ativar
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="max-w-lg">
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

                                        <div v-if="strategy === 'users'" class="space-y-3">
                                            <Label>Selecionar Usuários</Label>

                                            <!-- User Select -->
                                            <Select :key="selectKey" @update:model-value="addUser">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Selecione um usuário" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="user in users.filter(u => !selectedUserIds.includes(u.id))"
                                                        :key="user.id"
                                                        :value="String(user.id)"
                                                    >
                                                        {{ user.name }} ({{ user.email }})
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>

                                            <!-- Selected Users Tags -->
                                            <div v-if="selectedUsers.length > 0" class="flex flex-wrap gap-2">
                                                <Badge
                                                    v-for="user in selectedUsers"
                                                    :key="user.id"
                                                    variant="secondary"
                                                    class="gap-1 pr-1"
                                                >
                                                    {{ user.name }}
                                                    <button
                                                        @click="removeUser(user.id)"
                                                        class="ml-1 hover:bg-destructive/20 rounded-full p-0.5"
                                                    >
                                                        <X class="h-3 w-3" />
                                                    </button>
                                                </Badge>
                                            </div>
                                            <p v-else class="text-xs text-muted-foreground">
                                                Nenhum usuário selecionado
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

            <!-- Users with Access Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Users class="h-5 w-5 text-muted-foreground" />
                        <CardTitle>Usuários com Acesso</CardTitle>
                    </div>
                    <CardDescription>{{ accessDisplay.message }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- All users message -->
                    <div v-if="accessDisplay.type === 'all'" class="text-center py-8">
                        <div class="text-green-500 font-semibold text-lg mb-2">
                            TODOS OS USUÁRIOS ESTÃO VENDO A FEATURE
                        </div>
                        <p class="text-muted-foreground text-sm">
                            Total de {{ users.length }} usuário(s) no sistema
                        </p>
                    </div>

                    <!-- Users table for percentage and users strategies -->
                    <Table v-else-if="usersWithAccess.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>Email</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in usersWithAccess" :key="user.id">
                                <TableCell class="font-medium">
                                    {{ user.name }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ user.email }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- No users message -->
                    <div v-else class="text-center py-8 text-muted-foreground">
                        Nenhum usuário tem acesso a esta feature
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
    </AuthenticatedLayout>
</template>
