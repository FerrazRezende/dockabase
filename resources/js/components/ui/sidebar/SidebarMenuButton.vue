<script setup>
import { computed } from 'vue';

const props = defineProps({
    active: {
        type: Boolean,
        default: false,
    },
    as: {
        type: String,
        default: 'button',
    },
    href: {
        type: String,
        default: null,
    },
});

const componentTag = computed(() => props.as);

const classes = computed(() => [
    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
    props.active
        ? 'bg-primary text-primary-foreground'
        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
]);
</script>

<template>
    <component
        :is="componentTag"
        :href="href"
        :class="classes"
    >
        <slot />
    </component>
</template>
