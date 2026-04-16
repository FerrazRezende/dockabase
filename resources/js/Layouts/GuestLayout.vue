<script setup lang="ts">
import ApplicationLogo from '@/components/app/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-background pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-muted-foreground" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-card px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg border border-border"
        >
            <slot />
        </div>
    </div>
</template>
