<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { AlertTriangle, LogOut } from 'lucide-vue-next';

defineProps<{
    userName: string;
}>();

const stopImpersonating = (): void => {
    router.post(route('system.impersonate.stop'));
};
</script>

<template>
    <div class="border-b border-amber-500/20 bg-amber-500/10 px-4 py-3 flex items-center justify-center gap-4">
        <div class="flex items-center gap-2">
            <AlertTriangle class="h-4 w-4 text-amber-600 dark:text-amber-500" />
            <span class="text-sm font-medium text-amber-900 dark:text-amber-100">
                Você está acessando como <strong>{{ userName }}</strong>.
            </span>
        </div>
        <Button
            variant="outline"
            size="sm"
            class="border-amber-600 bg-amber-600 text-white hover:bg-amber-700 hover:border-amber-700 dark:bg-amber-700 dark:border-amber-700 dark:hover:bg-amber-800 dark:hover:border-amber-800"
            @click="stopImpersonating"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Sair
        </Button>
    </div>
</template>
