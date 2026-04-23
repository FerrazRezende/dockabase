<script setup lang="ts">
import { computed } from 'vue'
import { ChevronRight, ChevronDown, Table as TableIcon } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import ColumnBadge from '@/components/schema/ColumnBadge.vue'
import type { TableInfo } from '@/types/schema'

interface Props {
  schema: string
  table: TableInfo
  selected: boolean
  expanded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  expanded: false,
})

const emit = defineEmits<{
  select: [tableName: string]
  toggle: []
}>()

const tableKey = computed(() => `${props.schema}.${props.table.name}`)
</script>

<template>
  <div>
    <div class="flex items-center">
      <Button
        variant="ghost"
        size="sm"
        class="flex-1 justify-start font-normal"
        :class="{ 'bg-accent': selected }"
        @click="emit('select', table.name)"
      >
        <TableIcon class="h-4 w-4 mr-2" />
        {{ table.name }}
        <span class="ml-auto text-xs text-muted-foreground">({{ table.columns.length }})</span>
      </Button>

      <Button
        variant="ghost"
        size="icon"
        class="h-6 w-6"
        @click="emit('toggle')"
      >
        <component :is="expanded ? ChevronDown : ChevronRight" class="h-3 w-3" />
      </Button>
    </div>

    <div v-if="expanded" class="ml-6 mt-1 space-y-1">
      <div v-for="column in table.columns" :key="column.name" class="flex items-center text-sm">
        <ColumnBadge :column="column" />
      </div>
    </div>
  </div>
</template>
