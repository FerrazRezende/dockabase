<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Settings, Circle, Loader2 } from 'lucide-vue-next';
import { useUserStatus } from '@/composables/useUserStatus';
import type { UserStatus } from '@/types/user-status';
import { __ } from '@/utils/lang';

interface Props {
  avatarUrl?: string | null;
  userName?: string;
  initialStatus?: UserStatus;
  compact?: boolean;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  avatarUrl: null,
  userName: '',
  initialStatus: undefined,
  compact: false,
  disabled: false,
});

const emit = defineEmits<{
  'status-changed': [status: UserStatus];
  'error': [message: string];
}>();

const {
  currentStatus,
  isLoading,
  error,
  statusLabel,
  statusColor,
  statusBgColor,
  setStatus,
  initializeStatus,
} = useUserStatus();

if (props.initialStatus) {
  initializeStatus(props.initialStatus);
}

const isSubmitting = ref(false);

const initials = computed(() => {
  if (!props.userName) return '?';
  const parts = props.userName.trim().split(' ');
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
});

const statusOptions: Array<{
  value: UserStatus;
  label: string;
  colorClass: string;
  bgClass: string;
}> = [
  { value: 'online', label: 'user_status.online', colorClass: 'text-green-500', bgClass: 'bg-green-500' },
  { value: 'away', label: 'user_status.away', colorClass: 'text-yellow-500', bgClass: 'bg-yellow-500' },
  { value: 'busy', label: 'user_status.busy', colorClass: 'text-red-500', bgClass: 'bg-red-500' },
  { value: 'offline', label: 'user_status.offline', colorClass: 'text-gray-400', bgClass: 'bg-gray-400' },
];

const handleStatusChange = async (status: UserStatus) => {
  if (props.disabled || isSubmitting.value) return;

  isSubmitting.value = true;
  try {
    const success = await setStatus(status);
    if (success) {
      emit('status-changed', status);
    } else if (error.value) {
      emit('error', error.value);
    }
  } catch (err) {
    emit('error', err instanceof Error ? err.message : 'An error occurred');
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button
        :class="[
          'rounded-md hover:bg-accent transition-colors',
          compact
            ? 'relative flex items-center justify-center mx-auto p-1.5'
            : 'flex w-full items-center gap-2.5 px-2 py-1.5 text-left',
        ]"
        :disabled="disabled || isLoading"
      >
        <div class="relative shrink-0">
          <Avatar :class="compact ? 'size-8' : 'size-8'">
            <AvatarImage v-if="avatarUrl" :src="avatarUrl" :alt="userName" />
            <AvatarFallback class="text-xs">{{ initials }}</AvatarFallback>
          </Avatar>
          <span
            class="absolute -bottom-0.5 -right-0.5 flex size-3.5 items-center justify-center rounded-full bg-background"
          >
            <Loader2 v-if="isLoading" class="size-2.5 animate-spin text-muted-foreground" />
            <span v-else class="size-2.5 rounded-full" :class="statusBgColor" />
          </span>
        </div>
        <template v-if="!compact">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate text-foreground">{{ userName }}</p>
            <p class="text-xs truncate" :class="statusColor">{{ statusLabel }}</p>
          </div>
        </template>
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent :side="compact ? 'right' : 'right'" :align="'start'" class="w-48">
      <DropdownMenuSub>
        <DropdownMenuSubTrigger class="gap-2">
          <span class="size-2.5 rounded-full" :class="statusBgColor" />
          <span>{{ __('Status') }}</span>
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent>
          <DropdownMenuItem
            v-for="option in statusOptions"
            :key="option.value"
            @click="handleStatusChange(option.value)"
            :disabled="disabled || isSubmitting"
            class="cursor-pointer gap-2"
          >
            <Circle class="size-3 fill-current" :class="option.colorClass" />
            <span class="flex-1">{{ __(option.label) }}</span>
            <Loader2
              v-if="isSubmitting && currentStatus === option.value"
              class="size-4 animate-spin text-muted-foreground"
            />
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>
      <DropdownMenuSeparator />
      <DropdownMenuItem as-child>
        <Link :href="route('profile.edit')" class="flex items-center gap-2 cursor-pointer">
          <Settings class="size-4" />
          {{ __('Settings') }}
        </Link>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
