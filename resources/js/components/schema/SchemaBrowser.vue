<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useSchemaBrowser } from '@/composables/useSchemaBrowser'
import { usePermissions } from '@/composables/usePermissions'
import { useToast } from 'vue-toastification'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  FolderOpen,
  Folder,
  Table as TableIcon,
  ChevronLeft,
  Search,
  Loader2,
  ArrowUpDown,
  Columns3,
  TableProperties,
  Plus,
} from 'lucide-vue-next'
import axios from 'axios'
import CreateTableWizard from '@/components/schema/CreateTableWizard.vue'

interface Props {
  databaseId: string
}

const props = defineProps<Props>()

const {
  schemas,
  selectedSchema,
  selectedTable,
  loading,
  dataView,
  dataLoading,
  loadSchemas,
  selectTable,
  search,
  sortBy,
  sortDir,
  loadTableData,
  page,
  perPage,
} = useSchemaBrowser(props.databaseId)

const toast = useToast()
const { canEdit } = usePermissions()

const view = ref<'folders' | 'browser' | 'create-table'>('folders')
const creatingForSchema = ref<string | null>(null)
const searchInput = ref('')
const createDialogOpen = ref(false)
const newSchemaName = ref('')
const creating = ref(false)

const currentSchemaTables = computed(() => {
  const schema = schemas.value.find(s => s.name === selectedSchema.value)
  return schema?.tables || []
})

const openSchema = (schemaName: string) => {
  selectedSchema.value = schemaName
  view.value = 'browser'
}

const goBack = () => {
  view.value = 'folders'
  selectedSchema.value = null
  selectedTable.value = null
}

const handleSelectTable = async (schema: string, table: string) => {
  await selectTable(schema, table)
}

const handleSort = (column: string) => {
  if (sortBy.value === column) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortDir.value = 'asc'
  }
  if (selectedSchema.value && selectedTable.value) {
    loadTableData()
  }
}

const createSchema = async () => {
  if (!newSchemaName.value.trim()) return
  creating.value = true
  try {
    await axios.post(route('app.databases.schemas.store', props.databaseId), {
      name: newSchemaName.value.trim(),
    })
    createDialogOpen.value = false
    newSchemaName.value = ''
    await loadSchemas()
    toast.success(__('Schema created successfully'))
  } catch (error: any) {
    toast.error(error.response?.data?.message || __('Failed to create schema'))
  } finally {
    creating.value = false
  }
}

const openCreateTable = (schemaName?: string) => {
  creatingForSchema.value = schemaName ?? null
  view.value = 'create-table'
}

const handleTableCreated = async () => {
  view.value = 'folders'
  creatingForSchema.value = null
  await loadSchemas()
}

const cancelCreateTable = () => {
  view.value = 'folders'
  creatingForSchema.value = null
}

onMounted(() => {
  loadSchemas()
})
</script>

