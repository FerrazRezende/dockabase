<script setup lang="ts">
import { computed } from 'vue'
import { Input } from '@/components/ui/input'
import type { ColumnDefinition, ValidationPresetType } from '@/types/schema'

interface Props {
  columns: ColumnDefinition[]
  validations: Record<string, Record<string, boolean | number | string>>
  modelValue: Record<string, Record<string, string>>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: Record<string, Record<string, string>>]
}>()

const presetLabels: Record<string, string> = {
  required: 'Required',
  min_length: 'Min Length',
  max_length: 'Max Length',
  min_value: 'Min Value',
  max_value: 'Max Value',
  integer: 'Integer',
  numeric: 'Numeric',
  regex: 'Regex',
  unique: 'Unique',
  exists: 'Exists',
  email: 'Email',
  url: 'URL',
  uuid: 'UUID',
  date: 'Date',
  boolean: 'Boolean',
  in_list: 'In List',
  alpha: 'Only letters',
  alpha_num: 'Letters + numbers',
  alpha_dash: 'Letters + dash/underscore',
}

interface ActiveRule {
  columnName: string
  presetType: ValidationPresetType
  label: string
}

const activeRules = computed(() => {
  const rules: ActiveRule[] = []
  for (const column of props.columns) {
    const colValidations = props.validations[column.name]
    if (!colValidations) continue
    for (const [presetType, value] of Object.entries(colValidations)) {
      if (value === false || value === null || value === undefined) continue
      rules.push({
        columnName: column.name,
        presetType: presetType as ValidationPresetType,
        label: presetLabels[presetType] ?? presetType,
      })
    }
  }
  return rules
})

const getMessage = (columnName: string, presetType: string): string => {
  return props.modelValue[columnName]?.[presetType] ?? ''
}

const setMessage = (columnName: string, presetType: string, message: string) => {
  const updated = { ...props.modelValue }
  if (!updated[columnName]) {
    updated[columnName] = {}
  }
  updated[columnName] = { ...updated[columnName], [presetType]: message }
  emit('update:modelValue', updated)
}
</script>

<template>
  <div class="space-y-3">
    <p v-if="activeRules.length === 0" class="text-sm text-muted-foreground text-center py-8">
      {{ __('No validations defined. Go back to Step 2 to add validation rules first.') }}
    </p>

    <div v-for="rule in activeRules" :key="`${rule.columnName}.${rule.presetType}`" class="space-y-1">
      <div class="flex items-center gap-2">
        <span class="text-xs font-medium">{{ rule.columnName }}</span>
        <span class="text-[10px] text-muted-foreground uppercase tracking-wider bg-muted px-1.5 py-0.5 rounded">{{ rule.label }}</span>
      </div>
      <Input
        :model-value="getMessage(rule.columnName, rule.presetType)"
        :placeholder="__('Custom error message (optional)')"
        class="h-8 text-sm"
        @update:model-value="setMessage(rule.columnName, rule.presetType, $event)"
      />
    </div>
  </div>
</template>
