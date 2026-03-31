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
    <div class="bg-yellow-500 text-yellow-900 px-4 py-2 flex items-center justify-center gap-4">
        <div class="flex items-center gap-2">
            <AlertTriangle class="w-4 h-4" />
            <span class="text-sm font-medium">
                Você está acessando como <strong>{{ userName }}</strong>.
            </span>
        </div>
        <Button
            variant="outline"
            size="sm"
            class="bg-yellow-600 border-yellow-700 text-yellow-100 hover:bg-yellow-700"
            @click="stopImpersonating"
        >
            <LogOut class="w-4 h-4 mr-2" />
            Sair
        </Button>
    </div>
</template>
