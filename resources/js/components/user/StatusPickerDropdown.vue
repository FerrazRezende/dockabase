<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Loader2, Circle, MoreHorizontal } from 'lucide-vue-next';
import { useUserStatus } from '@/composables/useUserStatus';
import type { UserStatus } from '@/types/user-status';

interface Props {
  /** Avatar URL to display */
  avatarUrl?: string | null;
  /** User's name for avatar fallback */
  userName?: string;
  /** Initial status override */
  initialStatus?: UserStatus;
  /** Show compact trigger button (avatar only) */
  compact?: boolean;
  /** Disable status changes */
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

// Initialize with prop if provided
if (props.initialStatus) {
  initializeStatus(props.initialStatus);
}

const customMessage = ref('');
const isSubmitting = ref(false);

/**
 * Get initials from user name for avatar fallback
 */
const initials = computed(() => {
  if (!props.userName) return '?';
  const parts = props.userName.trim().split(' ');
  if (parts.length === 1) {
    return parts[0].charAt(0).toUpperCase();
  }
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
});

/**
 * Available status options with their metadata
 */
const statusOptions: Array<{
  value: UserStatus;
  icon: typeof Circle;
  label: string;
  colorClass: string;
  bgClass: string;
}> = [
  {
    value: 'online',
    icon: Circle,
    label: 'user_status.online',
    colorClass: 'text-green-500',
    bgClass: 'bg-green-500',
  },
  {
    value: 'away',
    icon: Circle,
    label: 'user_status.away',
    colorClass: 'text-yellow-500',
    bgClass: 'bg-yellow-500',
  },
  {
    value: 'busy',
    icon: Circle,
    label: 'user_status.busy',
    colorClass: 'text-red-500',
    bgClass: 'bg-red-500',
  },
  {
    value: 'offline',
    icon: Circle,
    label: 'user_status.offline',
    colorClass: 'text-gray-400',
    bgClass: 'bg-gray-400',
  },
];

/**
 * Handle status selection
 */
const handleStatusChange = async (status: UserStatus) => {
  if (props.disabled || isSubmitting.value) return;

  isSubmitting.value = true;
  try {
    const success = await setStatus(status, customMessage.value || undefined);
    if (success) {
      emit('status-changed', status);
      customMessage.value = ''; // Clear message after successful set
    } else if (error.value) {
      emit('error', error.value);
    }
  } catch (err) {
    emit('error', err instanceof Error ? err.message : 'An error occurred');
  } finally {
    isSubmitting.value = false;
  }
};

/**
 * Get translated label for a status
 */
const getStatusLabel = (label: string): string => {
  return __(label);
};
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        variant="ghost"
        size="sm"
        class="relative h-9 gap-2 px-2"
        :disabled="disabled || isLoading"
        :aria-label="__('user_status.set_status')"
      >
        <!-- Avatar with status indicator -->
        <Avatar class="size-7">
          <AvatarImage v-if="avatarUrl" :src="avatarUrl" :alt="userName" />
          <AvatarFallback class="text-xs">
            {{ initials }}
          </AvatarFallback>
        </Avatar>

        <!-- Status indicator dot -->
        <span
          class="absolute bottom-0 right-0 flex size-3 items-center justify-center rounded-full bg-background"
          :class="{ 'right-7': !compact }"
        >
          <span
            v-if="!isLoading"
            class="size-2 rounded-full"
            :class="statusBgColor"
          />
          <Loader2 v-else class="size-2 animate-spin text-muted-foreground" />
        </span>

        <!-- Status label (hidden in compact mode) -->
        <span v-if="!compact" class="text-sm font-medium" :class="statusColor">
          {{ statusLabel }}
        </span>

        <MoreHorizontal v-if="!compact" class="size-4 text-muted-foreground" />
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" class="w-56">
      <DropdownMenuLabel>{{ __('user_status.set_status') }}</DropdownMenuLabel>
      <DropdownMenuSeparator />

      <!-- Status options -->
      <DropdownMenuItem
        v-for="option in statusOptions"
        :key="option.value"
        @click="handleStatusChange(option.value)"
        :disabled="disabled || isSubmitting || isLoading"
        class="cursor-pointer"
      >
        <option.icon class="size-4" :class="option.colorClass" />
        <span class="flex-1">{{ getStatusLabel(option.label) }}</span>
        <Loader2
          v-if="isSubmitting && currentStatus === option.value"
          class="size-4 animate-spin text-muted-foreground"
        />
      </DropdownMenuItem>

      <DropdownMenuSeparator />

      <!-- Optional status message -->
      <div class="px-2 py-1.5">
        <Input
          v-model="customMessage"
          type="text"
          :placeholder="__('user_status.set_status_message')"
          :disabled="disabled || isSubmitting"
          class="h-8 text-sm"
          @keydown.enter.prevent="handleStatusChange(currentStatus)"
        />
        <p v-if="customMessage" class="mt-1 text-xs text-muted-foreground">
          {{ __('Press Enter to set status') }}
        </p>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
