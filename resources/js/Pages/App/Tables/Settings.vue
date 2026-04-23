<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { __ } from '@/composables/useLang'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from '@/components/ui/card'
import {
  ChevronDown,
  ChevronRight,
  ArrowLeft,
  Settings,
  Loader2,
  Key,
  Table as TableIcon,
  Columns3,
  ShieldCheck,
  MessageSquare,
} from 'lucide-vue-next'
import ValidationPresets from '@/components/schema/ValidationPresets.vue'
import { useToast } from 'vue-toastification'

interface ColumnInfo {
  name: string
  type: string
  nullable: boolean
  defaultValue: string | null
  isPrimaryKey: boolean
  isForeignKey: boolean
  foreignKey: { table: string; column: string; schema: string } | null
}

interface Props {
  database: { id: string; display_name?: string; name: string }
  schema: string
  table: string
  columns: ColumnInfo[]
  validations: Record<string, Record<string, boolean | number | string>>
  messages: Record<string, Record<string, string>>
}

const props = defineProps<Props>()
const toast = useToast()

const validations = reactive<Record<string, Record<string, boolean | number | string>>>(
  JSON.parse(JSON.stringify(props.validations))
)
const messages = reactive<Record<string, Record<string, string>>>(
  JSON.parse(JSON.stringify(props.messages))
)

const saving = ref(false)
const expandedColumns = ref<Set<string>>(new Set(props.columns.map(c => c.name)))

const toggleExpand = (name: string) => {
  const updated = new Set(expandedColumns.value)
  if (updated.has(name)) {
    updated.delete(name)
  } else {
    updated.add(name)
  }
  expandedColumns.value = updated
}

const updateColumnValidations = (columnName: string, presets: Record<string, boolean | number | string>) => {
  Object.assign(validations, { [columnName]: presets })
}

const updateMessage = (columnName: string, presetType: string, message: string) => {
  if (!messages[columnName]) {
    messages[columnName] = {}
  }
  messages[columnName] = { ...messages[columnName], [presetType]: message }
}

const activeRulesForColumn = (columnName: string): string[] => {
  const v = validations[columnName]
  if (!v) return []
  return Object.entries(v)
    .filter(([, val]) => val !== false && val !== null && val !== undefined)
    .map(([key]) => key)
}

const ruleCount = (columnName: string): number => activeRulesForColumn(columnName).length

const totalRules = computed(() => props.columns.reduce((sum, c) => sum + ruleCount(c.name), 0))

const save = () => {
  saving.value = true
  router.put(
    route('app.databases.tables.settings.update', {
      database: props.database.id,
      schema: props.schema,
      table: props.table,
    }),
    {
      validations: { ...validations },
      messages: { ...messages },
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(__('Table settings updated successfully'))
      },
      onError: () => {
        toast.error(__('Failed to update table settings'))
      },
      onFinish: () => {
        saving.value = false
      },
    }
  )
}
</script>

