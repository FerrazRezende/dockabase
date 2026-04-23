<script setup lang="ts">
import { ref, watch } from 'vue'
import { ChevronDown, ChevronRight } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import ValidationPresets from '@/components/schema/ValidationPresets.vue'
import type { ColumnDefinition } from '@/types/schema'

interface Props {
  columns: ColumnDefinition[]
  modelValue: Record<string, Record<string, boolean | number | string>>
  messages: Record<string, Record<string, string>>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: Record<string, Record<string, boolean | number | string>>]
  'update:messages': [value: Record<string, Record<string, string>>]
}>()

const expandedColumns = ref<Set<string>>(new Set(props.columns.map(c => c.name)))

watch(() => props.columns.map(c => c.name).join(','), () => {
  expandedColumns.value = new Set(props.columns.map(c => c.name))
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

const updateMessage = (columnName: string, presetType: string, message: string) => {
  const updated = { ...props.messages }
  if (!updated[columnName]) {
    updated[columnName] = {}
  }
  updated[columnName] = { ...updated[columnName], [presetType]: message }
  emit('update:messages', updated)
}

const ruleCount = (columnName: string): number => {
  const v = props.modelValue[columnName]
  if (!v) return 0
  return Object.values(v).filter(val => val !== false && val !== null && val !== undefined).length
}

const activeRulesForColumn = (columnName: string): string[] => {
  const v = props.modelValue[columnName]
  if (!v) return []
  return Object.entries(v)
    .filter(([, val]) => val !== false && val !== null && val !== undefined)
    .map(([key]) => key)
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
      <div v-if="expandedColumns.has(column.name)" class="px-4 pb-4 pt-2 border-t bg-muted/20 space-y-3">
        <ValidationPresets
          :column-name="column.name"
          :column-type="column.type"
          :model-value="modelValue[column.name] ?? {}"
          @update:model-value="updateColumnValidations(column.name, $event)"
        />

        <!-- Message inputs for active rules -->
        <div v-if="activeRulesForColumn(column.name).length > 0" class="space-y-2 pt-2 border-t border-border/50">
          <p class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium">{{ __('Error Messages') }}</p>
          <div v-for="rule in activeRulesForColumn(column.name)" :key="rule" class="flex items-center gap-2">
            <Badge variant="outline" class="text-[10px] h-5 shrink-0">{{ rule }}</Badge>
            <Input
              :model-value="messages[column.name]?.[rule] ?? ''"
              :placeholder="__('Custom error message (optional)')"
              class="h-7 text-xs flex-1"
              @update:model-value="updateMessage(column.name, rule, $event)"
            />
          </div>
        </div>
      </div>
    </div>

    <p v-if="columns.length === 0" class="text-sm text-muted-foreground text-center py-4">
      {{ __('No columns defined. Go back to Step 1 to add columns.') }}
    </p>
  </div>
</template>
