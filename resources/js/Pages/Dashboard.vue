<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps<{
    auth: {
        user: {
            id: string;
            name: string;
            email: string;
            is_admin: boolean;
            avatar?: string;
        };
    };
}>();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout :auth="auth">
        <template #header>
            <h1 class="text-2xl font-semibold text-foreground">
                Dashboard
            </h1>
        </template>

        <div class="space-y-6">
            <h2 class="text-3xl font-bold text-foreground">
                Bem-vindo, {{ auth.user.name }}!
            </h2>
        </div>
    </AuthenticatedLayout>
</template>
