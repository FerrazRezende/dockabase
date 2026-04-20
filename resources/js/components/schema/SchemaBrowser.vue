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

interface Props {
  databaseId: string
}

const props = defineProps<Props>()

const {
  schemas,
  selectedSchema,
  selectedTable,
  loading,
  loadSchemas,
  search,
  sortBy,
  sortDir,
  page,
  perPage,
} = useSchemaBrowser(props.databaseId)

// Local state for mock data - bypass composable API
const dataLoading = ref(false)
const dataView = ref<TableDataResponse | null>(null)

const toast = useToast()
const { canEdit } = usePermissions()

// View: 'folders' = schema grid, 'browser' = table browser
const view = ref<'folders' | 'browser'>('folders')
const searchInput = ref('')

// Create schema dialog
const createDialogOpen = ref(false)
const newSchemaName = ref('')
const creating = ref(false)

// Use local dataView directly (set by handleSelectTable with mock data)
const mockTableData = computed(() => {
  if (dataView.value) {
    return dataView.value
  }
  // Default fallback
  return {
    columns: ['id', 'name', 'created_at'],
    rows: [
      { id: 'mock_001', name: 'Select a table', created_at: '2026-04-15' },
    ],
    totalRows: 0,
  }
})

const currentSchemaTables = computed(() => {
  const schema = displaySchemas.value.find(s => s.name === selectedSchema.value)
  return schema?.tables || []
})

const totalRows = computed(() => {
  return displaySchemas.value.reduce((sum, s) => sum + s.tables.length, 0)
})

const openSchema = (schemaName: string) => {
  selectedSchema.value = schemaName
  view.value = 'browser'
}

