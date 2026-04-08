<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LocaleForm from './Partials/LocaleForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-foreground">
                Profile
            </h2>
        </template>

        <div class="p-6">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-6">
                    <div class="bg-card shadow-sm rounded-lg border border-border p-6">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-xl"
                        />
                    </div>

                    <div class="bg-card shadow-sm rounded-lg border border-border p-6">
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>
                </div>

                <div class="bg-card shadow-sm rounded-lg border border-border p-6">
                    <LocaleForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
