<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { X, Key } from 'lucide-vue-next'
import type { ColumnDefinition, PostgresType } from '@/types/schema'

interface Props {
  column: ColumnDefinition
  index: number
  canRemove: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  update: [index: number, column: ColumnDefinition]
  remove: [index: number]
}>()

const typeGroups: Record<string, { label: string; types: { value: PostgresType; label: string }[] }> = {
  numeric: {
    label: 'Numeric',
    types: [
      { value: 'integer', label: 'Integer' },
      { value: 'bigint', label: 'Bigint' },
      { value: 'decimal', label: 'Decimal' },
      { value: 'real', label: 'Real' },
    ],
  },
  text: {
    label: 'Text',
    types: [
      { value: 'varchar', label: 'Varchar' },
      { value: 'text', label: 'Text' },
      { value: 'char', label: 'Char' },
    ],
  },
  boolean: {
    label: 'Boolean',
    types: [{ value: 'boolean', label: 'Boolean' }],
  },
  datetime: {
    label: 'Date/Time',
    types: [
      { value: 'timestamp', label: 'Timestamp' },
      { value: 'date', label: 'Date' },
      { value: 'time', label: 'Time' },
    ],
  },
  uuid: {
    label: 'UUID',
    types: [{ value: 'uuid', label: 'UUID' }],
  },
  json: {
    label: 'JSON',
    types: [
      { value: 'jsonb', label: 'JSONB' },
      { value: 'json', label: 'JSON' },
    ],
  },
  array: {
    label: 'Array',
    types: [
      { value: 'text_array', label: 'Text[]' },
      { value: 'integer_array', label: 'Integer[]' },
      { value: 'uuid_array', label: 'UUID[]' },
    ],
  },
  network: {
    label: 'Network',
    types: [
      { value: 'inet', label: 'INET' },
      { value: 'cidr', label: 'CIDR' },
    ],
  },
}

const needsLength = computed(() => props.column.type === 'varchar' || props.column.type === 'char')

const update = (field: keyof ColumnDefinition, value: unknown) => {
  emit('update', props.index, { ...props.column, [field]: value })
}

// Default value autocomplete
const defaultSuggestions: Record<string, { value: string; label: string; desc: string }[]> = {
  uuid: [
    { value: 'gen_random_uuid()', label: 'gen_random_uuid()', desc: 'Auto-generated UUID v4' },
  ],
  timestamp: [
    { value: 'now()', label: 'now()', desc: 'Current timestamp' },
    { value: "CURRENT_TIMESTAMP", label: 'CURRENT_TIMESTAMP', desc: 'SQL standard timestamp' },
  ],
  date: [
    { value: 'CURRENT_DATE', label: 'CURRENT_DATE', desc: 'Current date' },
    { value: 'now()', label: 'now()', desc: 'Current timestamp' },
  ],
  time: [
    { value: 'CURRENT_TIME', label: 'CURRENT_TIME', desc: 'Current time' },
  ],
  boolean: [
    { value: 'true', label: 'true', desc: 'Boolean true' },
    { value: 'false', label: 'false', desc: 'Boolean false' },
  ],
  integer: [
    { value: '0', label: '0', desc: 'Zero' },
    { value: '1', label: '1', desc: 'One' },
  ],
  bigint: [
    { value: '0', label: '0', desc: 'Zero' },
  ],
  decimal: [
    { value: '0', label: '0', desc: 'Zero' },
    { value: '0.0', label: '0.0', desc: 'Zero (decimal)' },
  ],
  real: [
    { value: '0', label: '0', desc: 'Zero' },
  ],
  varchar: [
    { value: "''", label: "'' (empty string)", desc: 'Empty string' },
  ],
  text: [
    { value: "''", label: "'' (empty string)", desc: 'Empty string' },
  ],
  jsonb: [
    { value: "'{}'", label: "'{}'", desc: 'Empty JSON object' },
    { value: "'[]'", label: "'[]'", desc: 'Empty JSON array' },
  ],
  json: [
    { value: "'{}'", label: "'{}'", desc: 'Empty JSON object' },
    { value: "'[]'", label: "'[]'", desc: 'Empty JSON array' },
  ],
}

const suggestionsForType = computed(() => {
  return defaultSuggestions[props.column.type] ?? []
})

const defaultInput = ref('')
const showSuggestions = ref(false)
const suggestionsRef = ref<HTMLElement | null>(null)
const inputRef = ref<HTMLElement | null>(null)

