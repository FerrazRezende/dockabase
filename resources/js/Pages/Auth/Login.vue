<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Database, Sun, Moon } from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const { isDark, toggleDark } = useDarkMode();
</script>

<template>
    <Head title="Entrar" />

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
                    <CardTitle class="text-2xl">Entrar</CardTitle>
                    <CardDescription>
                        Digite suas credenciais para acessar
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Alert v-if="status" class="mb-4">
                        <AlertDescription>{{ status }}</AlertDescription>
                    </Alert>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="admin@dockabase.com"
                            />
                            <p v-if="form.errors.email" class="text-sm text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Senha</Label>
                            <Input
                                id="password"
                                type="password"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                            <p v-if="form.errors.password" class="text-sm text-destructive">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="rounded border-input"
                                />
                                Lembrar de mim
                            </label>

                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm text-primary hover:underline"
                            >
                                Esqueceu a senha?
                            </Link>
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Entrando...</span>
                            <span v-else>Entrar</span>
                        </Button>
                    </form>

                    <div class="mt-6 text-center text-sm text-muted-foreground">
                        Não tem uma conta?
                        <Link :href="route('register')" class="text-primary hover:underline">
                            Criar conta
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </main>
    </div>
</template>
