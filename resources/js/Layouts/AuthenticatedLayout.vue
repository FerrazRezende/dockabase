<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import NotificationCenter from '@/components/NotificationCenter.vue';
import StatusPickerDropdown from '@/components/user/StatusPickerDropdown.vue';
import {
    Database,
    Home,
    Sun,
    Moon,
    LogOut,
    PanelLeftClose,
    PanelLeft,
    Flag,
    Key,
    Users,
    Shield,
    AlertTriangle,
} from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';
import { useLang, __ } from '@/composables/useLang';
import { usePermissions } from '@/composables/usePermissions';
import { useUserStatus } from '@/composables/useUserStatus';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import type { UserStatus } from '@/types/user-status';

const { setStatus, refreshStatus, currentStatus } = useUserStatus();

// Only set online on first mount if user has no status yet in Redis.
// On subsequent Inertia navigations the layout remounts, so we check
// the current status first to avoid overwriting a manual status like "busy".
const hasInitialized = ref(false);

onMounted(async () => {
  if (!hasInitialized.value) {
    hasInitialized.value = true;
    await refreshStatus();
    // Only set online if user has no active status in Redis
    if (!currentStatus.value || currentStatus.value === 'offline') {
      await setStatus('online');
    }
  }
});

// Set status to offline when tab is closed or navigated away
const handleBeforeUnload = () => {
  fetch('/api/user/status', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify({ status: 'offline' }),
    keepalive: true,
  });
};

onMounted(() => {
  window.addEventListener('beforeunload', handleBeforeUnload);
});

onUnmounted(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
});

defineProps<{
    auth: {
        user: {
            id: string;
            name: string;
            email: string;
            is_admin: boolean;
            avatar?: string;
        };
    };
    userStatus?: UserStatus;
    title?: string;
}>();

const page = usePage();
const activeFeatures = computed(() => page.props.activeFeatures as string[] | undefined);
const impersonating = computed(() => page.props.impersonating as {
    is_impersonating: boolean;
    original_user_id?: number | null;
    original_user?: { name: string; email: string } | null;
    target_user?: { name: string; email: string };
} | undefined);

const { isDark, toggleDark } = useDarkMode();
const { canView } = usePermissions();
const collapsed = ref(false);

const toggleSidebar = () => {
    collapsed.value = !collapsed.value;
};