// Mock data generator - hardcoded in component
const getMockTableData = (tableName: string): TableDataResponse => {
  const mockData: Record<string, TableDataResponse> = {
    users: {
      columns: ['id', 'name', 'email', 'role', 'created_at'],
      rows: [
        { id: 'usr_01JF2K3M', name: 'Ana Silva', email: 'ana@dockabase.io', role: 'admin', created_at: '2026-04-10 14:30' },
        { id: 'usr_01JF2K3N', name: 'Carlos Souza', email: 'carlos@dockabase.io', role: 'editor', created_at: '2026-04-11 09:15' },
        { id: 'usr_01JF2K3P', name: 'Maria Oliveira', email: 'maria@dockabase.io', role: 'viewer', created_at: '2026-04-12 16:45' },
        { id: 'usr_01JF2K3Q', name: 'Pedro Santos', email: 'pedro@dockabase.io', role: 'editor', created_at: '2026-04-13 11:00' },
        { id: 'usr_01JF2K3R', name: 'Juliana Lima', email: 'juliana@dockabase.io', role: 'admin', created_at: '2026-04-14 08:20' },
      ],
      totalRows: 1240,
    },
    profiles: {
      columns: ['id', 'user_id', 'bio', 'avatar_url', 'updated_at'],
      rows: [
        { id: 'prf_001', user_id: 'usr_01JF2K3M', bio: 'Full stack developer', avatar_url: '/avatars/ana.jpg', updated_at: '2026-04-15 10:00' },
        { id: 'prf_002', user_id: 'usr_01JF2K3N', bio: 'Designer', avatar_url: '/avatars/carlos.jpg', updated_at: '2026-04-14 15:30' },
        { id: 'prf_003', user_id: 'usr_01JF2K3P', bio: 'Product manager', avatar_url: '/avatars/maria.jpg', updated_at: '2026-04-13 09:00' },
      ],
      totalRows: 856,
    },
    posts: {
      columns: ['id', 'title', 'status', 'views', 'published_at'],
      rows: [
        { id: 'pst_001', title: 'Getting Started with DockaBase', status: 'published', views: 1240, published_at: '2026-04-01' },
        { id: 'pst_002', title: 'Schema Builder Tutorial', status: 'published', views: 856, published_at: '2026-04-05' },
        { id: 'pst_003', title: 'API Authentication Guide', status: 'draft', views: 0, published_at: null },
        { id: 'pst_004', title: 'Realtime Features', status: 'published', views: 432, published_at: '2026-04-10' },
      ],
      totalRows: 489,
    },
    products: {
      columns: ['id', 'name', 'price', 'stock', 'active'],
      rows: [
        { id: 'prd_001', name: 'DockaBase Pro Plan', price: 29.99, stock: 999, active: true },
        { id: 'prd_002', name: 'DockaBase Enterprise', price: 99.99, stock: 50, active: true },
        { id: 'prd_003', name: 'DockaBase Starter', price: 0, stock: 9999, active: true },
        { id: 'prd_004', name: 'Support Pack', price: 9.99, stock: 0, active: false },
      ],
      totalRows: 567,
    },
    orders: {
      columns: ['id', 'customer', 'total', 'status', 'created_at'],
      rows: [
        { id: 'ord_001', customer: 'João Silva', total: 129.99, status: 'completed', created_at: '2026-04-15 14:30' },
        { id: 'ord_002', customer: 'Maria Santos', total: 59.99, status: 'pending', created_at: '2026-04-15 12:00' },
        { id: 'ord_003', customer: 'Pedro Lima', total: 199.99, status: 'processing', created_at: '2026-04-15 10:15' },
        { id: 'ord_004', customer: 'Ana Costa', total: 39.99, status: 'completed', created_at: '2026-04-14 18:45' },
      ],
      totalRows: 3421,
    },
    customers: {
      columns: ['id', 'name', 'email', 'country', 'signup_date'],
      rows: [
        { id: 'cust_001', name: 'João Silva', email: 'joao@email.com', country: 'Brazil', signup_date: '2026-01-15' },
        { id: 'cust_002', name: 'Maria Santos', email: 'maria@email.com', country: 'Portugal', signup_date: '2026-02-20' },
        { id: 'cust_003', name: 'Pedro Lima', email: 'pedro@email.com', country: 'Spain', signup_date: '2026-03-10' },
      ],
      totalRows: 1823,
    },
    sessions: {
      columns: ['id', 'user_id', 'ip_address', 'user_agent', 'last_activity'],
      rows: [
        { id: 'ses_001', user_id: 'usr_01JF2K3M', ip_address: '192.168.1.100', user_agent: 'Chrome/120', last_activity: '2026-04-15 16:00' },
        { id: 'ses_002', user_id: 'usr_01JF2K3N', ip_address: '192.168.1.101', user_agent: 'Firefox/121', last_activity: '2026-04-15 15:45' },
        { id: 'ses_003', user_id: 'usr_01JF2K3P', ip_address: '192.168.1.102', user_agent: 'Safari/17', last_activity: '2026-04-15 14:30' },
      ],
      totalRows: 3420,
    },
    permissions: {
      columns: ['id', 'name', 'description', 'guard_name'],
      rows: [
        { id: 1, name: 'view-databases', description: 'View databases list', guard_name: 'web' },
        { id: 2, name: 'create-database', description: 'Create new database', guard_name: 'web' },
        { id: 3, name: 'delete-database', description: 'Delete a database', guard_name: 'web' },
      ],
      totalRows: 45,
    },
    roles: {
      columns: ['id', 'name', 'description', 'created_at'],
      rows: [
        { id: 1, name: 'super-admin', description: 'Full access', created_at: '2026-01-01' },
        { id: 2, name: 'admin', description: 'Admin access', created_at: '2026-01-01' },
        { id: 3, name: 'manager', description: 'Manager access', created_at: '2026-01-01' },
      ],
      totalRows: 12,
    },
    oauth_providers: {
      columns: ['id', 'provider', 'user_id', 'provider_user_id', 'created_at'],
      rows: [
        { id: 1, provider: 'google', user_id: 'usr_01JF2K3M', provider_user_id: 'google_123', created_at: '2026-03-01' },
        { id: 2, provider: 'github', user_id: 'usr_01JF2K3N', provider_user_id: 'github_456', created_at: '2026-03-05' },
      ],
      totalRows: 128,
    },
    password_resets: {
      columns: ['id', 'email', 'token', 'created_at'],
      rows: [
        { id: 1, email: 'user@example.com', token: 'abc123...', created_at: '2026-04-15 10:00' },
        { id: 2, email: 'another@example.com', token: 'def456...', created_at: '2026-04-14 15:30' },
      ],
      totalRows: 67,
    },
    mfa_codes: {
      columns: ['id', 'user_id', 'code', 'type', 'expires_at'],
      rows: [
        { id: 1, user_id: 'usr_01JF2K3M', code: '******', type: 'totp', expires_at: '2026-04-15 18:00' },
        { id: 2, user_id: 'usr_01JF2K3N', code: '******', type: 'totp', expires_at: '2026-04-15 17:30' },
      ],
      totalRows: 234,
    },
    comments: {
      columns: ['id', 'post_id', 'author_name', 'content', 'created_at'],
      rows: [
        { id: 1, post_id: 'pst_001', author_name: 'João Silva', content: 'Great article!', created_at: '2026-04-15' },
        { id: 2, post_id: 'pst_002', author_name: 'Maria Santos', content: 'Very helpful', created_at: '2026-04-14' },
      ],
      totalRows: 2156,
    },
    categories: {
      columns: ['id', 'name', 'slug', 'posts_count'],
      rows: [
        { id: 1, name: 'Tutorials', slug: 'tutorials', posts_count: 45 },
        { id: 2, name: 'News', slug: 'news', posts_count: 23 },
        { id: 3, name: 'Reviews', slug: 'reviews', posts_count: 12 },
      ],
      totalRows: 34,
    },
    tags: {
      columns: ['id', 'name', 'slug', 'color'],
      rows: [
        { id: 1, name: 'Laravel', slug: 'laravel', color: '#FF2D20' },
        { id: 2, name: 'Vue', slug: 'vue', color: '#4FC08D' },
        { id: 3, name: 'TypeScript', slug: 'typescript', color: '#3178C6' },
      ],
      totalRows: 89,
    },
    order_items: {
      columns: ['id', 'order_id', 'product_id', 'quantity', 'price'],
      rows: [
        { id: 1, order_id: 'ord_001', product_id: 'prd_001', quantity: 2, price: 29.99 },
        { id: 2, order_id: 'ord_002', product_id: 'prd_002', quantity: 1, price: 99.99 },
      ],
      totalRows: 8456,
    },
  }

  return mockData[tableName] || {
    columns: ['id', 'name', 'created_at'],
    rows: [
      { id: 'mock_001', name: `Sample row for ${tableName}`, created_at: '2026-04-15' },
      { id: 'mock_002', name: `Sample row for ${tableName}`, created_at: '2026-04-15' },
    ],
    totalRows: 100,
  }
}

