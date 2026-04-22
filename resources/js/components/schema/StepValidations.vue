<script setup lang="ts">
import { ref, watch } from 'vue'
import { ChevronDown, ChevronRight } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
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

// Re-expand when columns change
watch(() => props.columns.map(c => c.name).join(','), (newKeys) => {
  const newNames = props.columns.map(c => c.name)
  expandedColumns.value = new Set(newNames.filter(n => expandedColumns.value.has(n) || true))
})

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

const ruleCount = (columnName: string): number => {
  const v = props.modelValue[columnName]
  if (!v) return 0
  return Object.values(v).filter(val => val !== false && val !== null && val !== undefined).length
}
</script>

<template>
  <div class="space-y-2">
    <div
      v-for="column in columns"
      :key="column.name"
      class="rounded-lg border overflow-hidden"
    >
      <button
        type="button"
        class="flex items-center gap-2 w-full px-4 py-2.5 text-left hover:bg-accent/50 transition-colors"
        @click="toggleExpand(column.name)"
      >
        <component :is="expandedColumns.has(column.name) ? ChevronDown : ChevronRight" class="h-4 w-4 text-muted-foreground shrink-0" />
        <span class="text-sm font-medium">{{ column.name }}</span>
        <Badge variant="secondary" class="text-[10px] h-5">{{ column.type }}</Badge>
        <Badge
          v-if="ruleCount(column.name) > 0"
          variant="default"
          class="text-[10px] h-5 ml-auto"
        >
          {{ ruleCount(column.name) }} {{ __('rules') }}
        </Badge>
      </button>
      <div v-if="expandedColumns.has(column.name)" class="px-4 pb-4 pt-2 border-t bg-muted/20">
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
