<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { __ } from '@/composables/useLang';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="__('Reset Password')" />

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

            <div class="space-y-2">
                <Label for="password">{{ __('Password') }}</Label>
                <Input
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password" class="text-sm text-destructive">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="password_confirmation">{{ __('Confirm Password') }}</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <div class="flex items-center justify-end">
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ __('Reset Password') }}
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>