// Sync external value
watch(() => props.column.defaultValue, (val) => {
  defaultInput.value = val ?? ''
}, { immediate: true })

const filteredSuggestions = computed(() => {
  const q = defaultInput.value.toLowerCase().trim()
  if (!q) return suggestionsForType.value
  return suggestionsForType.value.filter(s =>
    s.value.toLowerCase().includes(q) || s.desc.toLowerCase().includes(q)
  )
})

const onDefaultInput = (value: string) => {
  defaultInput.value = value
  showSuggestions.value = true
  update('defaultValue', value || null)
}

const selectSuggestion = (value: string) => {
  defaultInput.value = value
  showSuggestions.value = false
  update('defaultValue', value)
}

const onDefaultFocus = () => {
  showSuggestions.value = true
}

const onDefaultBlur = () => {
  // Delay to allow click on suggestion
  setTimeout(() => {
    showSuggestions.value = false
  }, 150)
}
</script>

<template>
  <div class="group relative rounded-lg border bg-background p-3 transition-all hover:border-primary/30 hover:shadow-sm">
    <!-- Header row: name + type + badges + remove -->
    <div class="flex items-center gap-2 mb-2.5">
      <Input
        :model-value="column.name"
        placeholder="column_name"
        class="h-8 text-sm font-mono flex-1"
        @update:model-value="update('name', $event)"
      />
      <Select :model-value="column.type" @update:model-value="update('type', $event)">
        <SelectTrigger class="h-8 text-sm w-36">
          <SelectValue placeholder="Type" />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup v-for="(group, key) in typeGroups" :key="key">
            <SelectLabel>{{ group.label }}</SelectLabel>
            <SelectItem v-for="t in group.types" :key="t.value" :value="t.value">
              {{ t.label }}
            </SelectItem>
          </SelectGroup>
        </SelectContent>
      </Select>
      <Badge v-if="column.isPrimaryKey" variant="default" class="h-6 text-[10px] gap-1 shrink-0">
        <Key class="h-3 w-3" />
        PK
      </Badge>
      <Button
        v-if="canRemove"
        variant="ghost"
        size="icon"
        class="h-7 w-7 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-destructive"
        @click="emit('remove', index)"
      >
        <X class="h-3.5 w-3.5" />
      </Button>
    </div>

    <!-- Options row -->
    <div class="flex items-center gap-4 pl-0.5">
      <!-- Length (only for varchar/char) -->
      <div v-if="needsLength" class="flex items-center gap-1.5">
        <span class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium">{{ __('Length') }}</span>
        <Input
          :model-value="column.length ?? ''"
          type="number"
          placeholder="255"
          class="h-7 w-16 text-xs text-center"
          @update:model-value="update('length', $event ? Number($event) : null)"
        />
      </div>

      <!-- Nullable -->
      <label class="flex items-center gap-1.5 cursor-pointer select-none">
        <Checkbox
          :checked="column.nullable"
          @update:checked="update('nullable', $event)"
        />
        <span class="text-xs text-muted-foreground">{{ __('Nullable') }}</span>
      </label>

      <!-- Primary Key -->
      <label v-if="!column.isPrimaryKey" class="flex items-center gap-1.5 cursor-pointer select-none">
        <Checkbox
          :checked="false"
          @update:checked="update('isPrimaryKey', $event)"
        />
        <span class="text-xs text-muted-foreground">{{ __('Primary Key') }}</span>
      </label>

      <!-- Default (autocomplete) -->
      <div class="flex items-center gap-1.5 ml-auto relative">
        <span class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium">{{ __('Default') }}</span>
        <div class="relative">
          <Input
            ref="inputRef"
            :model-value="defaultInput"
            placeholder="gen_random_uuid()"
            class="h-7 w-40 text-xs font-mono"
            @update:model-value="onDefaultInput"
            @focus="onDefaultFocus"
            @blur="onDefaultBlur"
          />
          <div
            v-if="showSuggestions && filteredSuggestions.length > 0"
            ref="suggestionsRef"
            class="absolute z-50 top-full mt-1 w-56 rounded-md border bg-popover p-1 shadow-md"
          >
            <button
              v-for="s in filteredSuggestions"
              :key="s.value"
              type="button"
              class="flex items-center gap-2 w-full rounded-sm px-2 py-1.5 text-left text-xs hover:bg-accent transition-colors"
              @mousedown.prevent="selectSuggestion(s.value)"
            >
              <span class="font-mono font-medium">{{ s.label }}</span>
              <span class="text-muted-foreground ml-auto">{{ s.desc }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
