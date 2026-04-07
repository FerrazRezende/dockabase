<script setup lang="ts">
import { computed } from 'vue';
import * as Icons from 'lucide-vue-next';

interface Tab {
    value: string;
    label: string;
    icon?: string;
    disabled?: boolean;
}

const props = defineProps<{
    tabs: Tab[];
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const activeTab = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const getIcon = (iconName: string) => {
    return (Icons as any)[iconName];
};
</script>

<template>
    <div class="w-full">
        <div class="relative border-b border-border">
            <ul class="flex gap-2" role="tablist">
                <li v-for="tab in tabs" :key="tab.value" class="relative">
                    <button
                        type="button"
                        class="relative px-4 py-3 text-sm font-medium transition-colors whitespace-nowrap flex items-center gap-2"
                        :class="[
                            activeTab === tab.value
                                ? 'text-foreground'
                                : 'text-muted-foreground hover:text-foreground',
                            tab.disabled && 'opacity-50 cursor-not-allowed',
                        ]"
                        :disabled="tab.disabled"
                        @click="!tab.disabled && (activeTab = tab.value)"
                    >
                        <component v-if="tab.icon" :is="getIcon(tab.icon)" class="w-4 h-4" />
                        {{ tab.label }}
                        <span
                            v-if="activeTab === tab.value"
                            class="absolute bottom-0 left-0 right-0 h-[2px] bg-primary"
                        />
                    </button>
                </li>
            </ul>
        </div>
        <div class="mt-4">
            <slot />
        </div>
    </div>
</template>
