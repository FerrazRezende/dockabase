<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Database } from 'lucide-vue-next';
import { ref, onMounted, watch } from 'vue';

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

// Dark mode toggle
const isDark = ref(false);

const toggleDark = () => {
    isDark.value = !isDark.value;
};

onMounted(() => {
    const stored = localStorage.getItem('theme');
    if (stored === 'dark') {
        isDark.value = true;
    } else if (stored === 'light') {
        isDark.value = false;
    } else {
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
});

watch(isDark, (value) => {
    if (value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
}, { immediate: true });
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
                        <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
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