const goBack = () => {
  view.value = 'folders'
  selectedSchema.value = null
  selectedTable.value = null
}

const handleSelectTable = async (schema: string, table: string) => {
  selectedTable.value = table
  dataLoading.value = true
  // Simulate API delay
  await new Promise(resolve => setTimeout(resolve, 300))
  dataView.value = getMockTableData(table)
  dataLoading.value = false
}

const handleSort = (column: string) => {
  if (sortBy.value === column) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortDir.value = 'asc'
  }
  // Re-sort mock data locally
  if (dataView.value && dataView.value.rows) {
    const rows = [...dataView.value.rows]
    rows.sort((a: any, b: any) => {
      const aVal = a[column]
      const bVal = b[column]
      const cmp = aVal < bVal ? -1 : aVal > bVal ? 1 : 0
      return sortDir.value === 'asc' ? cmp : -cmp
    })
    dataView.value = { ...dataView.value, rows }
  }
}

// Mock schemas for UI testing when backend returns empty
const displaySchemas = computed(() => {
  // Use mock when no real schemas loaded (empty or undefined)
  if (!schemas.value || schemas.value.length === 0) {
    return [
      {
        name: 'public',
        tables: [
          { name: 'users', schema: 'public', rowCount: 1240, columns: [] },
          { name: 'profiles', schema: 'public', rowCount: 856, columns: [] },
          { name: 'sessions', schema: 'public', rowCount: 3420, columns: [] },
          { name: 'permissions', schema: 'public', rowCount: 45, columns: [] },
          { name: 'roles', schema: 'public', rowCount: 12, columns: [] },
        ],
      },
      {
        name: 'auth',
        tables: [
          { name: 'oauth_providers', schema: 'auth', rowCount: 128, columns: [] },
          { name: 'password_resets', schema: 'auth', rowCount: 67, columns: [] },
          { name: 'mfa_codes', schema: 'auth', rowCount: 234, columns: [] },
        ],
      },
      {
        name: 'blog',
        tables: [
          { name: 'posts', schema: 'blog', rowCount: 489, columns: [] },
          { name: 'comments', schema: 'blog', rowCount: 2156, columns: [] },
          { name: 'categories', schema: 'blog', rowCount: 34, columns: [] },
          { name: 'tags', schema: 'blog', rowCount: 89, columns: [] },
        ],
      },
      {
        name: 'ecommerce',
        tables: [
          { name: 'products', schema: 'ecommerce', rowCount: 567, columns: [] },
          { name: 'orders', schema: 'ecommerce', rowCount: 3421, columns: [] },
          { name: 'customers', schema: 'ecommerce', rowCount: 1823, columns: [] },
          { name: 'order_items', schema: 'ecommerce', rowCount: 8456, columns: [] },
        ],
      },
    ]
  }
  return schemas.value
})

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

onMounted(() => {
  loadSchemas()
})
</script>

