<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { __ } from '@/composables/useLang';
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { useToast } from 'vue-toastification';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowLeft, Shield, ShieldCheck, Key, Loader2, Activity, User } from 'lucide-vue-next';
import PvTabs from '@/components/ui/pv-tabs/PvTabs.vue';
import PvTabsContent from '@/components/ui/pv-tabs/PvTabsContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import UserActivityTimeline from '@/components/user/UserActivityTimeline.vue';
import { useEchoChannels } from '@/composables/useEchoChannels';
import type { UserActivity, UserActivityCollection, UserStatus, UserStatusChangedEvent } from '@/types/user-status';
import axios from 'axios';

interface Role {
    id: number;
    name: string;
    permissions: { id: number; name: string }[];
}

interface AllRole {
    id: number;
    name: string;
}

interface Permission {
    id: number;
    name: string;
}

interface GroupedPermissions {
    [key: string]: Permission[];
}

interface Credential {
    id: number;
    name: string;
    permission: string;
}

interface Database {
    id: number;
    name: string;
    credential: string;
    permission: string;
}

interface PaginatedActivities {
    data: UserActivity[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface UserProfile {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    is_admin: boolean;
    active: boolean;
    status?: UserStatus;
    password_changed_at: string | null;
    created_at: string;
    roles: Role[];
    direct_permissions: { id: number; name: string }[];
    denied_permissions: number[];
    all_permissions: Permission[];
    features: string[];
    credentials: Credential[];
    databases: Database[];
}

const props = defineProps<{
    user: UserProfile;
    allRoles?: AllRole[];
    allPermissions?: Permission[];
}>();

const toast = useToast();
const activeTab = ref('info');
const isSavingPermissions = ref(false);

// User status - reactive for real-time updates
const userStatus = ref<UserStatus>(props.user.status || 'offline');

const statusRingColor = computed(() => {
  const colors: Record<UserStatus, string> = {
    online: 'ring-emerald-500',
    away: 'ring-yellow-500',
    busy: 'ring-red-500',
    offline: 'ring-gray-300',
  };
  return colors[userStatus.value] || colors.offline;
});

// WebSocket for real-time status of the viewed user
const { connect, listenToPresenceChannel, disconnect } = useEchoChannels();

onMounted(async () => {
  try {
    await connect();
    listenToPresenceChannel((event: UserStatusChangedEvent) => {
      if (String(event.user_id) === String(props.user.id)) {
        userStatus.value = event.status as UserStatus;
      }
    });
  } catch {
    // Non-blocking: continue without real-time updates
  }
});

onUnmounted(() => {
  try {
    disconnect();
  } catch {
    // Ignore
  }
});

// Activities state
const activities = ref<UserActivity[]>([]);
const isLoadingActivities = ref(false);
const activitiesCurrentPage = ref(1);
const activitiesLastPage = ref(1);
const hasLoadedActivities = ref(false);

// Fetch user activities
const fetchActivities = async (page: number = 1): Promise<void> => {
    if (isLoadingActivities.value) return;

    isLoadingActivities.value = true;

    try {
        const response = await axios.get<PaginatedActivities>(
            route('system.users.activities', props.user.id),
            {
                params: {
                    page,
                    per_page: 20,
                },
            }
        );

        if (page === 1) {
            activities.value = response.data.data;
        } else {
            activities.value = [...activities.value, ...response.data.data];
        }

        activitiesCurrentPage.value = response.data.current_page;
        activitiesLastPage.value = response.data.last_page;
        hasLoadedActivities.value = true;
    } catch (error) {
        console.error('Error fetching activities:', error);
        toast.error(__('Error loading activities'));
    } finally {
        isLoadingActivities.value = false;
    }
};

// Load more activities
const loadMoreActivities = (): void => {
    if (activitiesCurrentPage.value < activitiesLastPage.value) {
        fetchActivities(activitiesCurrentPage.value + 1);
    }
};

// Watch for tab changes to load activities
watch(activeTab, (newTab) => {
    if (newTab === 'updates' && !hasLoadedActivities.value) {
        fetchActivities(1);
    }
});

// Roles from backend - use computed for reactivity
const userRoles = computed(() => props.user.roles ?? []);
const userCredentials = computed(() => props.user.credentials ?? []);
const userDatabases = computed(() => props.user.databases ?? []);
const userFeatures = computed(() => props.user.features ?? []);

// Get the user's current role (only one allowed)
const currentRoleId = computed(() => {
    return userRoles.length > 0 ? userRoles[0].id : null;
});

// Use "none" for "no role" selection
const selectedRoleId = ref<number | string>(currentRoleId.value ?? 'none');

// Direct permissions (not from role)
const directPermissions = props.user.direct_permissions ?? [];
const directPermissionIds = ref<number[]>(directPermissions.map((p: Permission) => p.id));

// Denied permissions (explicitly revoked from role)
const deniedPermissionIds = ref<number[]>(props.user.denied_permissions ?? []);

// Checkbox states - reactive ref for reactivity
const checkboxStates = ref<Record<number, boolean>>({});

// Get role permission IDs for quick lookup
const rolePermissionIds = computed(() => {
    const ids: number[] = [];
    userRoles.value.forEach((r: Role) => {
        (r.permissions ?? []).forEach((p: Permission) => {
            if (p?.id) ids.push(p.id);
        });
    });
    return ids;
});

// Initialize checkbox states
const initCheckboxStates = (): void => {
    const states: Record<number, boolean> = {};
    (props.allPermissions ?? []).forEach((perm: Permission) => {
        const isDenied = deniedPermissionIds.value.includes(perm.id);
        const fromRole = rolePermissionIds.value.includes(perm.id);
        const isDirect = directPermissionIds.value.includes(perm.id);
        states[perm.id] = !isDenied && (fromRole || isDirect);
    });
    checkboxStates.value = states;
};

// Initialize after nextTick to ensure computed are evaluated
nextTick(() => {
    initCheckboxStates();
});

// Get role permission names for quick lookup
const rolePermissionNames = computed(() => {
    const perms: string[] = [];
    userRoles.value.forEach((r: Role) => {
        const rolePerms = Array.isArray(r.permissions)
            ? r.permissions
            : (r.permissions?.data ?? []);
        rolePerms.forEach((p: Permission) => {
            if (p?.name) perms.push(p.name);
        });
    });
    return new Set(perms);
});

// Group permissions by feature/module
const groupedPermissions = computed(() => {
    const groups: GroupedPermissions = {};

    (props.allPermissions ?? []).forEach((perm) => {
        // Skip users.* permissions - only admin can manage users
        if (perm.name.startsWith('users.')) {
            return;
        }

        const parts = perm.name.split('.');
        const module = parts[0] || 'other';
        if (!groups[module]) {
            groups[module] = [];
        }
        groups[module].push(perm);
    });

    return groups;
});

// Check if permission is assigned to user via role (by ID)
const hasRolePermissionById = (permId: number): boolean => {
    return rolePermissionIds.value.includes(permId);
};

// Check if permission is directly assigned
const hasDirectPermission = (permId: number): boolean => {
    return directPermissionIds.value.includes(permId);
};

// Check if permission is denied (explicitly revoked from role)
const isDeniedPermission = (permId: number): boolean => {
    return deniedPermissionIds.value.includes(permId);
};

// Check if user has permission (from role or direct, unless denied)
const hasPermission = (permName: string, permId: number): boolean => {
    if (isDeniedPermission(permId)) return false;
    return hasRolePermissionById(permId) || hasDirectPermission(permId);
};

// Get checkbox state for a permission - uses reactive checkboxStates
const getCheckboxState = (permId: number): boolean => {
    return checkboxStates.value[permId] ?? false;
};

// Toggle permission (add/remove from direct or denied)
const togglePermission = (permId: number): void => {
    const currentState = getCheckboxState(permId);
    const newState = !currentState;

    if (newState) {
        // Marcando - remover de negados se estiver lá
        deniedPermissionIds.value = deniedPermissionIds.value.filter(id => id !== permId);
        // Se NÃO vem da role, adicionar como direta
        if (!rolePermissionIds.value.includes(permId)) {
            directPermissionIds.value.push(permId);
        }
    } else {
        // Desmarcando
        if (rolePermissionIds.value.includes(permId)) {
            // Se vem da role, adicionar à lista de negados
            deniedPermissionIds.value.push(permId);
        } else {
            // Se é direta, remover
            directPermissionIds.value = directPermissionIds.value.filter(id => id !== permId);
        }
    }

    // Atualizar estado visual do checkbox imediatamente
    checkboxStates.value[permId] = newState;
};

const updateRole = (): void => {
    router.put(
        route('system.users.role.update', props.user.id),
        { role_id: selectedRoleId.value === 'none' ? null : selectedRoleId.value },
        {
            onSuccess: () => {
                toast.success(__('Role updated successfully'));
                // Reload page to get updated props
                router.visit(route('system.users.show', props.user.id), {
                    only: ['user'],
                    preserveScroll: true,
                    preserveState: false,
                });
            },
            onError: () => {
                toast.error(__('Error updating role. Please try again.'));
            },
        }
    );
};

const syncPermissions = (): void => {
    isSavingPermissions.value = true;

    router.post(
        route('system.users.permissions.sync', props.user.id),
        {
            permissions: [...directPermissionIds.value],
            denied_permissions: [...deniedPermissionIds.value],
        },
        {
            onSuccess: () => {
                toast.success(__('Permissions synced successfully'));
                // Reload page to get updated props
                router.visit(route('system.users.show', props.user.id), {
                    only: ['user'],
                    preserveScroll: true,
                    preserveState: false,
                });
            },
            onError: () => {
                toast.error(__('Error syncing permissions. Please try again.'));
            },
            onFinish: () => {
                isSavingPermissions.value = false;
            },
        }
    );
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const tabs = [
    { value: 'info', label: __('Information'), icon: 'User' },
    { value: 'roles', label: __('Roles and Permissions'), icon: 'Shield' },
    { value: 'updates', label: __('Updates'), icon: 'Activity' },
];

// Get user initials for avatar fallback
const userInitials = computed(() => {
    return props.user.name.slice(0, 2).toUpperCase();
});</script>

<template>
    <Head :title="__('Profile: :name', { name: user.name })" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('system.users.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="w-4 h-4" />
                    </Button>
                </Link>
                <div class="flex items-center gap-4">
                    <Avatar class="h-11 w-11 ring-3 ring-border" :class="statusRingColor">
                        <AvatarImage v-if="user.avatar" :src="user.avatar" />
                        <AvatarFallback class="bg-primary text-primary-foreground text-xl">
                            {{ userInitials }}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <h2 class="text-2xl font-semibold text-foreground">
                            {{ user.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            {{ user.email }}
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div>
            <PvTabs v-model="activeTab" :tabs="tabs">
                <!-- Aba Informações -->
                <PvTabsContent value="info" :active-tab="activeTab">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Informações Básicas -->
                        <Card class="h-full">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <User class="w-5 h-5" />
                                    {{ __('Basic Information') }}
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('Email') }}</span>
                                    <span>{{ user.email }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('Status') }}</span>
                                    <Badge :variant="user.active ? 'default' : 'outline'">
                                        {{ user.active ? __('Active') : __('Inactive') }}
                                    </Badge>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('Type') }}</span>
                                    <Badge v-if="user.is_admin" variant="default" class="bg-primary">
                                        {{ __('Admin') }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground">{{ __('User') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('Created at') }}</span>
                                    <span>{{ formatDate(user.created_at) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('Password changed') }}</span>
                                    <span v-if="user.password_changed_at">
                                        {{ formatDate(user.password_changed_at) }}
                                    </span>
                                    <Badge v-else variant="outline" class="text-yellow-500">
                                        {{ __('Pending') }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Features -->
                        <Card class="h-full">
                            <CardHeader>
                                <CardTitle>{{ __('Visible Features') }}</CardTitle>
                                <CardDescription>{{ __('Features active for this user') }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="userFeatures.length > 0" class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="feature in userFeatures"
                                        :key="feature"
                                        variant="outline"
                                    >
                                        {{ feature }}
                                    </Badge>
                                </div>
                                <p v-else class="text-muted-foreground text-sm">
                                    {{ __('No active features') }}
                                </p>
                            </CardContent>
                        </Card>

                        <!-- Credentials -->
                        <Card class="h-full">
                            <CardHeader>
                                <CardTitle>{{ __('Credentials') }}</CardTitle>
                                <CardDescription>{{ __('Database access permissions via credentials') }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="userCredentials.length > 0" class="space-y-2">
                                    <div
                                        v-for="credential in userCredentials"
                                        :key="credential.id"
                                        class="flex items-center justify-between"
                                    >
                                        <span>{{ credential.name }}</span>
                                        <Badge variant="secondary">
                                            {{ credential.permission }}
                                        </Badge>
                                    </div>
                                </div>
                                <p v-else class="text-muted-foreground text-sm">
                                    {{ __('No credentials') }}
                                </p>
                            </CardContent>
                        </Card>

                        <!-- Databases -->
                        <Card class="h-full">
                            <CardHeader>
                                <CardTitle>{{ __('Databases') }}</CardTitle>
                                <CardDescription>{{ __('Databases accessible via credentials') }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="userDatabases.length > 0" class="grid gap-2">
                                    <div
                                        v-for="database in userDatabases"
                                        :key="database.id"
                                        class="flex items-center justify-between p-2 rounded-lg bg-muted/50"
                                    >
                                        <div>
                                            <span class="font-medium">{{ database.name }}</span>
                                            <span class="text-muted-foreground text-sm ml-2">
                                                {{ __('via') }} {{ database.credential }}
                                            </span>
                                        </div>
                                        <Badge variant="secondary">
                                            {{ database.permission }}
                                        </Badge>
                                    </div>
                                </div>
                                <p v-else class="text-muted-foreground text-sm">
                                    {{ __('No accessible databases') }}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </PvTabsContent>

                <!-- Aba Roles e Permissões -->
                <PvTabsContent value="roles" :active-tab="activeTab">
                    <div class="grid gap-6 md:grid-cols-1">
                        <!-- Role do Usuário -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Shield class="w-5 h-5" />
                                    {{ __("User's Role") }}
                                </CardTitle>
                                <CardDescription>{{ __('Each user can only have one role') }}</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex gap-2">
                                    <Select v-model="selectedRoleId">
                                        <SelectTrigger class="flex-1">
                                            <SelectValue :placeholder="__('Select a role')" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                <span class="text-muted-foreground">{{ __('No role assigned') }}</span>
                                            </SelectItem>
                                            <SelectItem
                                                v-for="role in allRoles ?? []"
                                                :key="role.id"
                                                :value="role.id"
                                            >
                                                {{ role.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Button
                                        :disabled="selectedRoleId === (currentRoleId ?? 'none')"
                                        @click="updateRole"
                                    >
                                        <ShieldCheck class="w-4 h-4 mr-2" />
                                        {{ __('Save') }}
                                    </Button>
                                </div>
                                <div v-if="userRoles.length > 0">
                                    <Badge variant="secondary">
                                        {{ __('Current role: :name', { name: userRoles[0].name }) }}
                                    </Badge>
                                </div>
                                <p v-else class="text-muted-foreground text-sm">
                                    {{ __('No role assigned') }}
                                </p>
                            </CardContent>
                        </Card>

                        <!-- Todas as Permissões do Sistema -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Key class="w-5 h-5" />
                                    {{ __('System Permissions') }}
                                </CardTitle>
                                <CardDescription class="space-y-1">
                                    <p>{{ __('Mark extra permissions for this user (besides the role).') }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ __('Permissions inherited from the role are disabled.') }}
                                    </p>
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="Object.keys(groupedPermissions).length > 0" class="space-y-6">
                                    <div v-for="(permissions, module) in groupedPermissions" :key="module">
                                        <h4 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground mb-3">
                                            {{ module }}
                                        </h4>
                                        <div class="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                                            <label
                                                v-for="perm in permissions"
                                                :key="perm.id"
                                                class="flex items-center gap-3 p-3 rounded border transition-colors"
                                                :class="[
                                                    isDeniedPermission(perm.id)
                                                        ? 'bg-red-100 border-red-300 dark:bg-red-900/30 dark:border-red-700 opacity-70'
                                                        : !getCheckboxState(perm.id)
                                                        ? 'bg-muted/30 hover:bg-muted/50'
                                                        : rolePermissionIds.includes(perm.id)
                                                        ? 'bg-blue-100 border-blue-300 dark:bg-blue-900/30 dark:border-blue-700'
                                                        : 'bg-green-100 border-green-300 dark:bg-green-900/30 dark:border-green-700',
                                                    'cursor-pointer',
                                                ]"
                                            >
                                                <Checkbox
                                                    :modelValue="getCheckboxState(perm.id)"
                                                    :disabled="isSavingPermissions"
                                                    @update:modelValue="() => togglePermission(perm.id)"
                                                />
                                                <span class="text-sm font-mono flex-1">{{ perm.name }}</span>
                                                <Badge
                                                    v-if="isDeniedPermission(perm.id)"
                                                    variant="outline"
                                                    class="text-xs bg-red-500 text-white border-red-500"
                                                >
                                                    {{ __('Denied') }}
                                                </Badge>
                                                <Badge
                                                    v-else-if="rolePermissionIds.includes(perm.id)"
                                                    variant="outline"
                                                    class="text-xs bg-blue-500 text-white border-blue-500"
                                                >
                                                    {{ __('Role') }}
                                                </Badge>
                                                <Badge
                                                    v-else-if="directPermissionIds.includes(perm.id)"
                                                    variant="default"
                                                    class="badge-success text-xs"
                                                >
                                                    {{ __('Extra') }}
                                                </Badge>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-muted-foreground text-sm">
                                    {{ __('No permissions available in the system') }}
                                </p>

                                <!-- Save button for direct permissions -->
                                <div v-if="Object.keys(groupedPermissions).length > 0" class="mt-6 pt-4 border-t">
                                    <Button
                                        @click="syncPermissions"
                                        :disabled="isSavingPermissions"
                                    >
                                        <Loader2 v-if="isSavingPermissions" class="w-4 h-4 mr-2 animate-spin" />
                                        {{ __('Save Extra Permissions') }}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </PvTabsContent>

                <!-- Aba Updates -->
                <PvTabsContent value="updates" :active-tab="activeTab">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Activity class="w-5 h-5" />
                                {{ __('Activity Log') }}
                            </CardTitle>
                            <CardDescription>
                                {{ __('Recent activities and updates for this user') }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <UserActivityTimeline
                                :user-id="String(user.id)"
                                :activities="activities"
                                :loading="isLoadingActivities"
                                :has-more-pages="activitiesCurrentPage < activitiesLastPage"
                                @load-more="loadMoreActivities"
                            />
                        </CardContent>
                    </Card>
                </PvTabsContent>
            </PvTabs>
        </div>
    </AuthenticatedLayout>
</template>
