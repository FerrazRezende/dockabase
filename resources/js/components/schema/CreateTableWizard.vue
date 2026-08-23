<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { __ } from '@/composables/useLang'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Loader2, ChevronLeft, Table2, ShieldCheck, FolderOpen } from 'lucide-vue-next'
import StepColumns from '@/components/schema/StepColumns.vue'
import StepValidations from '@/components/schema/StepValidations.vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import type { ColumnDefinition, PostgresType } from '@/types/schema'

interface SchemaOption {
  name: string
}

interface Props {
  databaseId: string
  schemas: SchemaOption[]
  preSelectedSchema?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  cancel: []
  created: []
}>()

const toast = useToast()

const currentStep = ref(1)
const tableName = ref('')
const selectedSchema = ref(props.preSelectedSchema ?? '')
const submitting = ref(false)

const columns = ref<ColumnDefinition[]>([
  {
    name: 'id',
    type: 'uuid' as PostgresType,
    length: null,
    nullable: false,
    defaultValue: 'gen_random_uuid()',
    isPrimaryKey: true,
    foreignKey: null,
  },
])

const validations = reactive<Record<string, Record<string, boolean | number | string>>>({})
const messages = reactive<Record<string, Record<string, string>>>({})

const canProceedToStep2 = computed(() => {
  return tableName.value.trim() !== '' &&
    selectedSchema.value !== '' &&
    columns.value.length > 0 &&
    columns.value.every(c => c.name.trim() !== '')
})

const canSubmit = computed(() => canProceedToStep2.value)

const submit = async () => {
  if (!canSubmit.value) return

  submitting.value = true
  try {
    await axios.post(route('app.databases.tables.store', props.databaseId), {
      name: tableName.value,
      schema: selectedSchema.value,
      columns: columns.value.map(c => ({
        name: c.name,
        type: c.type,
        length: c.length,
        nullable: c.nullable,
        default_value: c.defaultValue,
        is_primary_key: c.isPrimaryKey,
        foreign_key: c.foreignKey,
      })),
      validations: validations,
      messages: messages,
    })

    toast.success(__('Table created successfully'))
    emit('created')
  } catch (error: any) {
    const message = error.response?.data?.message || __('Failed to create table')
    toast.error(message)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-4">
    <!-- Top bar: back + title -->
    <div class="flex items-center gap-3">
      <button
        class="flex items-center justify-center h-8 w-8 rounded-lg border hover:bg-accent transition-colors"
        @click="emit('cancel')"
      >
        <ChevronLeft class="h-4 w-4" />
      </button>
      <h2 class="text-lg font-semibold">{{ __('New Table') }}</h2>
    </div>

    <!-- Main card -->
    <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
      <!-- Card header: table config -->
      <div class="border-b bg-muted/30 px-5 py-4 space-y-3">
        <div class="grid gap-4 sm:grid-cols-[1fr_180px]">
          <div class="space-y-1.5">
            <Label class="text-xs text-muted-foreground">{{ __('Table Name') }}</Label>
            <Input
              v-model="tableName"
              placeholder="products"
              class="font-mono h-9"
            />
          </div>
          <div class="space-y-1.5">
            <Label class="text-xs text-muted-foreground">{{ __('Schema') }} <span class="text-destructive">*</span></Label>
            <Select v-model="selectedSchema">
              <SelectTrigger class="h-9">
                <FolderOpen class="h-3.5 w-3.5 mr-1.5 text-muted-foreground shrink-0" />
                <SelectValue :placeholder="__('Select schema')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="schema in schemas" :key="schema.name" :value="schema.name">
                  {{ schema.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      </div>

      <!-- Step tabs -->
      <div class="flex border-b">
        <button
          type="button"
          class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors"
          :class="currentStep === 1
            ? 'border-primary text-primary'
            : 'border-transparent text-muted-foreground hover:text-foreground'"
          @click="currentStep === 2 && canProceedToStep2 && (currentStep = 1)"
        >
          <Table2 class="h-3.5 w-3.5" />
          {{ __('Columns') }}
          <span
            v-if="columns.length > 0"
            class="text-[10px] px-1.5 py-0.5 rounded-full"
            :class="currentStep === 1 ? 'bg-primary/15' : 'bg-muted'"
          >
            {{ columns.length }}
          </span>
        </button>
        <button
          type="button"
          class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
          :class="currentStep === 2
            ? 'border-primary text-primary'
            : 'border-transparent text-muted-foreground hover:text-foreground'"
          :disabled="!canProceedToStep2"
          @click="canProceedToStep2 && (currentStep = 2)"
        >
          <ShieldCheck class="h-3.5 w-3.5" />
          {{ __('Validations') }}
        </button>
      </div>

      <!-- Step content -->
      <div class="p-5 min-h-[400px]">
        <StepColumns v-if="currentStep === 1" v-model="columns" />
        <StepValidations
          v-else
          :columns="columns"
          :model-value="validations"
          :messages="messages"
          @update:model-value="Object.assign(validations, $event)"
          @update:messages="Object.assign(messages, $event)"
        />
      </div>

      <!-- Card footer: actions -->
      <div class="flex items-center justify-between border-t bg-muted/20 px-5 py-3">
        <Button
          v-if="currentStep === 2"
          variant="ghost"
          size="sm"
          @click="currentStep = 1"
        >
          <ChevronLeft class="h-4 w-4 mr-1" />
          {{ __('Columns') }}
        </Button>
        <div v-else />

        <div class="flex gap-2">
          <Button variant="outline" size="sm" @click="emit('cancel')">
            {{ __('Cancel') }}
          </Button>
          <Button
            v-if="currentStep === 1"
            size="sm"
            :disabled="!canProceedToStep2"
            @click="currentStep = 2"
          >
            {{ __('Validations') }}
            <ShieldCheck class="h-3.5 w-3.5 ml-1.5" />
          </Button>
          <Button
            v-if="currentStep === 2"
            size="sm"
            :disabled="!canSubmit || submitting"
            @click="submit"
          >
            <Loader2 v-if="submitting" class="h-4 w-4 mr-2 animate-spin" />
            {{ __('Create Table') }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
