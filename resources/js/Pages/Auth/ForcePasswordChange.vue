<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AlertCircle } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.post(route('password.force-change.update'), {
        preserveScroll: true,
        onError: (errors) => {
            const errorMessage = errors.current_password || errors.password || errors.password_confirmation || __('Error changing password');
            toast.error(errorMessage);
        },
    });
};
</script>

<template>
    <Head :title="__('Change Password')" />

    <div class="min-h-screen flex items-center justify-center bg-background p-4">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                    <AlertCircle class="w-6 h-6 text-yellow-600" />
                </div>
                <CardTitle>{{ __('Change Password') }}</CardTitle>
                <CardDescription>
                    {{ __('For security, you need to set a new password before continuing.') }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <label for="current_password" class="text-sm font-medium">{{ __('Current password') }}</label>
                        <Input
                            id="current_password"
                            v-model="form.current_password"
                            type="password"
                            :placeholder="__('Enter your current password')"
                            required
                            autocomplete="current-password"
                        />
                        <p v-if="form.errors.current_password" class="text-sm text-destructive">
                            {{ form.errors.current_password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-sm font-medium">{{ __('New password') }}</label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            :placeholder="__('Enter new password (min. 8 characters)')"
                            required
                            autocomplete="new-password"
                        />
                        <p v-if="form.errors.password" class="text-sm text-destructive">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="password_confirmation" class="text-sm font-medium">{{ __('Confirm new password') }}</label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            :placeholder="__('Confirm the new password')"
                            required
                            autocomplete="new-password"
                        />
                        <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                    >
                        {{ __('Change Password') }}
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
