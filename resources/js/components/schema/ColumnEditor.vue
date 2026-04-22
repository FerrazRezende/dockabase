<script setup lang="ts">
import { computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { X } from 'lucide-vue-next'
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
</script>

<template>
  <tr class="group border-b last:border-b-0 hover:bg-accent/30 transition-colors">
    <td class="py-2 px-3">
      <Input
        :model-value="column.name"
        placeholder="column_name"
        class="h-8 text-sm font-mono"
        @update:model-value="update('name', $event)"
      />
    </td>
    <td class="py-2 px-3">
      <Select :model-value="column.type" @update:model-value="update('type', $event)">
        <SelectTrigger class="h-8 text-sm">
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
    </td>
    <td class="py-2 px-3">
      <Input
        v-if="needsLength"
        :model-value="column.length ?? ''"
        type="number"
        placeholder="255"
        class="h-8 w-20 text-sm"
        @update:model-value="update('length', $event ? Number($event) : null)"
      />
      <span v-else class="text-xs text-muted-foreground">—</span>
    </td>
    <td class="py-2 px-3 text-center">
      <Checkbox
        :checked="column.nullable"
        @update:checked="update('nullable', $event)"
      />
    </td>
    <td class="py-2 px-3">
      <Input
        :model-value="column.defaultValue ?? ''"
        placeholder="gen_random_uuid()"
        class="h-8 text-sm font-mono"
        @update:model-value="update('defaultValue', $event || null)"
      />
    </td>
    <td class="py-2 px-3 text-center">
      <Checkbox
        :checked="column.isPrimaryKey"
        @update:checked="update('isPrimaryKey', $event)"
      />
    </td>
    <td class="py-2 px-3 w-10">
      <Button
        v-if="canRemove"
        variant="ghost"
        size="icon"
        class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity"
        @click="emit('remove', index)"
      >
        <X class="h-3.5 w-3.5 text-destructive" />
      </Button>
    </td>
  </tr>
</template>
