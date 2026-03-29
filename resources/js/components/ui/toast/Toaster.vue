<script setup lang="ts">
import { Toaster as Sonner, type ToasterProps } from 'sonner';

const props = withDefaults(defineProps<ToasterProps>(), {
  position: 'bottom-right',
  richColors: true,
  closeButton: true,
  duration: 4000,
});
</script>

<template>
  <Sonner v-bind="props" class="toaster group" />
</template>
