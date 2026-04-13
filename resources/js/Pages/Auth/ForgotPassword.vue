<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { __ } from '@/composables/useLang';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head :title="__('Forgot password?')" />

        <div class="mb-4 text-sm text-muted-foreground">
            {{ __('No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </div>

        <div
            v-if="status"
            class="mb-4 text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
                <Label for="email">{{ __('Email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <p v-if="form.errors.email" class="text-sm text-destructive">
                    {{ form.errors.email }}
                </p>
            </div>

            <div class="flex items-center justify-end">
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ __('Email Password Reset Link') }}
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>
