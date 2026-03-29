<script setup lang="ts">

import { Check, Loader2, Circle } from 'lucide-vue-next';
import type { CreationStep, DatabaseStatus } from '@/types/database';

interface Step {
  key: CreationStep;
  label: string;
}

const props = defineProps<{
  currentStep: CreationStep | null;
  progress: number;
  status: DatabaseStatus;
}>();

const steps: Step[] = [
  { key: 'validating', label: 'Validando' },
  { key: 'creating', label: 'Criando' },
  { key: 'configuring', label: 'Config' },
  { key: 'migrating', label: 'Migra' },
  { key: 'permissions', label: 'Perms' },
  { key: 'testing', label: 'Teste' },
  { key: 'ready', label: 'Pronto' },
];

const stepOrder = steps.map(s => s.key);

const getStepStatus = (step: CreationStep): 'completed' | 'running' | 'pending' => {
  if (props.status === 'failed') {
    const currentIndex = stepOrder.indexOf(props.currentStep || 'validating');
    const stepIndex = stepOrder.indexOf(step);
    return stepIndex < currentIndex ? 'completed' : 'pending';
  }

  if (props.status === 'ready') {
    return 'completed';
  }

  const currentIndex = stepOrder.indexOf(props.currentStep || 'validating');
  const stepIndex = stepOrder.indexOf(step);

  if (stepIndex < currentIndex) {
    return 'completed';
  }
  if (stepIndex === currentIndex) {
    return 'running';
  }
  return 'pending';
};

const getStepColor = (status: 'completed' | 'running' | 'pending'): string => {
  switch (status) {
    case 'completed':
      return 'bg-green-500 text-white';
    case 'running':
      return 'bg-blue-500 text-white animate-pulse';
    default:
      return 'bg-muted text-muted-foreground';
  }
};

const getLineColor = (index: number): string => {
  const nextStep = steps[index + 1];
  if (!nextStep) return 'bg-muted';

  const nextStatus = getStepStatus(nextStep.key);
  return nextStatus === 'completed' ? 'bg-green-500' : 'bg-muted';
};
</script>

<template>
  <div class="w-full py-6">
    <!-- Progress bar -->
    <div class="mb-4">
      <div class="flex justify-between text-sm text-muted-foreground mb-1">
        <span>Progresso</span>
        <span>{{ progress }}%</span>
      </div>
      <div class="h-2 bg-muted rounded-full overflow-hidden">
        <div
          class="h-full bg-primary transition-all duration-500 ease-out"
          :style="{ width: \`\${progress}%\` }"
        />
      </div>
    </div>

    <!-- Steps -->
    <div class="flex items-center justify-between">
      <template v-for="(step, index) in steps" :key="step.key">
        <!-- Step -->
        <div class="flex flex-col items-center">
          <div
            :class="[
              'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300',
              getStepColor(getStepStatus(step.key))
            ]"
          >
            <Check
              v-if="getStepStatus(step.key) === 'completed'"
              class="h-5 w-5"
            />
            <Loader2
              v-else-if="getStepStatus(step.key) === 'running'"
              class="h-5 w-5 animate-spin"
            />
            <Circle
              v-else
              class="h-5 w-5"
            />
          </div>
          <span
            :class="[
              'text-xs mt-2 font-medium',
              getStepStatus(step.key) === 'running' ? 'text-primary' : 'text-muted-foreground'
            ]"
          >
            {{ step.label }}
          </span>
        </div>

        <!-- Connector line -->
        <div
          v-if="index < steps.length - 1"
          :class="[
            'flex-1 h-1 mx-2 rounded transition-all duration-300',
            getLineColor(index)
          ]"
        />
      </template>
    </div>
  </div>
</template>
