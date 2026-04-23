<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import type { ColumnInfo } from '@/types/schema'

interface Props {
  column: ColumnInfo
}

defineProps<Props>()

const getTypeColor = (type: string): string => {
  if (type.includes('int')) return 'bg-blue-500/10 text-blue-500'
  if (type.includes('char') || type.includes('text')) return 'bg-green-500/10 text-green-500'
  if (type.includes('bool')) return 'bg-purple-500/10 text-purple-500'
  if (type.includes('date') || type.includes('time')) return 'bg-orange-500/10 text-orange-500'
  return 'bg-gray-500/10 text-gray-500'
}
</script>

<template>
  <div class="flex items-center gap-1 text-xs">
    <span class="font-mono">{{ column.name }}</span>
    <Badge :class="getTypeColor(column.type)" variant="outline" class="text-[10px]">
      {{ column.type }}
    </Badge>
    <Badge v-if="column.isPrimaryKey" variant="default" class="text-[10px]">PK</Badge>
    <Badge v-if="column.isForeignKey" variant="secondary" class="text-[10px]">FK</Badge>
    <Badge v-if="column.nullable" variant="outline" class="text-[10px]">NULL</Badge>
    <Badge v-else variant="outline" class="text-[10px]">NOT NULL</Badge>
  </div>
</template>
