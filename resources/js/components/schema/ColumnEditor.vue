<script setup lang="ts">
import { computed } from 'vue'
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
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { X, Key, HelpCircle } from 'lucide-vue-next'
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
      <label class="flex items-center gap-1.5 cursor-pointer select-none">
        <Checkbox
          :checked="column.isPrimaryKey"
          @update:checked="update('isPrimaryKey', $event)"
        />
        <span class="text-xs text-muted-foreground">{{ __('Primary Key') }}</span>
      </label>

      <!-- Default -->
      <div class="flex items-center gap-1.5 ml-auto">
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <span class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium flex items-center gap-0.5">
                {{ __('Default') }}
                <HelpCircle class="h-3 w-3 text-muted-foreground/50" />
              </span>
            </TooltipTrigger>
            <TooltipContent side="top" class="max-w-64">
              <p class="text-xs">{{ __('A raw SQL expression used as the default value. Examples: gen_random_uuid(), now(), 0, true.') }}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
        <Input
          :model-value="column.defaultValue ?? ''"
          placeholder="gen_random_uuid()"
          class="h-7 w-40 text-xs font-mono"
          @update:model-value="update('defaultValue', $event || null)"
        />
      </div>
    </div>
  </div>
</template>
