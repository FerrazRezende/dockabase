<script setup lang="ts">
import { ref, onMounted } from 'vue';
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
import { Bell, CheckCheck, Loader2 } from 'lucide-vue-next';
import type { Notification } from '@/types/notification';

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);

const fetchNotifications = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/notifications', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });

        if (response.ok) {
            const data = await response.json();
            notifications.value = data.data;
        }
    } catch (e) {
        console.error('Failed to fetch notifications:', e);
    } finally {
        loading.value = false;
    }
};

const fetchUnreadCount = async () => {
    try {
        const response = await fetch('/api/notifications/unread-count', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });

        if (response.ok) {
            const data = await response.json();
            unreadCount.value = data.count;
        }
    } catch (e) {
        console.error('Failed to fetch unread count:', e);
    }
};

const markAsRead = async (notificationId: number) => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        await fetch(`/api/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'include',
        });

        const notification = notifications.value.find(n => n.id === notificationId);
        if (notification) {
            notification.read = true;
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (e) {
        console.error('Failed to mark notification as read:', e);
    }
};

const markAllAsRead = async () => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        await fetch('/api/notifications/read-all', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'include',
        });

        notifications.value.forEach(n => n.read = true);
        unreadCount.value = 0;
    } catch (e) {
        console.error('Failed to mark all as read:', e);
    }
};

const formatTime = (dateString: string): string => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();

    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Agora';
    if (minutes < 60) return `${minutes}m atras`;
    if (hours < 24) return `${hours}h atras`;
    if (days < 7) return `${days}d atras`;

    return date.toLocaleDateString('pt-BR');
};

onMounted(() => {
    fetchNotifications();
    fetchUnreadCount();
});
</script>

<template>
    <DropdownMenu>
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
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notificacoes</span>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-auto py-1 px-2 text-xs"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="h-3 w-3 mr-1" />
                    Marcar todas
                </Button>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div v-if="loading" class="p-4 flex justify-center">
                <Loader2 class="h-5 w-5 animate-spin text-muted-foreground" />
            </div>

            <div v-else-if="notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                Nenhuma notificacao
            </div>

            <template v-else>
                <div class="max-h-64 overflow-y-auto">
                    <DropdownMenuItem
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex flex-col items-start gap-1 p-3 cursor-pointer"
                        :class="{ 'bg-muted/50': !notification.read }"
                        @click="!notification.read && markAsRead(notification.id)"
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
