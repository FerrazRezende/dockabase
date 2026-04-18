<script setup lang="ts">
import { computed } from 'vue'
import { ChevronRight, ChevronDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import TableTreeItem from '@/components/schema/TableTreeItem.vue'
import type { SchemaInfo } from '@/types/schema'

interface Props {
  schema: SchemaInfo
  expanded: boolean
  selectedSchema: string | null
  selectedTable: string | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  toggle: [schemaName: string]
  selectTable: [schema: string, table: string]
}>()

const sortedTables = computed(() => {
  return [...props.schema.tables].sort((a, b) => a.name.localeCompare(b.name))
})
</script>

<template>
  <div>
    <Button
      variant="ghost"
      size="sm"
      class="w-full justify-start font-normal"
      @click="emit('toggle', schema.name)"
    >
      <component :is="expanded ? ChevronDown : ChevronRight" class="h-4 w-4 mr-1" />
      {{ schema.name }}
    </Button>

    <div v-if="expanded" class="ml-4 mt-1 space-y-1">
      <TableTreeItem
        v-for="table in sortedTables"
        :key="`${schema.name}.${table.name}`"
        :schema="schema.name"
        :table="table"
        :selected="selectedSchema === schema.name && selectedTable === table.name"
        @select="emit('selectTable', schema.name, $event)"
      />
    </div>
  </div>
</template>
