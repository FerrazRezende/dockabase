import { ref, Ref } from 'vue'
import axios from 'axios'
import type { SchemaInfo, TableDataResponse } from '@/types/schema'
import { useToast } from '@/composables/useToast'

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
      schemas.value = data.schemas
    } catch (error) {
      toast.error('Failed to load schema')
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
      dataView.value = data
    } catch (error) {
      toast.error('Failed to load table data')
    } finally {
      dataLoading.value = false
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
