<script setup lang="ts">
import { Toaster as Sonner } from 'sonner';

const props = withDefaults(defineProps<{
  position?: 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left' | 'bottom-center' | 'top-center';
  richColors?: boolean;
  closeButton?: boolean;
  duration?: number;
}>(), {
  position: 'bottom-right',
  richColors: true,
  closeButton: true,
  duration: 4000,
});
</script>

<template>
  <Sonner
    :position="position"
    :rich-colors="richColors"
    :close-button="closeButton"
    :duration="duration"
    class="toaster group"
  />
</template>
