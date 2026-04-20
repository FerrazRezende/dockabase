import { ref, Ref } from 'vue'
import axios from 'axios'
import type { SchemaInfo, TableDataResponse } from '@/types/schema'
import { useToast } from '@/composables/useToast'
import { __ } from '@/composables/useLang'

interface SchemaBrowserState {
  schemas: Ref<SchemaInfo[]>
  selectedSchema: Ref<string | null>
  selectedTable: Ref<string | null>
  expandedSchemas: Ref<Set<string>>
  expandedTables: Ref<Set<string>>
  loading: Ref<boolean>
  dataView: Ref<TableDataResponse | null>
  dataLoading: Ref<boolean>
  page: Ref<number>
  perPage: Ref<number>
  search: Ref<string>
  sortBy: Ref<string | null>
  sortDir: Ref<'asc' | 'desc'>
}

export function useSchemaBrowser(databaseId: string) {
  const toast = useToast()

  const schemas = ref<SchemaInfo[]>([])
  const selectedSchema = ref<string | null>(null)
  const selectedTable = ref<string | null>(null)
  const expandedSchemas = ref<Set<string>>(new Set())
  const expandedTables = ref<Set<string>>(new Set())
  const loading = ref(false)
  const dataView = ref<TableDataResponse | null>(null)
  const dataLoading = ref(false)
  const page = ref(1)
  const perPage = ref(50)
  const search = ref('')
  const sortBy = ref<string | null>(null)
  const sortDir = ref<'asc' | 'desc'>('asc')

  const loadSchemas = async () => {
    loading.value = true
    try {
      const { data } = await axios.get(route('app.databases.schema', databaseId))
      schemas.value = data.data?.schemas ?? data.schemas ?? []
    } catch (error) {
      toast.error(__('Failed to load schema'))
    } finally {
      loading.value = false
    }
  }

  const toggleSchemaExpand = (schemaName: string) => {
    if (expandedSchemas.value.has(schemaName)) {
      expandedSchemas.value.delete(schemaName)
    } else {
      expandedSchemas.value.add(schemaName)
    }
    // Force reactivity
    expandedSchemas.value = new Set(expandedSchemas.value)
  }

  const toggleTableExpand = (tableKey: string) => {
    if (expandedTables.value.has(tableKey)) {
      expandedTables.value.delete(tableKey)
    } else {
      expandedTables.value.add(tableKey)
    }
    expandedTables.value = new Set(expandedTables.value)
  }

  const selectTable = async (schema: string, table: string) => {
    selectedSchema.value = schema
    selectedTable.value = table
    await loadTableData()
  }

  const loadTableData = async () => {
    if (!selectedSchema.value || !selectedTable.value) return

    dataLoading.value = true
    try {
      const { data } = await axios.get(route('app.databases.tables.data', {
        database: databaseId,
        schema: selectedSchema.value,
        table: selectedTable.value,
        page: page.value,
        per_page: perPage.value,
        search: search.value || undefined,
        sort_by: sortBy.value || undefined,
        sort_dir: sortDir.value,
      }))
      dataView.value = data.data ?? data
    } catch (error) {
      // Use mock data when API fails (for UI development)
      dataView.value = getMockTableData(selectedTable.value!)
    } finally {
      dataLoading.value = false
    }
  }

  // Mock data generator for UI development
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

  return {
    schemas,
    selectedSchema,
    selectedTable,
    expandedSchemas,
    expandedTables,
    loading,
    dataView,
    dataLoading,
    page,
    perPage,
    search,
    sortBy,
    sortDir,
    loadSchemas,
    toggleSchemaExpand,
    toggleTableExpand,
    selectTable,
    loadTableData,
  }
}
