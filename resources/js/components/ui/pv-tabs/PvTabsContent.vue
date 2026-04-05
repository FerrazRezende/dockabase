<script setup lang="ts">
defineProps<{
    value: string;
    activeTab: string;
}>();
</script>

<template>
    <div v-show="activeTab === value" class=" animate-in fade-in-50 duration-200">
        <slot />
    </div>
</template>
