<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertTriangle, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    open: boolean;
    title: string;
    description: string;
    confirmText?: string;
    confirmName?: string;
    loading?: boolean;
    variant?: 'danger' | 'warning';
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

const inputValue = ref('');

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const requiresNameInput = computed(() => !!props.confirmName);

const canConfirm = computed(() => {
    if (!requiresNameInput.value) return true;
    return inputValue.value === props.confirmName;
});

const handleConfirm = () => {
    if (canConfirm.value) {
        emit('confirm');
    }
};

const handleCancel = () => {
    emit('cancel');
    isOpen.value = false;
};

// Reset input when dialog opens/closes
watch(isOpen, (value) => {
    if (!value) {
        inputValue.value = '';
    }
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <AlertTriangle
                        v-if="variant === 'warning'"
                        class="h-5 w-5 text-yellow-500"
                    />
                    <Trash2
                        v-else
                        class="h-5 w-5 text-destructive"
                    />
                    {{ title }}
                </DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="requiresNameInput" class="space-y-2">
                <Label for="confirm-name" class="text-sm">
                    <span>{{ __('confirm_name_prefix') }} <span class="font-mono font-bold">{{ confirmName }}</span> {{ __('confirm_name_suffix') }}</span>
                </Label>
                <Input
                    id="confirm-name"
                    v-model="inputValue"
                    :placeholder="confirmName"
                    autocomplete="off"
                />
            </div>

            <DialogFooter class="gap-2 sm:gap-2">
                <Button
                    variant="outline"
                    @click="handleCancel"
                    :disabled="loading"
                >
                    {{ __('Cancel') }}
                </Button>
                <Button
                    v-if="variant === 'danger'"
                    variant="destructive"
                    @click="handleConfirm"
                    :disabled="!canConfirm || loading"
                >
                    <span v-if="loading">{{ __('Deleting...') }}</span>
                    <span v-else>{{ confirmText || __('Delete') }}</span>
                </Button>
                <Button
                    v-else
                    variant="default"
                    @click="handleConfirm"
                    :disabled="!canConfirm || loading"
                >
                    <span v-if="loading">{{ __('Processing') }}</span>
                    <span v-else>{{ confirmText || __('Confirm') }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
