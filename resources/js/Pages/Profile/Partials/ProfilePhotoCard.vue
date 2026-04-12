<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Camera } from 'lucide-vue-next';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';
import ProfilePhotoDialog from './ProfilePhotoDialog.vue';

const page = usePage();
const user = computed(() => page.props.auth.user as { name: string; email: string; avatar?: string; bio?: string });

const isDialogOpen = ref(false);

const initials = computed(() => {
  const name = user.value.name || '';
  return name.slice(0, 2).toUpperCase();
});

const openDialog = () => {
  isDialogOpen.value = true;
};

const truncateBio = (bio: string, maxLength = 150) => {
  if (bio.length <= maxLength) return bio;
  return bio.slice(0, maxLength) + '...';
};
</script>

<template>
  <div class="max-w-xl mx-auto">
    <div
      class="cursor-pointer"
      @click="openDialog"
    >
      <div class="flex flex-col items-center justify-center space-y-4">
        <Avatar class="h-32 w-32 ring-4 ring-border hover:ring-primary/50 transition-all cursor-pointer">
          <AvatarImage
            v-if="user.avatar"
            :src="user.avatar"
            :alt="user.name"
          />
          <AvatarFallback class="bg-primary text-primary-foreground text-2xl font-semibold">
            {{ initials }}
          </AvatarFallback>
        </Avatar>

        <div class="flex flex-col items-center space-y-1">
          <div class="flex items-center space-x-2 text-sm font-medium text-foreground">
            <Camera :size="16" />
            <span>{{ __('Change photo') }}</span>
          </div>
          <p class="text-xs text-muted-foreground text-center max-w-xs">
            {{ __('Click to upload or change your profile photo') }}
          </p>
        </div>

        <div v-if="user.bio" class="text-sm text-muted-foreground text-center max-w-xs space-y-2">
          {{ truncateBio(user.bio) }}
        </div>
      </div>

      <ProfilePhotoDialog v-model:open="isDialogOpen" />
    </div>
  </div>
</template>
