<script setup lang="ts">
import { Button } from '@/components/ui/button'
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
  <div class="space-y-3">
    <!-- Column cards instead of table -->
    <div class="space-y-2">
      <ColumnEditor
        v-for="(column, index) in modelValue"
        :key="index"
        :column="column"
        :index="index"
        :can-remove="modelValue.length > 1"
        @update="updateColumn"
        @remove="removeColumn"
      />
    </div>

    <Button variant="outline" size="sm" class="w-full" @click="addColumn">
      <Plus class="h-3.5 w-3.5 mr-1.5" />
      {{ __('Add Column') }}
    </Button>
  </div>
</template>
