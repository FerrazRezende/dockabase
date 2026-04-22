<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
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
import { Loader2, ChevronLeft } from 'lucide-vue-next'
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
    <!-- Header -->
    <div class="flex items-center justify-between">
      <button
        class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
        @click="emit('cancel')"
      >
        <ChevronLeft class="h-4 w-4" />
        {{ __('Back to schemas') }}
      </button>
      <span class="text-sm text-muted-foreground">
        {{ currentStep }} / 2
      </span>
    </div>

    <!-- Step indicator -->
    <div class="flex gap-2">
      <div class="h-1 flex-1 rounded-full" :class="currentStep >= 1 ? 'bg-primary' : 'bg-muted'" />
      <div class="h-1 flex-1 rounded-full" :class="currentStep >= 2 ? 'bg-primary' : 'bg-muted'" />
    </div>

    <!-- Table name + schema selector -->
    <div class="grid gap-4 sm:grid-cols-2">
      <div class="space-y-2">
        <Label>{{ __('Table Name') }}</Label>
        <Input
          v-model="tableName"
          placeholder="products"
          class="font-mono"
        />
      </div>
      <div class="space-y-2">
        <Label>{{ __('Schema') }} <span class="text-destructive">*</span></Label>
        <Select v-model="selectedSchema">
          <SelectTrigger>
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

    <!-- Step 1: Columns -->
    <div v-if="currentStep === 1">
      <h3 class="text-sm font-medium mb-3">{{ __('Columns') }}</h3>
      <StepColumns v-model="columns" />
    </div>

    <!-- Step 2: Validations -->
    <div v-if="currentStep === 2">
      <h3 class="text-sm font-medium mb-3">{{ __('Validations') }}</h3>
      <StepValidations
        :columns="columns"
        :model-value="validations"
        @update:model-value="Object.assign(validations, $event)"
      />
    </div>

    <!-- Actions -->
    <div class="flex justify-between pt-4 border-t">
      <Button
        v-if="currentStep === 2"
        variant="outline"
        @click="currentStep = 1"
      >
        {{ __('Back') }}
      </Button>
      <div v-else />

      <div class="flex gap-2">
        <Button variant="outline" @click="emit('cancel')">
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
