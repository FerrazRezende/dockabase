<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  Loader2,
  ChevronDown,
  Eye,
  CircleDot,
  Database,
  KeyRound,
  Clock,
  ArrowRightLeft,
  FileText,
} from 'lucide-vue-next';
import type { UserActivity, UserActivityType } from '@/types/user-status';
import { __ } from '@/utils/lang';

interface Props {
  userId?: string;
  activities: UserActivity[];
  loading?: boolean;
  hasMorePages?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  hasMorePages: false,
});

const emit = defineEmits<{
  loadMore: [];
}>();

const getActivityIcon = (type: UserActivityType) => {
  const icons: Record<UserActivityType, typeof Eye> = {
    status_changed: ArrowRightLeft,
    database_created: Database,
    credential_created: KeyRound,
    page_view: Eye,
  };
  return icons[type] || FileText;
};

const getActivityColor = (type: UserActivityType): string => {
  const colors: Record<UserActivityType, string> = {
    status_changed: 'text-blue-500 bg-blue-500 border-blue-500',
    database_created: 'text-emerald-500 bg-emerald-500 border-emerald-500',
    credential_created: 'text-violet-500 bg-violet-500 border-violet-500',
    page_view: 'text-slate-400 bg-slate-400 border-slate-400',
  };
  return colors[type] || 'text-slate-400 bg-slate-400 border-slate-400';
};

const formatRelativeTime = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

  if (seconds < 60) {
    return __('Just now');
  }

  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) {
    return __(':count minute ago | :count minutes ago', { count: minutes });
  }

  const hours = Math.floor(minutes / 60);
  if (hours < 24) {
    return __(':count hour ago | :count hours ago', { count: hours });
  }

  const days = Math.floor(hours / 24);
  if (days < 7) {
    return __(':count day ago | :count days ago', { count: days });
  }

  const weeks = Math.floor(days / 7);
  if (weeks < 4) {
    return __(':count week ago | :count weeks ago', { count: weeks });
  }

  const months = Math.floor(days / 30);
  if (months < 12) {
    return __(':count month ago | :count months ago', { count: months });
  }

  const years = Math.floor(days / 365);
  return __(':count year ago | :count years ago', { count: years });
};

const getActivityLabel = (activity: UserActivity): string => {
  const labels: Record<UserActivityType, string> = {
    status_changed: __('user_activity.status_changed'),
    database_created: __('user_activity.database_created'),
    credential_created: __('Credential created'),
    page_view: __('user_activity.page_view'),
  };

  const baseLabel = labels[activity.activity_type] || activity.activity_type;

  if (activity.metadata) {
    if (activity.activity_type === 'database_created' && activity.metadata.database_name) {
      return `${baseLabel}: ${activity.metadata.database_name}`;
    }
    if (activity.activity_type === 'credential_created' && activity.metadata.credential_name) {
      return `${baseLabel}: ${activity.metadata.credential_name}`;
    }
    if (activity.activity_type === 'page_view' && activity.metadata.path) {
      return `${baseLabel} ${activity.metadata.path}`;
    }
  }

  if (activity.activity_type === 'status_changed' && activity.from_status && activity.to_status) {
    const fromLabel = __(activity.from_status);
    const toLabel = __(activity.to_status);
    return `${baseLabel}: ${fromLabel} \u2192 ${toLabel}`;
  }

  return baseLabel;
};

const sortedActivities = computed(() => {
  return [...props.activities].sort((a, b) => {
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
  });
});

const showEmptyState = computed(() => {
  return !props.loading && props.activities.length === 0;
});

const showLoadMore = computed(() => {
  return props.hasMorePages && !props.loading;
});
</script>

<template>
  <div class="w-full">
    <!-- Loading State -->
    <div
      v-if="loading && activities.length === 0"
      class="flex flex-col items-center justify-center py-12"
    >
      <Loader2 class="h-8 w-8 animate-spin text-primary mb-4" />
      <p class="text-sm text-muted-foreground">{{ __('Loading...') }}</p>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="showEmptyState"
      class="flex flex-col items-center justify-center py-12"
    >
      <Clock class="h-12 w-12 text-muted-foreground/50 mb-4" />
      <p class="text-lg font-medium mb-2">{{ __('user_status.activity_log') }}</p>
      <p class="text-sm text-muted-foreground">{{ __('user_status.no_recent_activity') }}</p>
    </div>

    <!-- Timeline -->
    <div v-else class="relative">
      <!-- Vertical Line -->
      <div
        class="absolute left-[19px] top-3 bottom-3 w-px bg-border"
        v-if="sortedActivities.length > 0"
      />

      <!-- Activities -->
      <div class="space-y-4">
        <div
          v-for="activity in sortedActivities"
          :key="activity.id"
          class="relative flex gap-4"
        >
          <!-- Icon node - solid bg to cover the line -->
          <div
            :class="[
              'relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 bg-card',
              getActivityColor(activity.activity_type)
            ]"
          >
            <component
              :is="getActivityIcon(activity.activity_type)"
              class="h-4.5 w-4.5"
            />
          </div>

          <!-- Content -->
          <Card class="flex-1">
            <CardContent class="p-4">
              <div class="space-y-1">
                <p class="text-sm font-medium leading-none">
                  {{ getActivityLabel(activity) }}
                </p>
                <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                  <Clock class="h-3 w-3" />
                  {{ formatRelativeTime(activity.created_at) }}
                </div>
              </div>

              <!-- Additional Details -->
              <div
                v-if="activity.metadata && Object.keys(activity.metadata).length > 0"
                class="mt-3 pt-3 border-t"
              >
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="(value, key) in activity.metadata"
                    :key="key"
                    class="inline-flex items-center rounded-md bg-muted px-2 py-1 text-xs font-medium"
                  >
                    <span class="text-muted-foreground mr-1">{{ key }}:</span>
                    <span>{{ value }}</span>
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Load More Button -->
      <div
        v-if="showLoadMore"
        class="mt-6 flex justify-center"
      >
        <Button
          variant="outline"
          size="sm"
          @click="emit('loadMore')"
          :disabled="loading"
        >
          <Loader2
            v-if="loading"
            class="h-4 w-4 animate-spin mr-2"
          />
          <ChevronDown
            v-else
            class="h-4 w-4 mr-2"
          />
          {{ __('Load more') }}
        </Button>
      </div>

      <!-- Loading More Indicator -->
      <div
        v-if="loading && activities.length > 0"
        class="mt-4 flex justify-center"
      >
        <Loader2 class="h-6 w-6 animate-spin text-primary" />
      </div>
    </div>
  </div>
</template>
