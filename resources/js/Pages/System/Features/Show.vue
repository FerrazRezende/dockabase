<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { __ } from '@/composables/useLang';
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, Play, Square, History, UserPlus, X, Users } from 'lucide-vue-next';
import { ref, computed, watch, nextTick } from 'vue';
import type { Feature } from '@/types/feature';
import { useToast } from '@/composables/useToast';

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
    avatar?: string;
}

interface Props {
    feature: Feature;
    history: HistoryItem[];
    users: User[];
    usersWithAccess: User[];
}

const props = defineProps<Props>();
const toast = useToast();

const showActivateDialog = ref(false);
const showDeactivateDialog = ref(false);
const activating = ref(false);

// Form state for activation
const strategy = ref<'all' | 'percentage' | 'users'>('all');
const percentage = ref(50);
const selectedUserIds = ref<string[]>([]);
const selectedUserId = ref<string>(''); // Temporary for Select v-model

// Watch for Select changes
watch(selectedUserId, (newId) => {
    if (newId && !selectedUserIds.value.includes(newId)) {
        selectedUserIds.value.push(newId);
    }
    // Reset to allow selecting another
    nextTick(() => {
        selectedUserId.value = '';
    });
});

const actionLabel = computed(() => {
    switch (props.feature.strategy) {
        case 'all':
            return __('Released to all');
        case 'percentage':
            return `${props.feature.percentage}% ${__('of users')}`;
        case 'users':
            return __('Specific users');
        default:
            return __('Inactive');
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
            toast.success(__('Feature enabled successfully'));
            showActivateDialog.value = false;
        },
        onError: () => {
            toast.error(__('Error activating feature'));
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
        onSuccess: () => {
            toast.success(__('Feature disabled successfully'));
        },
        onError: () => {
            toast.error(__('Error deactivating feature'));
        },
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
            return { variant: 'default', label: __('Activated'), class: 'badge-success' };
        case 'deactivated':
            return { variant: 'outline', label: __('Deactivated'), class: '' };
        case 'updated':
            return { variant: 'secondary', label: __('Updated'), class: '' };
        default:
            return { variant: 'outline', label: action, class: '' };
    }
};

// Reset form when dialog opens
const openActivateDialog = () => {
    strategy.value = 'all';
    percentage.value = 50;
    selectedUserIds.value = [];
    selectedUserId.value = '';
    showActivateDialog.value = true;
};

// Get display info for access
const accessDisplay = computed(() => {
    if (!props.feature.is_active) {
        return { type: 'none', message: __('No users have access') };
    }

    switch (props.feature.strategy) {
        case 'all':
            return { type: 'all', message: __('All users are seeing this feature') };
        case 'percentage':
            return { type: 'percentage', message: `${props.feature.percentage}% ${__('of users')} (${props.usersWithAccess.length} ${__('of')} ${props.users.length})` };
        case 'users':
            return { type: 'users', message: __(':count user(s) selected', { count: props.usersWithAccess.length }) };
        default:
            return { type: 'none', message: __('No users have access') };
    }
});
</script>

<template>
    <Head :title="`${feature.display_name} - ${__('Feature Flags')}`" />

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
                    <CardTitle>{{ __('Status') }}</CardTitle>
                    <CardDescription>{{ __('Current configuration') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <Badge
                                :variant="feature.is_active ? 'default' : 'outline'"
                                :class="feature.is_active ? 'badge-success' : ''"
                                class="text-base px-4 py-1"
                            >
                                {{ feature.is_active ? __('Active') : __('Inactive') }}
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
                                        {{ __('Activate') }}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="max-w-lg">
                                    <DialogHeader>
                                        <DialogTitle>{{ __('Activate') }} {{ __('Feature') }}</DialogTitle>
                                        <DialogDescription>
                                            {{ __('Choose rollout strategy') }}
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div class="space-y-4 py-4">
                                        <div class="space-y-2">
                                            <Label>{{ __('Rollout Strategy') }}</Label>
                                            <div class="grid grid-cols-3 gap-2">
                                                <Button
                                                    :variant="strategy === 'all' ? 'default' : 'outline'"
                                                    @click="strategy = 'all'"
                                                    class="h-auto py-3 flex-col"
                                                >
                                                    <span class="font-semibold">{{ __('All') }}</span>
                                                    <span class="text-xs opacity-70">100%</span>
                                                </Button>
                                                <Button
                                                    :variant="strategy === 'percentage' ? 'default' : 'outline'"
                                                    @click="strategy = 'percentage'"
                                                    class="h-auto py-3 flex-col"
                                                >
                                                    <span class="font-semibold">{{ __('Percentage') }}</span>
                                                    <span class="text-xs opacity-70">{{ __('of users') }}</span>
                                                </Button>
                                                <Button
                                                    :variant="strategy === 'users' ? 'default' : 'outline'"
                                                    @click="strategy = 'users'"
                                                    class="h-auto py-3 flex-col"
                                                >
                                                    <span class="font-semibold">{{ __('Users') }}</span>
                                                    <span class="text-xs opacity-70">{{ __('of users') }}</span>
                                                </Button>
                                            </div>
                                        </div>

                                        <div v-if="strategy === 'percentage'" class="space-y-2">
                                            <Label>{{ __('Percentage') }}</Label>
                                            <Input
                                                type="number"
                                                v-model="percentage"
                                                min="0"
                                                max="100"
                                                placeholder="50"
                                            />
                                            <p class="text-xs text-muted-foreground">
                                                Users will be selected deterministically based on ID.
                                            </p>
                                        </div>

                                        <div v-if="strategy === 'users'" class="space-y-3">
                                            <Label>{{ __('Select users') }}</Label>

                                            <!-- User Select -->
                                            <Select v-model="selectedUserId">
                                                <SelectTrigger>
                                                    <SelectValue :placeholder="__('Select a user')" />
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
                                                {{ __('No user selected') }}
                                            </p>
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <Button variant="outline" @click="showActivateDialog = false">
                                            {{ __('Cancel') }}
                                        </Button>
                                        <Button @click="activateFeature" :disabled="activating">
                                            {{ activating ? __('Activating...') : __('Activate') }}
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
                                        {{ __('Deactivate') }}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>{{ __('Deactivate') }} {{ __('Feature') }}</DialogTitle>
                                        <DialogDescription>
                                            Are you sure you want to deactivate this feature? All users will lose access.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter>
                                        <Button variant="outline" @click="showDeactivateDialog = false">
                                            {{ __('Cancel') }}
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            @click="deactivateFeature"
                                            :disabled="activating"
                                        >
                                            {{ activating ? __('Deactivating...') : __('Deactivate') }}
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
                        <CardTitle>{{ __('Users with access') }}</CardTitle>
                    </div>
                    <CardDescription>{{ accessDisplay.message }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- All users message -->
                    <div v-if="accessDisplay.type === 'all'" class="text-center py-8">
                        <div class="text-success font-semibold text-lg mb-2">
                            {{ __('All users are seeing this feature') }}
                        </div>
                        <p class="text-muted-foreground text-sm">
                            {{ __('Total users in system', { count: users.length }) }}
                        </p>
                    </div>

                    <!-- Users table for percentage and users strategies -->
                    <Table v-else-if="usersWithAccess.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[60px]"></TableHead>
                                <TableHead>{{ __('Name') }}</TableHead>
                                <TableHead>{{ __('Email') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in usersWithAccess" :key="user.id">
                                <TableCell>
                                    <Avatar class="h-8 w-8">
                                        <AvatarImage v-if="user.avatar" :src="user.avatar" />
                                        <AvatarFallback class="bg-primary text-primary-foreground text-xs">
                                            {{ user.name.slice(0, 2).toUpperCase() }}
                                        </AvatarFallback>
                                    </Avatar>
                                </TableCell>
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
                        {{ __('No users have access') }}
                    </div>
                </CardContent>
            </Card>

            <!-- History Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <History class="h-5 w-5 text-muted-foreground" />
                        <CardTitle>{{ __('History') }}</CardTitle>
                    </div>
                    <CardDescription>{{ __('Change history') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table v-if="history.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ __('Date') }}</TableHead>
                                <TableHead>{{ __('Action') }}</TableHead>
                                <TableHead>{{ __('Responsible') }}</TableHead>
                                <TableHead>{{ __('Details') }}</TableHead>
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
                                            {{ __('Strategy:') }} {{ item.new_state.strategy }}
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
                        {{ __('No history available') }}
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