<template>
  <Head :title="__('Table Settings: :name', { name: table })" />

  <AuthenticatedLayout :auth="$page.props.auth">
    <template #header>
      <div class="flex items-center gap-4">
        <Link :href="route('app.databases.show', database.id)">
          <Button variant="ghost" size="icon">
            <ArrowLeft class="h-4 w-4" />
          </Button>
        </Link>
        <div>
          <h2 class="text-2xl font-semibold text-foreground flex items-center gap-2">
            <Settings class="h-6 w-6 text-muted-foreground" />
            {{ table }}
          </h2>
          <p class="text-sm text-muted-foreground mt-1">
            {{ schema }} · {{ database.display_name || database.name }}
          </p>
        </div>
      </div>
    </template>

    <div class="max-w-4xl space-y-6">
      <!-- Columns Overview -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-base">
            <Columns3 class="h-4 w-4" />
            {{ __('Columns') }}
            <Badge variant="secondary" class="text-[10px]">{{ columns.length }}</Badge>
          </CardTitle>
          <CardDescription>{{ __('Current column structure of this table') }}</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="text-xs">{{ __('Name') }}</TableHead>
                <TableHead class="text-xs">{{ __('Type') }}</TableHead>
                <TableHead class="text-xs">{{ __('Nullable') }}</TableHead>
                <TableHead class="text-xs">{{ __('Default') }}</TableHead>
                <TableHead class="text-xs">{{ __('Constraints') }}</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="col in columns" :key="col.name">
                <TableCell class="font-mono text-sm">{{ col.name }}</TableCell>
                <TableCell>
                  <Badge variant="outline" class="text-[10px]">{{ col.type }}</Badge>
                </TableCell>
                <TableCell class="text-xs">{{ col.nullable ? 'NULL' : 'NOT NULL' }}</TableCell>
                <TableCell class="font-mono text-xs text-muted-foreground">{{ col.defaultValue ?? '—' }}</TableCell>
                <TableCell>
                  <div class="flex gap-1">
                    <Badge v-if="col.isPrimaryKey" variant="default" class="text-[10px] h-5 gap-0.5">
                      <Key class="h-3 w-3" /> PK
                    </Badge>
                    <Badge v-if="col.isForeignKey" variant="secondary" class="text-[10px] h-5">FK</Badge>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Validations + Messages -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-base">
            <ShieldCheck class="h-4 w-4" />
            {{ __('Validations & Messages') }}
            <Badge v-if="totalRules > 0" variant="default" class="text-[10px]">{{ totalRules }} {{ __('rules') }}</Badge>
          </CardTitle>
          <CardDescription>{{ __('Configure validation rules and custom error messages for each column') }}</CardDescription>
        </CardHeader>
        <CardContent class="space-y-2">
          <div
            v-for="col in columns"
            :key="col.name"
            class="rounded-lg border overflow-hidden"
          >
            <button
              type="button"
              class="flex items-center gap-2 w-full px-4 py-2.5 text-left hover:bg-accent/50 transition-colors"
              @click="toggleExpand(col.name)"
            >
              <component :is="expandedColumns.has(col.name) ? ChevronDown : ChevronRight" class="h-4 w-4 text-muted-foreground shrink-0" />
              <span class="text-sm font-medium">{{ col.name }}</span>
              <Badge variant="secondary" class="text-[10px] h-5">{{ col.type }}</Badge>
              <Badge
                v-if="ruleCount(col.name) > 0"
                variant="default"
                class="text-[10px] h-5 ml-auto"
              >
                {{ ruleCount(col.name) }} {{ __('rules') }}
              </Badge>
            </button>

            <div v-if="expandedColumns.has(col.name)" class="px-4 pb-4 pt-2 border-t bg-muted/20 space-y-3">
              <ValidationPresets
                :column-name="col.name"
                :column-type="col.type as any"
                :model-value="validations[col.name] ?? {}"
                @update:model-value="updateColumnValidations(col.name, $event)"
              />

              <!-- Message inputs for active rules -->
              <div v-if="activeRulesForColumn(col.name).length > 0" class="space-y-2 pt-2 border-t border-border/50">
                <div class="flex items-center gap-1.5">
                  <MessageSquare class="h-3.5 w-3.5 text-muted-foreground" />
                  <span class="text-[10px] uppercase tracking-wider text-muted-foreground font-medium">{{ __('Error Messages') }}</span>
                </div>
                <div v-for="rule in activeRulesForColumn(col.name)" :key="rule" class="flex items-center gap-2">
                  <Badge variant="outline" class="text-[10px] h-5 shrink-0">{{ rule }}</Badge>
                  <Input
                    :model-value="messages[col.name]?.[rule] ?? ''"
                    :placeholder="__('Custom error message (optional)')"
                    class="h-7 text-xs flex-1"
                    @update:model-value="updateMessage(col.name, rule, $event)"
                  />
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Save -->
      <div class="flex justify-end gap-2">
        <Link :href="route('app.databases.show', database.id)">
          <Button variant="outline">{{ __('Cancel') }}</Button>
        </Link>
        <Button :disabled="saving" @click="save">
          <Loader2 v-if="saving" class="h-4 w-4 mr-2 animate-spin" />
          {{ __('Save Settings') }}
        </Button>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
