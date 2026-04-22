<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Plus } from 'lucide-vue-next'
import ColumnEditor from '@/components/schema/ColumnEditor.vue'
import type { ColumnDefinition, PostgresType } from '@/types/schema'

interface Props {
  modelValue: ColumnDefinition[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: ColumnDefinition[]]
}>()

const createDefaultColumn = (): ColumnDefinition => ({
  name: '',
  type: 'varchar' as PostgresType,
  length: 255,
  nullable: false,
  defaultValue: null,
  isPrimaryKey: false,
  foreignKey: null,
})

const addColumn = () => {
  emit('update:modelValue', [...props.modelValue, createDefaultColumn()])
}

const updateColumn = (index: number, column: ColumnDefinition) => {
  const updated = [...props.modelValue]
  updated[index] = column
  emit('update:modelValue', updated)
}

const removeColumn = (index: number) => {
  const updated = props.modelValue.filter((_, i) => i !== index)
  emit('update:modelValue', updated)
}
</script>

<template>
  <div>
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead class="text-xs">{{ __('Name') }}</TableHead>
          <TableHead class="text-xs">{{ __('Type') }}</TableHead>
          <TableHead class="text-xs w-20">{{ __('Length') }}</TableHead>
          <TableHead class="text-xs w-16 text-center">{{ __('Nullable') }}</TableHead>
          <TableHead class="text-xs">{{ __('Default') }}</TableHead>
          <TableHead class="text-xs w-16 text-center">{{ __('PK') }}</TableHead>
          <TableHead class="w-10" />
        </TableRow>
      </TableHeader>
      <TableBody>
        <ColumnEditor
          v-for="(column, index) in modelValue"
          :key="index"
          :column="column"
          :index="index"
          :can-remove="modelValue.length > 1"
          @update="updateColumn"
          @remove="removeColumn"
        />
      </TableBody>
    </Table>

    <Button variant="outline" size="sm" class="mt-3" @click="addColumn">
      <Plus class="h-3.5 w-3.5 mr-1.5" />
      {{ __('Add Column') }}
    </Button>
  </div>
</template>