const initials = (name: string): string => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen bg-background">
        <!-- Sidebar -->
        <aside
            :class="[
                'fixed left-0 top-0 z-40 h-screen border-r border-border bg-card transition-all duration-300 flex flex-col',
                collapsed ? 'w-16' : 'w-64',
            ]"
        >
            <!-- Header -->
            <div
                :class="[
                    'flex h-16 items-center border-b border-border',
                    collapsed ? 'px-2 justify-center' : 'px-4 gap-3',
                ]"
            >
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-9 w-9 shrink-0"
                    @click="toggleSidebar"
                >
                    <PanelLeftClose v-if="!collapsed" class="h-4 w-4" />
                    <PanelLeft v-else class="h-4 w-4" />
                </Button>

                <div
                    v-if="!collapsed"
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary"
                >
                    <Database class="h-5 w-5 text-primary-foreground" />
                </div>
                <span
                    v-if="!collapsed"
                    class="text-lg font-semibold text-foreground"
                >
                    DockaBase
                </span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1 p-2">
                <!-- Home for non-admin users -->
                <Link
                    v-if="!auth.user.is_admin"
                    :href="route('dashboard')"
                    :class="[
                        'flex items-center rounded-lg text-sm font-medium transition-colors',
                        collapsed
                            ? 'justify-center p-3'
                            : 'gap-3 px-3 py-2',
                        route().current('dashboard')
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                    ]"
                >
                    <Home class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed">{{ __('Home') }}</span>
                </Link>

                <!-- Databases - requires database-creator feature AND databases.view permission -->
                <Link
                    v-if="!auth.user.is_admin && activeFeatures?.includes('database-creator') && canView('databases')"
                    :href="route('app.databases.index')"
                    :class="[
                        'flex items-center rounded-lg text-sm font-medium transition-colors',
                        collapsed
                            ? 'justify-center p-3'
                            : 'gap-3 px-3 py-2',
                        route().current('app.databases.*')
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                    ]"
                >
                    <Database class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed">{{ __('Databases') }}</span>
                </Link>

                <!-- Credentials - requires credentials-manager feature AND credentials.view permission -->
                <Link
                    v-if="!auth.user.is_admin && activeFeatures?.includes('credentials-manager') && canView('credentials')"
                    :href="route('app.credentials.index')"
                    :class="[
                        'flex items-center rounded-lg text-sm font-medium transition-colors',
                        collapsed
                            ? 'justify-center p-3'
                            : 'gap-3 px-3 py-2',
                        route().current('app.credentials.*')
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                    ]"
                >
                    <Key class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed">{{ __('Credentials') }}</span>
                </Link>

                <!-- Admin Section -->
                <template v-if="auth.user.is_admin">
                    <Link
                        :href="route('dashboard')"
                        :class="[
                            'flex items-center rounded-lg text-sm font-medium transition-colors',
                            collapsed
                                ? 'justify-center p-3'
                                : 'gap-3 px-3 py-2',
                            route().current('dashboard')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <Home class="h-5 w-5 shrink-0" />
                        <span v-if="!collapsed">{{ __('Home') }}</span>
                    </Link>

                    <!-- System Section -->
                    <div class="pt-4 mt-4 border-t border-border">
                    <p v-if="!collapsed" class="px-3 mb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        {{ __('System') }}
                    </p>
                    <Link
                        :href="route('system.features.index')"
                        :class="[
                            'flex items-center rounded-lg text-sm font-medium transition-colors',
                            collapsed
                                ? 'justify-center p-3'
                                : 'gap-3 px-3 py-2',
                            route().current('system.features.*')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <Flag class="h-5 w-5 shrink-0" />
                        <span v-if="!collapsed">{{ __('Features') }}</span>
                    </Link>
                    <Link
                        :href="route('system.permissions.index')"
                        :class="[
                            'flex items-center rounded-lg text-sm font-medium transition-colors',
                            collapsed
                                ? 'justify-center p-3'
                                : 'gap-3 px-3 py-2',
                            route().current('system.permissions.*') || route().current('system.roles.*')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <Shield class="h-5 w-5 shrink-0" />
                        <span v-if="!collapsed">{{ __('Permissions') }}</span>
                    </Link>
                    <Link
                        :href="route('system.users.index')"
                        :class="[
                            'flex items-center rounded-lg text-sm font-medium transition-colors',
                            collapsed
                                ? 'justify-center p-3'
                                : 'gap-3 px-3 py-2',
                            route().current('system.users.*')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <Users class="h-5 w-5 shrink-0" />
                        <span v-if="!collapsed">{{ __('Users') }}</span>
                    </Link>
                </div>
                </template>
            </nav>

            <!-- Footer -->
            <div class="border-t border-border p-2">
                <StatusPickerDropdown
                    :avatar-url="auth.user.avatar"
                    :user-name="auth.user.name"
                    :initial-status="userStatus"
                    :compact="collapsed"
                />
            </div>
        </aside>

        <!-- Main Content -->
        <main :class="['flex-1 transition-all duration-300 flex flex-col', collapsed ? 'ml-16' : 'ml-64']">
            <!-- Header -->
            <header
                v-if="impersonating?.is_impersonating"
                class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-amber-500/30 bg-amber-500/10 backdrop-blur-sm px-6"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <slot name="header" />
                </div>
                <div class="absolute inset-x-0 flex items-center justify-center pointer-events-none">
                    <div class="flex items-center gap-2 pointer-events-auto">
                        <AlertTriangle class="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" />
                        <span class="text-sm font-medium text-amber-900 dark:text-amber-100">
                            {{ __('You are accessing as :name.', { name: auth.user.name }) }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <NotificationCenter />
                    <Button variant="ghost" size="icon" @click="toggleDark">
                        <Sun v-if="isDark" class="h-5 w-5" />
                        <Moon v-else class="h-5 w-5" />
                    </Button>
                    <Button
                        size="sm"
                        class="gap-2 bg-amber-600 text-white hover:bg-amber-700 border-amber-600 hover:border-amber-700"
                        @click="router.post(route('system.impersonate.stop'))"
                    >
                        <LogOut class="h-4 w-4" />
                        {{ __('Exit impersonation') }}
                    </Button>
                </div>
            </header>
            <header
                v-else
                class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border bg-background/80 backdrop-blur-sm px-6"
            >
                <div>
                    <slot name="header" />
                </div>
                <div class="flex items-center gap-2">
                    <NotificationCenter />
                    <Button variant="ghost" size="icon" @click="toggleDark">
                        <Sun v-if="isDark" class="h-5 w-5" />
                        <Moon v-else class="h-5 w-5" />
                    </Button>
                    <Link :href="route('logout')" method="post" as="button">
                        <Button variant="ghost" size="sm" class="gap-2">
                            <LogOut class="h-4 w-4" />
                            {{ __('Sign out') }}
                        </Button>
                    </Link>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-6">
                <slot />
            </div>
        </main>
    </div>
</template>
