<script setup lang="ts">
import { ref } from 'vue'
import { ChevronDown, ChevronRight } from 'lucide-vue-next'
import ValidationPresets from '@/components/schema/ValidationPresets.vue'
import type { ColumnDefinition } from '@/types/schema'

interface Props {
  columns: ColumnDefinition[]
  modelValue: Record<string, Record<string, boolean | number | string>>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: Record<string, Record<string, boolean | number | string>>]
}>()

const expandedColumns = ref<Set<string>>(new Set(props.columns.map(c => c.name)))

const toggleExpand = (name: string) => {
  const updated = new Set(expandedColumns.value)
  if (updated.has(name)) {
    updated.delete(name)
  } else {
    updated.add(name)
  }
  expandedColumns.value = updated
}

const updateColumnValidations = (columnName: string, presets: Record<string, boolean | number | string>) => {
  emit('update:modelValue', { ...props.modelValue, [columnName]: presets })
}
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="column in columns"
      :key="column.name"
      class="rounded-lg border"
    >
      <button
        class="flex items-center gap-2 w-full px-4 py-3 text-left hover:bg-accent/30 transition-colors"
        @click="toggleExpand(column.name)"
      >
        <ChevronDown v-if="expandedColumns.has(column.name)" class="h-4 w-4 text-muted-foreground" />
        <ChevronRight v-else class="h-4 w-4 text-muted-foreground" />
        <span class="text-sm font-medium">{{ column.name }}</span>
        <span class="text-xs text-muted-foreground">({{ column.type }})</span>
        <span v-if="modelValue[column.name] && Object.keys(modelValue[column.name]).length > 0" class="ml-auto text-xs text-primary">
          {{ Object.keys(modelValue[column.name]).length }} {{ __('rules') }}
        </span>
      </button>
      <div v-if="expandedColumns.has(column.name)" class="px-4 pb-4 pt-1 border-t">
        <ValidationPresets
          :column-name="column.name"
          :column-type="column.type"
          :model-value="modelValue[column.name] ?? {}"
          @update:model-value="updateColumnValidations(column.name, $event)"
        />
      </div>
    </div>

    <p v-if="columns.length === 0" class="text-sm text-muted-foreground text-center py-4">
      {{ __('No columns defined. Go back to Step 1 to add columns.') }}
    </p>
  </div>
</template>
