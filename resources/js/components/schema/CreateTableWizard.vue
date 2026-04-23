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
import { Loader2, ChevronLeft, Table2, ShieldCheck } from 'lucide-vue-next'
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
  <div class="space-y-6">
    <!-- Header with back + title -->
    <div class="flex items-center gap-4">
      <button
        class="flex items-center justify-center h-8 w-8 rounded-lg border hover:bg-accent transition-colors"
        @click="emit('cancel')"
      >
        <ChevronLeft class="h-4 w-4" />
      </button>
      <div class="flex-1">
        <h2 class="text-lg font-semibold">{{ __('New Table') }}</h2>
        <p class="text-sm text-muted-foreground">{{ currentStep === 1 ? __('Define the columns of your table') : __('Set validation rules for each column') }}</p>
      </div>
    </div>

    <!-- Step indicator pills -->
    <div class="flex gap-2">
      <button
        class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium transition-all"
        :class="currentStep === 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-accent'"
        @click="currentStep === 2 && canProceedToStep2 && (currentStep = 1)"
      >
        <Table2 class="h-3.5 w-3.5" />
        {{ __('Columns') }}
      </button>
      <button
        class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium transition-all"
        :class="currentStep === 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
        :disabled="!canProceedToStep2"
        @click="canProceedToStep2 && (currentStep = 2)"
      >
        <ShieldCheck class="h-3.5 w-3.5" />
        {{ __('Validations') }}
      </button>
    </div>

    <!-- Table name + schema selector -->
    <div class="grid gap-4 sm:grid-cols-2">
      <div class="space-y-1.5">
        <Label class="text-xs">{{ __('Table Name') }}</Label>
        <Input
          v-model="tableName"
          placeholder="products"
          class="font-mono h-9"
        />
      </div>
      <div class="space-y-1.5">
        <Label class="text-xs">{{ __('Schema') }} <span class="text-destructive">*</span></Label>
        <Select v-model="selectedSchema">
          <SelectTrigger class="h-9">
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

    <!-- Step content -->
    <div class="rounded-xl border bg-card min-h-[400px]">
      <div class="p-4">
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
    </div>

    <!-- Actions -->
    <div class="flex justify-between pt-2">
      <Button
        v-if="currentStep === 2"
        variant="outline"
        @click="currentStep = 1"
      >
        {{ __('Back') }}
      </Button>
      <div v-else />

      <div class="flex gap-2">
        <Button variant="ghost" @click="emit('cancel')">
          {{ __('Cancel') }}
        </Button>
        <Button
          v-if="currentStep === 1"
          :disabled="!canProceedToStep2"
          @click="currentStep = 2"
        >
          {{ __('Next') }}
        </Button>
        <Button
          v-if="currentStep === 2"
          :disabled="!canSubmit || submitting"
          @click="submit"
        >
          <Loader2 v-if="submitting" class="h-4 w-4 mr-2 animate-spin" />
          {{ __('Create Table') }}
        </Button>
      </div>
    </div>
  </div>
</template>