<template>
  <!-- FOLDERS VIEW: Schema cards -->
  <div v-if="view === 'folders'">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold">{{ __('Schemas') }}</h3>
        <p class="text-sm text-muted-foreground">{{ displaySchemas.length }} {{ displaySchemas.length === 1 ? __('schema') : __('schemas') }} {{ __('in this database') }}</p>
      </div>
      <Button v-if="canEdit('databases')" @click="createDialogOpen = true">
        <Plus class="h-4 w-4 mr-2" />
        {{ __('New Schema') }}
      </Button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="displaySchemas.length === 0" class="flex flex-col items-center justify-center py-20 text-muted-foreground gap-3">
      <Folder class="h-12 w-12 opacity-20" />
      <p class="text-sm">{{ __('No schemas found in this database') }}</p>
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <button
        v-for="schema in displaySchemas"
        :key="schema.name"
        class="group relative overflow-hidden rounded-xl border bg-card p-0 text-left transition-all hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5 hover:-translate-y-0.5 cursor-pointer"
        @click="openSchema(schema.name)"
      >
        <div class="p-5">
          <!-- Header with icon -->
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

          <!-- Tables preview -->
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

          <!-- Empty state -->
          <div v-else class="flex items-center justify-center rounded-lg bg-muted/30 py-3 text-xs text-muted-foreground">
            {{ __('No tables yet') }}
          </div>
        </div>

        <!-- Hover glow effect -->
        <div class="absolute inset-0 bg-gradient-to-tr from-primary/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
      </button>
    </div>
  </div>

  <!-- BROWSER VIEW: Table sidebar + data -->
  <div v-else class="flex h-[calc(100vh-14rem)] min-h-[500px] rounded-lg border bg-card overflow-hidden">
    <!-- Left Sidebar: Tables -->
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

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- No table selected -->
      <div v-if="!selectedTable" class="flex-1 flex flex-col items-center justify-center text-muted-foreground gap-3">
        <TableProperties class="h-10 w-10 opacity-20" />
        <p class="text-sm">{{ __('Select a table to view its data') }}</p>
      </div>

      <!-- Table data view -->
      <template v-else>
        <!-- Toolbar -->
        <div class="flex items-center gap-3 px-4 py-3 border-b shrink-0">
          <div class="flex items-center gap-2 min-w-0">
            <TableIcon class="h-4 w-4 text-muted-foreground shrink-0" />
            <h3 class="text-sm font-semibold truncate">{{ selectedTable }}</h3>
            <Badge variant="outline" class="text-[10px] h-5 shrink-0">
              <Columns3 class="h-3 w-3 mr-1" />
              {{ currentSchemaTables.find(t => t.name === selectedTable)?.columns.length || 0 }} {{ __('cols') }}
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

        <!-- Data table -->
        <div class="flex-1 overflow-auto">
          <div v-if="dataLoading" class="flex items-center justify-center h-40">
            <Loader2 class="h-5 w-5 animate-spin text-muted-foreground" />
          </div>

          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead
                  v-for="column in mockTableData.columns"
                  :key="column"
                  class="cursor-pointer hover:bg-accent/50 text-xs h-9"
                  @click="handleSort(column)"
                >
                  <div class="flex items-center gap-1">
                    {{ column }}
                    <ArrowUpDown
                      v-if="sortBy === column"
                      class="h-3 w-3"
                      :class="{ 'rotate-180': sortDir === 'desc' }"
                    />
                  </div>
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(row, idx) in mockTableData.rows" :key="idx" class="hover:bg-accent/30">
                <TableCell
                  v-for="column in mockTableData.columns"
                  :key="column"
                  class="text-sm py-2"
                >
                  <span v-if="column === 'id'" class="font-mono text-xs text-muted-foreground">{{ row[column] }}</span>
                  <Badge v-else-if="column === 'role'" variant="secondary" class="text-[10px] h-5">{{ row[column] }}</Badge>
                  <span v-else>{{ row[column] }}</span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-4 py-2.5 border-t text-xs text-muted-foreground shrink-0">
          <span>
            {{ __('Showing :from to :to of :total rows', {
              from: (page - 1) * perPage + 1,
              to: Math.min(page * perPage, mockTableData.totalRows),
              total: mockTableData.totalRows,
            }) }}
          </span>
          <div class="flex items-center gap-1">
            <Button variant="outline" size="sm" class="h-7 text-xs" :disabled="page <= 1">
              {{ __('Previous') }}
            </Button>
            <span class="px-2">{{ page }}</span>
            <Button variant="outline" size="sm" class="h-7 text-xs" :disabled="page >= Math.ceil(mockTableData.totalRows / perPage)">
              {{ __('Next') }}
            </Button>
          </div>
        </div>
      </template>
    </div>
  </div>

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
