<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Link } from '@inertiajs/vue3';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import type { UserStatus } from '@/types/user-status';
import { __ } from '@/utils/lang';

interface Props {
  /** User ID for link generation */
  userId?: string;
  /** User's name for avatar fallback and alt text */
  userName?: string;
  /** Avatar image URL */
  avatarUrl?: string | null;
  /** Current user status */
  status?: UserStatus;
  /** Size of the avatar component */
  size?: 'sm' | 'md' | 'lg';
  /** Whether the avatar should be clickable/linkable */
  clickable?: boolean;
  /** Custom link URL (overrides default profile link) */
  link?: string | null;
  /** Show tooltip with status text */
  showTooltip?: boolean;
  /** Use rich tooltip component (default: true) */
  richTooltip?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  userId: '',
  userName: '',
  avatarUrl: null,
  status: 'offline',
  size: 'md',
  clickable: false,
  link: null,
  showTooltip: true,
  richTooltip: true,
});

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
 * Get avatar size based on size prop
 */
const avatarSize = computed(() => {
  const sizes = {
    sm: 'h-8 w-8',
    md: 'h-10 w-10',
    lg: 'h-12 w-12',
  };
  return sizes[props.size];
});

/**
 * Get status dot size based on avatar size
 */
const statusDotSize = computed(() => {
  const sizes = {
    sm: 'h-2 w-2',
    md: 'h-2.5 w-2.5',
    lg: 'h-3 w-3',
  };
  return sizes[props.size];
});

/**
 * Get status indicator background color
 */
const statusColorClass = computed(() => {
  const colors: Record<UserStatus, string> = {
    online: 'bg-green-500',
    away: 'bg-yellow-500',
    busy: 'bg-red-500',
    offline: 'bg-gray-400',
  };
  return colors[props.status] || colors.offline;
});

/**
 * Get status border color (for contrast with avatar)
 */
const statusBorderColorClass = computed(() => {
  return 'bg-white dark:bg-gray-900';
});

/**
 * Get translated status label for tooltip
 */
const statusLabel = computed(() => {
  return __('user_status.' + props.status);
});

/**
 * Get the link URL for clickable avatars
 */
const linkUrl = computed(() => {
  if (props.link) {
    return props.link;
  }
  if (props.clickable && props.userId) {
    return `/system/users/${props.userId}`;
  }
  return null;
});

/**
 * Get accessible alt text for the avatar
 */
const altText = computed(() => {
  if (props.userName) {
    return __('Avatar of :name', { name: props.userName });
  }
  return __('User avatar');
});

/**
 * Get tooltip text combining name and status
 */
const tooltipText = computed(() => {
  if (props.userName) {
    return `${props.userName} - ${statusLabel.value}`;
  }
  return statusLabel.value;
});
</script>

<template>
  <TooltipProvider v-if="showTooltip && richTooltip">
    <Tooltip>
      <TooltipTrigger as-child>
        <component
          :is="linkUrl ? Link : 'span'"
          :href="linkUrl || undefined"
          class="relative inline-block shrink-0"
          :class="{ 'cursor-pointer': linkUrl }"
          :title="!richTooltip ? tooltipText : undefined"
          :aria-label="altText"
        >
          <!-- Avatar -->
          <Avatar :class="avatarSize">
            <AvatarImage
              v-if="avatarUrl"
              :src="avatarUrl"
              :alt="altText"
            />
            <AvatarFallback class="text-sm font-medium">
              {{ initials }}
            </AvatarFallback>
          </Avatar>

          <!-- Status indicator dot -->
          <span
            class="absolute bottom-0 right-0 flex items-center justify-center rounded-full"
            :class="[statusDotSize, statusBorderColorClass]"
            :aria-hidden="true"
          >
            <span
              class="rounded-full"
              :class="[statusDotSize, statusColorClass]"
            />
          </span>

          <!-- Visually hidden status text for screen readers -->
          <span class="sr-only">
            {{ __('Status') }}: {{ statusLabel }}
          </span>
        </component>
      </TooltipTrigger>
      <TooltipContent>
        <p class="font-medium">{{ userName || __('User') }}</p>
        <p class="text-xs text-muted-foreground">{{ statusLabel }}</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>

  <!-- Simple version without rich tooltip -->
  <component
    v-else
    :is="linkUrl ? Link : 'span'"
    :href="linkUrl || undefined"
    class="relative inline-block shrink-0"
    :class="{ 'cursor-pointer': linkUrl }"
    :title="showTooltip ? tooltipText : undefined"
    :aria-label="altText"
  >
    <!-- Avatar -->
    <Avatar :class="avatarSize">
      <AvatarImage
        v-if="avatarUrl"
        :src="avatarUrl"
        :alt="altText"
      />
      <AvatarFallback class="text-sm font-medium">
        {{ initials }}
      </AvatarFallback>
    </Avatar>

    <!-- Status indicator dot -->
    <span
      class="absolute bottom-0 right-0 flex items-center justify-center rounded-full"
      :class="[statusDotSize, statusBorderColorClass]"
      :aria-hidden="true"
    >
      <span
        class="rounded-full"
        :class="[statusDotSize, statusColorClass]"
      />
    </span>

    <!-- Visually hidden status text for screen readers -->
    <span class="sr-only">
      {{ __('Status') }}: {{ statusLabel }}
    </span>
  </component>
</template>
