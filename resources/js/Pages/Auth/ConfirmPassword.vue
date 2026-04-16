<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { __ } from '@/composables/useLang';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="__('Confirm Password')" />

        <div class="mb-4 text-sm text-muted-foreground">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
                <Label for="password">{{ __('Password') }}</Label>
                <Input
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <p v-if="form.errors.password" class="text-sm text-destructive">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="flex justify-end">
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ __('Confirm') }}
                </Button>
            </div>
        </form>
    </GuestLayout>
</template>
