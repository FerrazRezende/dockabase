<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Database,
    Key,
    Zap,
    Globe,
    Server,
    Shield,
    ArrowRight,
    Code2,
    Layers,
    Lock,
    CheckCircle2,
    Sun,
    Moon,
} from 'lucide-vue-next';
import { useDarkMode } from '@/composables/useDarkMode';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const { isDark, toggleDark } = useDarkMode();
</script>

<template>
    <Head title="DockaBase - Backend as a Service" />

    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[65%] rounded-full border border-border bg-background/80 backdrop-blur-sm shadow-lg">
            <div class="flex h-14 items-center justify-between px-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                        <Database class="h-5 w-5 text-primary-foreground" />
                    </div>
                    <span class="text-xl font-bold text-foreground">DockaBase</span>
                </div>

                <nav class="flex items-center gap-2">
                    <Button variant="ghost" size="icon" @click="toggleDark">
                        <Sun v-if="isDark" class="h-5 w-5" />
                        <Moon v-else class="h-5 w-5" />
                    </Button>

                    <template v-if="canLogin">
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')">
                            <Button variant="ghost">Dashboard</Button>
                        </Link>
                        <template v-else>
                            <Link :href="route('login')">
                                <Button variant="ghost">Entrar</Button>
                            </Link>
                            <Link v-if="canRegister" :href="route('register')">
                                <Button>Começar</Button>
                            </Link>
                        </template>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-32">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-3xl text-center">
                    <Badge variant="secondary" class="mb-4">
                        Open Source BaaS
                    </Badge>
                    <h1 class="text-4xl font-bold tracking-tight text-foreground sm:text-6xl">
                        Seu próprio
                        <span class="text-primary">Supabase</span>
                        self-hosted
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-muted-foreground">
                        Backend as a Service completo com PostgreSQL, autenticação, API REST dinâmica,
                        tempo real e storage. Hospede você mesmo, tenha controle total.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-4">
                        <Link v-if="canRegister" :href="route('register')">
                            <Button size="lg" class="gap-2">
                                Começar agora
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                        <Button variant="outline" size="lg" class="gap-2">
                            <Code2 class="h-4 w-4" />
                            Ver documentação
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Background gradient -->
            <div class="absolute inset-0 -z-10">
                <div class="absolute inset-0 bg-gradient-to-b from-primary/5 via-transparent to-transparent" />
            </div>
        </section>

        <!-- Features Grid -->
        <section class="py-20">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-foreground">
                        Tudo que você precisa
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        Uma plataforma completa para construir aplicações modernas
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card class="relative overflow-hidden">
                        <CardHeader>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 mb-4">
                                <Database class="h-6 w-6 text-primary" />
                            </div>
                            <CardTitle>Database Manager</CardTitle>
                            <CardDescription>
                                Interface visual para gerenciar tabelas PostgreSQL com suporte a tipos avançados
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card class="relative overflow-hidden">
                        <CardHeader>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 mb-4">
                                <Key class="h-6 w-6 text-primary" />
                            </div>
                            <CardTitle>Auth Provider</CardTitle>
                            <CardDescription>
                                Autenticação multi-tenant com OTP, JWT e RBAC integrado
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card class="relative overflow-hidden">
                        <CardHeader>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 mb-4">
                                <Globe class="h-6 w-6 text-primary" />
                            </div>
                            <CardTitle>Dynamic REST API</CardTitle>
                            <CardDescription>
                                API auto-gerada a partir do schema do banco, estilo PostgREST
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card class="relative overflow-hidden">
                        <CardHeader>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 mb-4">
                                <Zap class="h-6 w-6 text-primary" />
                            </div>
                            <CardTitle>Realtime</CardTitle>
                            <CardDescription>
                                Websockets com LISTEN/NOTIFY do PostgreSQL para updates em tempo real
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card class="relative overflow-hidden">
                        <CardHeader>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 mb-4">
                                <Server class="h-6 w-6 text-primary" />
                            </div>
                            <CardTitle>Storage</CardTitle>
                            <CardDescription>
                                Abstração S3/MinIO com políticas de acesso e URLs temporárias
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card class="relative overflow-hidden">
                        <CardHeader>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 mb-4">
                                <Shield class="h-6 w-6 text-primary" />
                            </div>
                            <CardTitle>Row Level Security</CardTitle>
                            <CardDescription>
                                Segurança a nível de linha integrada com roles e permissões
                            </CardDescription>
                        </CardHeader>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Tech Stack -->
        <section class="border-t border-border bg-card/50 py-20">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-foreground">
                        Stack moderna e robusta
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        Construído com as melhores tecnologias do ecossistema PHP e Vue
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-4">
                    <div class="flex flex-col items-center text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-background shadow-sm border border-border mb-4">
                            <Layers class="h-8 w-8 text-primary" />
                        </div>
                        <h3 class="font-semibold text-foreground">Laravel 12</h3>
                        <p class="text-sm text-muted-foreground">Backend framework</p>
                    </div>

                    <div class="flex flex-col items-center text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-background shadow-sm border border-border mb-4">
                            <Database class="h-8 w-8 text-primary" />
                        </div>
                        <h3 class="font-semibold text-foreground">PostgreSQL 16</h3>
                        <p class="text-sm text-muted-foreground">Database</p>
                    </div>

                    <div class="flex flex-col items-center text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-background shadow-sm border border-border mb-4">
                            <Zap class="h-8 w-8 text-primary" />
                        </div>
                        <h3 class="font-semibold text-foreground">Redis 7</h3>
                        <p class="text-sm text-muted-foreground">Cache & Queue</p>
                    </div>

                    <div class="flex flex-col items-center text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-background shadow-sm border border-border mb-4">
                            <Lock class="h-8 w-8 text-primary" />
                        </div>
                        <h3 class="font-semibold text-foreground">Vue 3 + Inertia</h3>
                        <p class="text-sm text-muted-foreground">Frontend</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20">
            <div class="container mx-auto px-4">
                <Card class="mx-auto max-w-4xl overflow-hidden">
                    <CardContent class="p-0">
                        <div class="grid md:grid-cols-2">
                            <div class="p-8 md:p-12">
                                <h2 class="text-2xl font-bold text-foreground mb-4">
                                    Pronto para começar?
                                </h2>
                                <p class="text-muted-foreground mb-6">
                                    Suba sua própria instância do DockaBase em minutos com Docker.
                                    Controle total, sem vendor lock-in.
                                </p>
                                <ul class="space-y-3 mb-8">
                                    <li class="flex items-center gap-3 text-sm text-muted-foreground">
                                        <CheckCircle2 class="h-5 w-5 text-success" />
                                        Open source para sempre
                                    </li>
                                    <li class="flex items-center gap-3 text-sm text-muted-foreground">
                                        <CheckCircle2 class="h-5 w-5 text-success" />
                                        Deploy com um comando
                                    </li>
                                    <li class="flex items-center gap-3 text-sm text-muted-foreground">
                                        <CheckCircle2 class="h-5 w-5 text-success" />
                                        Sem limites de uso
                                    </li>
                                </ul>
                                <Link v-if="canRegister" :href="route('register')">
                                    <Button class="gap-2">
                                        Criar conta gratuita
                                        <ArrowRight class="h-4 w-4" />
                                    </Button>
                                </Link>
                            </div>
                            <div class="hidden md:block bg-gradient-to-br from-primary/20 to-primary/5 p-12">
                                <div class="flex h-full items-center justify-center">
                                    <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/25">
                                        <Database class="h-12 w-12 text-primary-foreground" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border py-8">
            <div class="container mx-auto px-4">
                <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                    <div class="flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded bg-primary">
                            <Database class="h-4 w-4 text-primary-foreground" />
                        </div>
                        <span class="font-semibold text-foreground">DockaBase</span>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Open Source Backend as a Service
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
