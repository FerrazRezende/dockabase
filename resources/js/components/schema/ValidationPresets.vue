<script setup lang="ts">
import { computed } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import type { PostgresType, ValidationPresetType } from '@/types/schema'

interface Props {
  columnName: string
  columnType: PostgresType
  modelValue: Record<string, boolean | number | string>
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: Record<string, boolean | number | string>]
}>()

const typeCategoryMap: Record<string, string> = {
  integer: 'numeric', bigint: 'numeric', decimal: 'numeric', real: 'numeric',
  varchar: 'text', text: 'text', char: 'text',
  boolean: 'boolean',
  timestamp: 'datetime', date: 'datetime', time: 'datetime',
  uuid: 'uuid',
  jsonb: 'json', json: 'json',
  text_array: 'array', integer_array: 'array', uuid_array: 'array',
  inet: 'network', cidr: 'network',
}

const category = computed(() => typeCategoryMap[props.columnType] ?? 'text')

interface PresetDef {
  type: ValidationPresetType
  label: string
  hasValue: boolean
  valuePlaceholder?: string
}

const allPresets: PresetDef[] = [
  { type: 'required', label: 'Required', hasValue: false },
  { type: 'min_length', label: 'Min Length', hasValue: true, valuePlaceholder: '3' },
  { type: 'max_length', label: 'Max Length', hasValue: true, valuePlaceholder: '255' },
  { type: 'min_value', label: 'Min Value', hasValue: true, valuePlaceholder: '0' },
  { type: 'max_value', label: 'Max Value', hasValue: true, valuePlaceholder: '999' },
  { type: 'integer', label: 'Must be integer', hasValue: false },
  { type: 'numeric', label: 'Must be numeric', hasValue: false },
  { type: 'regex', label: 'Regex', hasValue: true, valuePlaceholder: '/^[a-z]+$/' },
  { type: 'unique', label: 'Unique in table', hasValue: false },
  { type: 'exists', label: 'Exists in table', hasValue: true, valuePlaceholder: 'table,column' },
  { type: 'email', label: 'Must be email', hasValue: false },
  { type: 'url', label: 'Must be URL', hasValue: false },
  { type: 'uuid', label: 'Must be UUID', hasValue: false },
  { type: 'date', label: 'Must be date', hasValue: false },
  { type: 'boolean', label: 'Must be boolean', hasValue: false },
  { type: 'in_list', label: 'In list', hasValue: true, valuePlaceholder: 'a,b,c' },
  { type: 'alpha', label: 'Only letters', hasValue: false },
  { type: 'alpha_num', label: 'Letters + numbers', hasValue: false },
  { type: 'alpha_dash', label: 'Letters + dash/underscore', hasValue: false },
]

const categoryPresets: Record<string, ValidationPresetType[]> = {
  text: ['required', 'min_length', 'max_length', 'numeric', 'regex', 'unique', 'exists', 'email', 'url', 'uuid', 'in_list', 'alpha', 'alpha_num', 'alpha_dash'],
  numeric: ['required', 'min_value', 'max_value', 'integer', 'numeric', 'unique'],
  boolean: ['required', 'boolean'],
  datetime: ['required', 'date', 'unique'],
  uuid: ['required', 'unique'],
  json: ['required'],
  array: ['required'],
  network: ['required'],
}

const applicablePresets = computed(() => {
  const allowed = categoryPresets[category.value] ?? ['required']
  return allPresets.filter(p => allowed.includes(p.type))
})

const isEnabled = (type: ValidationPresetType): boolean => {
  const val = props.modelValue[type]
  return val !== undefined && val !== false && val !== null
}

const getValue = (type: ValidationPresetType): string => {
  const val = props.modelValue[type]
  if (val === true || val === undefined || val === null) return ''
  return String(val)
}

const toggle = (type: ValidationPresetType) => {
  const updated = { ...props.modelValue }
  if (isEnabled(type)) {
    delete updated[type]
  } else {
    const preset = allPresets.find(p => p.type === type)
    updated[type] = preset?.hasValue ? '' : true
  }
  emit('update:modelValue', updated)
}

const setValue = (type: ValidationPresetType, value: string) => {
  const updated = { ...props.modelValue }
  updated[type] = value
  emit('update:modelValue', updated)
}
</script>

<template>
  <div class="grid gap-1.5 sm:grid-cols-2">
    <button
      v-for="preset in applicablePresets"
      :key="preset.type"
      type="button"
      class="flex items-center gap-2.5 rounded-md px-3 py-2 text-left transition-all"
      :class="isEnabled(preset.type)
        ? 'bg-primary/10 border border-primary/30 hover:bg-primary/15'
        : 'bg-muted/30 border border-transparent hover:bg-muted/60'"
      @click="toggle(preset.type)"
    >
      <Checkbox
        :checked="isEnabled(preset.type)"
        class="pointer-events-none"
      />
      <span class="text-sm flex-1">{{ __(preset.label) }}</span>
      <Input
        v-if="preset.hasValue && isEnabled(preset.type)"
        :model-value="getValue(preset.type)"
        :placeholder="preset.valuePlaceholder"
        class="h-7 w-28 text-xs font-mono pointer-events-auto"
        @click.stop
        @update:model-value="setValue(preset.type, $event)"
      />
    </button>
  </div>
</template>
