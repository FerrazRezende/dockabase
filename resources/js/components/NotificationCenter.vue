<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Bell, Loader2 } from 'lucide-vue-next';
import type { Notification } from '@/types/notification';
import { __ } from '@/composables/useLang';
import { useToast } from 'vue-toastification';
import { getEcho } from '@/composables/echo';
import axios from 'axios';

const page = usePage();
const toast = useToast();

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);

const fetchNotifications = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/notifications');
        notifications.value = data.data;
    } catch {
        // Silently fail
    } finally {
        loading.value = false;
    }
};

const fetchUnreadCount = async () => {
    try {
        const { data } = await axios.get('/api/notifications/unread-count');
        unreadCount.value = data.count;
    } catch {
        // Silently fail
    }
};

const addNotification = (notification: Notification) => {
    notifications.value.unshift(notification);
    unreadCount.value++;
};

const markAllAsRead = async () => {
    try {
        await axios.post('/api/notifications/read-all');
        notifications.value.forEach(n => n.read = true);
        unreadCount.value = 0;
    } catch {
        // Silently fail
    }
};

const formatTime = (dateString: string): string => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();

    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return __('Just now');
    if (minutes < 60) return __(':minutesm ago', { minutes });
    if (hours < 24) return __(':hoursh ago', { hours });
    if (days < 7) return __(':daysd ago', { days });

    return date.toLocaleDateString();
};

const onDropdownOpen = (open: boolean) => {
    if (open && unreadCount.value > 0) {
        markAllAsRead();
    }
};

// Real-time notifications via Echo
let echoChannel: ReturnType<ReturnType<typeof getEcho>['private']> | null = null;

const setupEchoListener = () => {
    const user = page.props.auth?.user as { id: number } | undefined;
    if (!user?.id) return;

    try {
        const echo = getEcho();
        echoChannel = echo.private(`users.${user.id}`);

        echoChannel.listen('.user.added-to-credential', (event: { credential_name: string }) => {
            toast.info(__('You have been added to the credential :name', { name: event.credential_name }));
            fetchUnreadCount();
            fetchNotifications();
        });

        echoChannel.listen('.user.removed-from-credential', (event: { credential_name: string }) => {
            toast.info(__('You have been removed from the credential :name', { name: event.credential_name }));
            fetchUnreadCount();
            fetchNotifications();
        });
    } catch {
        // Echo not available
    }
};

onMounted(() => {
    fetchNotifications();
    fetchUnreadCount();
    setupEchoListener();
});

onUnmounted(() => {
    if (echoChannel) {
        try {
            const echo = getEcho();
            echo.leaveChannel(`private-users.${(page.props.auth?.user as { id: number })?.id}`);
        } catch {
            // Silently fail
        }
    }
});
</script>

<template>
    <DropdownMenu @update:open="onDropdownOpen">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="h-5 w-5" />
                <Badge
                    v-if="unreadCount > 0"
                    variant="destructive"
                    class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel>
                <span>{{ __('Notifications') }}</span>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div v-if="loading" class="p-4 flex justify-center">
                <Loader2 class="h-5 w-5 animate-spin text-muted-foreground" />
            </div>

            <div v-else-if="notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                {{ __('No notifications') }}
            </div>

            <template v-else>
                <div class="max-h-64 overflow-y-auto">
                    <DropdownMenuItem
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex flex-col items-start gap-1 p-3 cursor-pointer"
                        :class="{ 'bg-muted/50': !notification.read }"
                    >
                        <div class="flex items-center gap-2 w-full">
                            <span class="font-medium text-sm flex-1">{{ notification.title }}</span>
                            <span class="text-xs text-muted-foreground">{{ formatTime(notification.created_at) }}</span>
                        </div>
                        <p class="text-xs text-muted-foreground line-clamp-2">
                            {{ notification.message }}
                        </p>
                    </DropdownMenuItem>
                </div>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
