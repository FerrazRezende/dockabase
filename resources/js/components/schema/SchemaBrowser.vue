<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useSchemaBrowser } from '@/composables/useSchemaBrowser'
import SchemaFolder from '@/components/schema/SchemaFolder.vue'
import DataView from '@/components/schema/DataView.vue'
import { Button } from '@/components/ui/button'
import { Plus } from 'lucide-vue-next'

interface Props {
  databaseId: string
}

const props = defineProps<Props>()

const {
  schemas,
  selectedSchema,
  selectedTable,
  expandedSchemas,
  loading,
  dataView,
  dataLoading,
  loadSchemas,
  toggleSchemaExpand,
} = useSchemaBrowser(props.databaseId)

const createTableDialogOpen = ref(false)

onMounted(() => {
  loadSchemas()
})
</script>

<template>
  <div class="flex h-full">
    <!-- Schema Browser Sidebar -->
    <div class="w-64 border-r bg-card p-4 overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold">{{ __('Schema') }}</h3>
        <Button size="sm" @click="createTableDialogOpen = true">
          <Plus class="h-4 w-4 mr-1" />
          {{ __('New Table') }}
        </Button>
      </div>

      <div v-if="loading" class="text-sm text-muted-foreground">
        {{ __('Loading...') }}
      </div>

      <div v-else class="space-y-1">
        <SchemaFolder
          v-for="schema in schemas"
          :key="schema.name"
          :schema="schema"
          :expanded="expandedSchemas.has(schema.name)"
          :selected_schema="selectedSchema"
          :selected_table="selectedTable"
          @toggle="toggleSchemaExpand"
          @select-table="selectTable"
        />
      </div>
    </div>

    <!-- Data View -->
    <div class="flex-1">
      <DataView
        v-if="selectedTable"
        :database-id="databaseId"
        :loading="dataLoading"
        :data-view="dataView"
      />
      <div v-else class="flex items-center justify-center h-full text-muted-foreground">
        {{ __('Select a table to view data') }}
      </div>
    </div>
  </div>
</template>
