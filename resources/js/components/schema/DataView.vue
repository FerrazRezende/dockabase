<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from '@inertiajs/vue3'
import type { TableDataResponse } from '@/types/schema'
import { Search, ArrowUpDown, Download } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

interface Props {
  databaseId: string
  loading: boolean
  dataView: TableDataResponse | null
}

const props = defineProps<Props>()

const router = useRouter()
const searchInput = ref('')
const sortBy = ref<string | null>(null)
const sortDir = ref<'asc' | 'desc'>('asc')

const handleSort = (column: string) => {
  if (sortBy.value === column) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortDir.value = 'asc'
  }

  router.get(route('app.databases.tables.data', {
    database: props.databaseId,
    schema: props.dataView?.schema,
    table: props.dataView?.table,
    sort_by: sortBy.value,
    sort_dir: sortDir.value,
  }))
}

const handleSearch = () => {
  router.get(route('app.databases.tables.data', {
    database: props.databaseId,
    schema: props.dataView?.schema,
    table: props.dataView?.table,
    search: searchInput.value || undefined,
  }))
}
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- Toolbar -->
    <div class="border-b p-4 flex items-center gap-4">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          v-model="searchInput"
          type="text"
          :placeholder="__('Search...')"
          class="pl-9"
          @keyup.enter="handleSearch"
        />
      </div>
      <Button variant="outline" size="sm" @click="handleSearch">
        <Search class="h-4 w-4 mr-2" />
        {{ __('Search') }}
      </Button>
      <Button variant="outline" size="sm" @click="$emit('export')">
        <Download class="h-4 w-4 mr-2" />
        {{ __('Export CSV') }}
      </Button>
    </div>

    <!-- Table -->
    <div class="flex-1 overflow-auto p-4">
      <div v-if="loading" class="text-center text-muted-foreground">
        {{ __('Loading...') }}
      </div>

      <Table v-else-if="dataView && dataView.rows.length > 0">
        <TableHeader>
          <TableRow>
            <TableHead
              v-for="column in dataView.columns"
              :key="column"
              class="cursor-pointer hover:bg-accent"
              @click="handleSort(column)"
            >
              <div class="flex items-center">
                {{ column }}
                <ArrowUpDown
                  v-if="sortBy === column"
                  class="h-3 w-3 ml-1"
                  :class="{ 'rotate-180': sortDir === 'desc' }"
                />
              </div>
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="(row, idx) in dataView.rows" :key="idx">
            <TableCell v-for="column in dataView.columns" :key="column">
              {{ String(row[column] ?? '') }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <div v-else class="text-center text-muted-foreground">
        {{ __('No data available') }}
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="dataView" class="border-t p-4 flex items-center justify-between text-sm">
      <div>
        {{ __('Showing :from to :to of :total records', {
          from: (dataView.pagination.page - 1) * dataView.pagination.perPage + 1,
          to: Math.min(dataView.pagination.page * dataView.pagination.perPage, dataView.pagination.totalRows),
          total: dataView.pagination.totalRows,
        }) }}
      </div>
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="dataView.pagination.page <= 1"
          @click="router.get(route('app.databases.tables.data', {
            database: databaseId,
            schema: dataView.schema,
            table: dataView.table,
            page: dataView.pagination.page - 1,
          }))"
        >
          {{ __('Previous') }}
        </Button>
        <span>{{ dataView.pagination.page }} / {{ dataView.pagination.totalPages }}</span>
        <Button
          variant="outline"
          size="sm"
          :disabled="dataView.pagination.page >= dataView.pagination.totalPages"
          @click="router.get(route('app.databases.tables.data', {
            database: databaseId,
            schema: dataView.schema,
            table: dataView.table,
            page: dataView.pagination.page + 1,
          }))"
        >
          {{ __('Next') }}
        </Button>
      </div>
    </div>
  </div>
</template>
