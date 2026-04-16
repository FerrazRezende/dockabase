<script setup>
import { computed } from 'vue';

const props = defineProps({
    collapsible: {
        type: Boolean,
        default: false,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const widthClass = computed(() => props.collapsed ? 'w-16' : 'w-64');
</script>

<template>
    <aside
        :class="[
            'fixed left-0 top-0 z-40 h-screen border-r border-border bg-card transition-all duration-300',
            widthClass
        ]"
    >
        <slot />
    </aside>
</template>
