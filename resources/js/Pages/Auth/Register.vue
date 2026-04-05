<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Database, Sun, Moon } from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const { isDark, toggleDark } = useDarkMode();
</script>

<template>
    <Head :title="__('Create account')" />

    <div class="min-h-screen flex flex-col bg-background">
        <!-- Header -->
        <header class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[65%] rounded-full border border-border bg-background/80 backdrop-blur-sm shadow-lg">
            <div class="flex h-14 items-center justify-between px-6">
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                        <Database class="h-5 w-5 text-primary-foreground" />
                    </div>
                    <span class="text-xl font-bold text-foreground">DockaBase</span>
                </Link>

                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="icon" @click="toggleDark">
                        <Sun v-if="isDark" class="h-5 w-5" />
                        <Moon v-else class="h-5 w-5" />
                    </Button>
                </div>
            </div>
        </header>

        <!-- Main -->
        <main class="flex-1 flex items-center justify-center px-4 pt-24">
            <Card class="w-full max-w-md">
                <CardHeader class="text-center">
                    <CardTitle class="text-2xl">{{ __('Create account') }}</CardTitle>
                    <CardDescription>
                        {{ __('Fill in the data to create your account') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">{{ __('Name') }}</Label>
                            <Input
                                id="name"
                                type="text"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                                :placeholder="__('Enter your name')"
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">{{ __('Email') }}</Label>
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                autocomplete="email"
                                :placeholder="__('Enter your email')"
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
                                :placeholder="__('Enter your password')"
                            />
                            <p v-if="form.errors.password" class="text-sm text-destructive">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">{{ __('Confirm password') }}</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                :placeholder="__('Confirm password')"
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
                            <span v-if="form.processing">{{ __('Creating...') }}</span>
                            <span v-else>{{ __('Create account') }}</span>
                        </Button>
                    </form>

                    <div class="mt-6 text-center text-sm text-muted-foreground">
                        {{ __('Already have an account?') }}
                        <Link :href="route('login')" class="text-primary hover:underline">
                            {{ __('Sign in') }}
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </main>
    </div>
</template>
