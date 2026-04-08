<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import NotificationCenter from '@/components/NotificationCenter.vue';
import StatusPickerDropdown from '@/components/user/StatusPickerDropdown.vue';
import {
    Database,
    Home,
    Sun,
    Moon,
    LogOut,
    Settings,
    PanelLeftClose,
    PanelLeft,
    ChevronDown,
    Flag,
    Key,
    Users,
    Shield,
} from 'lucide-vue-next';
import ImpersonateBanner from '@/components/ImpersonateBanner.vue';
import { useDarkMode } from '@/composables/useDarkMode';
import { usePermissions } from '@/composables/usePermissions';
import { ref, computed } from 'vue';
import type { UserStatus } from '@/types/user-status';

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
                    <span v-if="!collapsed">Home</span>
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
                    <span v-if="!collapsed">Databases</span>
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
                    <span v-if="!collapsed">Credentials</span>
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
                        <span v-if="!collapsed">Home</span>
                    </Link>

                    <!-- System Section -->
                    <div class="pt-4 mt-4 border-t border-border">
                    <p v-if="!collapsed" class="px-3 mb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        Sistema
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
                        <span v-if="!collapsed">Features</span>
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
                        <span v-if="!collapsed">Permissões</span>
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
                        <span v-if="!collapsed">Usuários</span>
                    </Link>
                </div>
                </template>
            </nav>

            <!-- Footer -->
            <div class="border-t border-border p-2 space-y-2">
                <!-- Status Picker (only show when not collapsed) -->
                <div v-if="!collapsed" class="px-1">
                    <StatusPickerDropdown
                        :avatar-url="auth.user.avatar"
                        :user-name="auth.user.name"
                        :initial-status="userStatus"
                        compact
                    />
                </div>

                <!-- User Menu -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            :class="[
                                'w-full',
                                collapsed
                                    ? 'h-10 w-10 p-0 justify-center'
                                    : 'justify-start gap-2 h-10 px-2',
                            ]"
                        >
                            <Avatar class="h-8 w-8 shrink-0">
                                <AvatarImage :src="auth.user.avatar" />
                                <AvatarFallback class="bg-primary text-primary-foreground text-xs">
                                    {{ initials(auth.user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <template v-if="!collapsed">
                                <div class="flex flex-1 flex-col items-start text-left truncate">
                                    <span class="text-sm font-medium leading-none">
                                        {{ auth.user.name }}
                                    </span>
                                    <span class="text-xs text-muted-foreground truncate">
                                        {{ auth.user.email }}
                                    </span>
                                </div>
                                <ChevronDown class="h-4 w-4 shrink-0 text-muted-foreground" />
                            </template>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" side="top" :side-offset="8" class="w-56">
                        <DropdownMenuItem as-child>
                            <Link :href="route('profile.edit')" class="flex items-center gap-2">
                                <Settings class="h-4 w-4" />
                                Configurações
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </aside>

        <!-- Main Content -->
        <main :class="['flex-1 transition-all duration-300 flex flex-col', collapsed ? 'ml-16' : 'ml-64']">
            <!-- Impersonate Banner -->
            <ImpersonateBanner
                v-if="impersonating?.is_impersonating"
                :user-name="auth.user.name"
            />
            <!-- Header -->
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border bg-background/80 backdrop-blur-sm px-6">
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
                            Sair
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