<template>
  <!-- FOLDERS VIEW: Schema cards -->
  <div v-if="view === 'folders'">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold">{{ __('Schemas') }}</h3>
        <p class="text-sm text-muted-foreground">{{ schemas.length }} {{ schemas.length === 1 ? __('schema') : __('schemas') }} {{ __('in this database') }}</p>
      </div>
      <Button v-if="canEdit('databases')" @click="createDialogOpen = true">
        <Plus class="h-4 w-4 mr-2" />
        {{ __('New Schema') }}
      </Button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="schemas.length === 0" class="flex flex-col items-center justify-center py-20 text-muted-foreground gap-3">
      <Folder class="h-12 w-12 opacity-20" />
      <p class="text-sm">{{ __('No schemas found in this database') }}</p>
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <button
        v-for="schema in schemas"
        :key="schema.name"
        class="group relative overflow-hidden rounded-xl border bg-card p-0 text-left transition-all hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5 hover:-translate-y-0.5 cursor-pointer"
        @click="openSchema(schema.name)"
      >
        <div class="p-5">
          <div class="flex items-start gap-4 mb-4">
            <div class="relative">
              <div class="absolute inset-0 bg-primary/20 blur-xl rounded-full group-hover:blur-2xl transition-all" />
              <div class="relative flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-primary/20 to-primary/5 ring-1 ring-primary/20 group-hover:ring-primary/40 transition-all">
                <FolderOpen class="h-6 w-6 text-primary" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-base truncate">{{ schema.name }}</h3>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-xs text-muted-foreground">
                  {{ schema.tables.length }} {{ schema.tables.length === 1 ? __('table') : __('tables') }}
                </span>
                <span class="text-xs text-muted-foreground/40">•</span>
                <span class="text-xs text-primary/80 font-medium">
                  {{ schema.tables.reduce((sum, t) => sum + (t.rowCount || 0), 0).toLocaleString() }} {{ __('rows') }}
                </span>
              </div>
            </div>
          </div>

          <div v-if="schema.tables.length > 0" class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-[10px] text-muted-foreground uppercase tracking-wider font-medium">
              <TableIcon class="h-3 w-3" />
              {{ __('Tables') }}
            </div>
            <div class="flex flex-col gap-1">
              <div
                v-for="table in schema.tables.slice(0, 3)"
                :key="table.name"
                class="flex items-center justify-between rounded-lg bg-muted/40 px-3 py-1.5 group/table hover:bg-primary/10 transition-colors"
              >
                <div class="flex items-center gap-2 min-w-0">
                  <div class="h-1.5 w-1.5 rounded-full bg-primary/60 group-hover/table:bg-primary transition-colors" />
                  <span class="text-xs font-medium truncate">{{ table.name }}</span>
                </div>
                <span class="text-[10px] text-muted-foreground font-mono">{{ table.rowCount?.toLocaleString() ?? '0' }}</span>
              </div>
              <div
                v-if="schema.tables.length > 3"
                class="flex items-center justify-center rounded-lg bg-muted/30 px-3 py-1.5 text-xs text-muted-foreground hover:bg-primary/5 transition-colors"
              >
                <span class="text-primary/70">+{{ schema.tables.length - 3 }} {{ __('more tables') }}</span>
              </div>
            </div>
          </div>

          <div v-else class="flex items-center justify-center rounded-lg bg-muted/30 py-3 text-xs text-muted-foreground">
            {{ __('No tables yet') }}
          </div>
        </div>

        <div class="mt-3 pt-3 border-t" v-if="canEdit('databases')">
          <Button
            variant="ghost"
            size="sm"
            class="w-full text-xs"
            @click.stop="openCreateTable(schema.name)"
          >
            <Plus class="h-3 w-3 mr-1" />
            {{ __('New Table') }}
          </Button>
        </div>

        <div class="absolute inset-0 bg-gradient-to-tr from-primary/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
      </button>
    </div>
  </div>

  <!-- BROWSER VIEW: Table sidebar + data - FIXED HEIGHT, NO PAGE SCROLL -->
  <div v-else-if="view === 'browser'" class="flex rounded-lg border bg-card" style="height: calc(100vh - 14rem); min-height: 500px;">
    <!-- Left Sidebar: Tables - FIXED, no scroll on page -->
    <div class="w-60 border-r flex flex-col shrink-0">
      <div class="p-3 border-b">
        <button
          class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors w-full"
          @click="goBack"
        >
          <ChevronLeft class="h-4 w-4" />
          {{ __('All schemas') }}
        </button>
      </div>
      <div class="px-3 py-2.5 border-b">
        <div class="flex items-center gap-2">
          <FolderOpen class="h-4 w-4 text-primary" />
          <span class="text-sm font-semibold">{{ selectedSchema }}</span>
          <Badge variant="secondary" class="ml-auto text-[10px] h-5">
            {{ currentSchemaTables.length }}
          </Badge>
        </div>
      </div>
      <div v-if="canEdit('databases')" class="px-3 pb-2">
        <Button variant="ghost" size="sm" class="w-full" @click="openCreateTable(selectedSchema!)">
          <Plus class="h-3.5 w-3.5 mr-1.5" />
          {{ __('New Table') }}
        </Button>
      </div>

      <div class="flex-1 overflow-y-auto">
        <div class="p-2 space-y-0.5">
          <button
            v-for="table in currentSchemaTables"
            :key="table.name"
            class="w-full flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors text-left"
            :class="selectedTable === table.name
              ? 'bg-primary/10 text-primary font-medium'
              : 'hover:bg-accent'"
            @click="handleSelectTable(selectedSchema!, table.name)"
          >
            <TableIcon class="h-3.5 w-3.5 shrink-0 opacity-60" />
            <span class="truncate">{{ table.name }}</span>
            <span class="ml-auto text-[10px] text-muted-foreground font-mono">
              {{ table.rowCount }}
            </span>
          </button>

          <div v-if="currentSchemaTables.length === 0" class="text-center py-8 text-muted-foreground text-xs">
            {{ __('No tables found') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Area - THIS is the scroll container -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- No table selected -->
      <div v-if="!selectedTable" class="flex-1 flex flex-col items-center justify-center text-muted-foreground gap-3">
        <TableProperties class="h-10 w-10 opacity-20" />
        <p class="text-sm">{{ __('Select a table to view its data') }}</p>
      </div>

      <!-- Table data view -->
      <template v-else>
        <!-- Toolbar - fixed at top -->
        <div class="flex items-center gap-3 px-4 py-3 border-b shrink-0">
          <div class="flex items-center gap-2 min-w-0">
            <TableIcon class="h-4 w-4 text-muted-foreground shrink-0" />
            <h3 class="text-sm font-semibold truncate">{{ selectedTable }}</h3>
            <Badge variant="outline" class="text-[10px] h-5 shrink-0">
              <Columns3 class="h-3 w-3 mr-1" />
              {{ dataView?.columns?.length ?? 0 }} {{ __('cols') }}
            </Badge>
          </div>
          <div class="ml-auto">
            <div class="relative w-52">
              <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
              <Input
                v-model="searchInput"
                :placeholder="__('Search...')"
                class="h-8 pl-8 text-sm"
                @keyup.enter="search = searchInput; loadTableData()"
              />
            </div>
          </div>
        </div>

        <!-- SCROLL CONTAINER - only this panel scrolls -->
        <div class="flex-1 overflow-auto">
          <div v-if="dataLoading" class="flex items-center justify-center h-40">
            <Loader2 class="h-5 w-5 animate-spin text-muted-foreground" />
          </div>

          <div v-else-if="dataView" style="overflow-x: auto; overflow-y: visible;">
            <table class="w-max border-collapse">
              <thead>
                <tr>
                  <th
                    v-for="column in dataView.columns"
                    :key="column"
                    class="sticky top-0 z-10 cursor-pointer hover:bg-accent/50 text-xs h-9 px-3 text-left font-medium border-r last:border-r-0 whitespace-nowrap bg-muted/50"
                    @click="handleSort(column)"
                  >
                    <div class="flex items-center gap-1">
                      {{ column }}
                      <ArrowUpDown
                        v-if="sortBy === column"
                        class="h-3 w-3 shrink-0"
                        :class="{ 'rotate-180': sortDir === 'desc' }"
                      />
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in dataView.rows" :key="idx" class="hover:bg-accent/30">
                  <td
                    v-for="column in dataView.columns"
                    :key="column"
                    class="text-sm py-2 px-3 border-r last:border-r-0 whitespace-nowrap"
                  >
                    <span v-if="column === 'id'" class="font-mono text-xs text-muted-foreground">{{ row[column] }}</span>
                    <Badge v-else-if="column === 'role' || column === 'status'" variant="secondary" class="text-[10px] h-5">{{ row[column] }}</Badge>
                    <span v-else :title="String(row[column])">{{ row[column] }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination - fixed at bottom -->
        <div class="flex items-center justify-between px-4 py-2.5 border-t text-xs text-muted-foreground shrink-0">
          <span>
            {{ __('Showing :from to :to of :total rows', {
              from: (page - 1) * perPage + 1,
              to: Math.min(page * perPage, dataView?.totalRows ?? 0),
              total: dataView?.totalRows ?? 0,
            }) }}
          </span>
          <div class="flex items-center gap-1">
            <Button variant="outline" size="sm" class="h-7 text-xs" :disabled="page <= 1" @click="page--; loadTableData()">
              {{ __('Previous') }}
            </Button>
            <span class="px-2">{{ page }}</span>
            <Button variant="outline" size="sm" class="h-7 text-xs" :disabled="!dataView || page >= Math.ceil(dataView.totalRows / perPage)" @click="page++; loadTableData()">
              {{ __('Next') }}
            </Button>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- CREATE TABLE VIEW -->
  <CreateTableWizard
    v-if="view === 'create-table'"
    :database-id="databaseId"
    :schemas="schemas"
    :pre-selected-schema="creatingForSchema ?? undefined"
    @cancel="cancelCreateTable"
    @created="handleTableCreated"
  />

  <!-- Create Schema Dialog -->
  <Dialog v-model:open="createDialogOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ __('Create Schema') }}</DialogTitle>
        <DialogDescription>
          {{ __('Create a new schema (folder) in this database to organize your tables.') }}
        </DialogDescription>
      </DialogHeader>
      <div class="py-4">
        <div class="space-y-2">
          <Label for="schema-name">{{ __('Schema Name') }}</Label>
          <Input
            id="schema-name"
            v-model="newSchemaName"
            placeholder="e.g. auth, blog, store"
            @keyup.enter="createSchema"
          />
        </div>
      </div>
      <DialogFooter>
        <Button variant="outline" @click="createDialogOpen = false">
          {{ __('Cancel') }}
        </Button>
        <Button @click="createSchema" :disabled="!newSchemaName.trim() || creating">
          <Loader2 v-if="creating" class="h-4 w-4 mr-2 animate-spin" />
          {{ __('Create') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
