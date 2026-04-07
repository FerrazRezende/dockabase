<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import { useLang } from '@/composables/useLang';
import {
    Database,
    Key,
    Zap,
    Globe,
    Server,
    Shield,
    ArrowRight,
    Check,
    Sparkles,
    Workflow,
    GitBranch,
    Cpu,
    Crown,
    Building2,
    Languages,
} from 'lucide-vue-next';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

// Language selector
const { availableLocales, currentLocale, setLocale } = useLang();
const showLangMenu = ref(false);

// Mouse position for spotlight effect
const mouseX = ref(0);
const mouseY = ref(0);

const handleMouseMove = (e: MouseEvent) => {
    mouseX.value = e.clientX;
    mouseY.value = e.clientY;
};

onMounted(() => {
    window.addEventListener('mousemove', handleMouseMove);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
});

// Tech stack for marquee
const techStack = [
    { name: 'Laravel 13', icon: 'L' },
    { name: 'Vue 3', icon: 'V' },
    { name: 'PostgreSQL', icon: 'P' },
    { name: 'Redis', icon: 'R' },
    { name: 'RabbitMQ', icon: 'Q' },
    { name: 'Docker', icon: 'D' },
    { name: 'MinIO', icon: 'M' },
    { name: 'Tailwind', icon: 'T' },
];

// Plans data
const plans = [
    {
        name: 'Self-Hosted',
        price: 'Grátis',
        period: 'para sempre',
        description: 'Para desenvolvedores que querem controle total',
        features: [
            'Databases ilimitados',
            'Todas as features core',
            'API REST dinâmica',
            'Auth com OTP',
            'Realtime websockets',
            'Storage MinIO',
            'Suporte via Discord',
        ],
        cta: 'Começar Grátis',
        highlighted: false,
        icon: Server,
    },
    {
        name: 'Starter',
        price: 'R$ 97',
        period: '/mês',
        description: 'Para startups e pequenos projetos',
        features: [
            'Tudo do Self-Hosted',
            'Deploy gerenciado',
            '5 databases inclusos',
            '10GB storage',
            'Backups diários',
            'Uptime 99.5%',
            'Suporte por email',
        ],
        cta: 'Começar Trial',
        highlighted: false,
        icon: Cpu,
    },
    {
        name: 'Pro',
        price: 'R$ 297',
        period: '/mês',
        description: 'Para equipes em crescimento',
        features: [
            'Tudo do Starter',
            '25 databases inclusos',
            '100GB storage',
            'Backups a cada 6h',
            'Uptime 99.9%',
            'RLS avançado',
            'Suporte prioritário',
            'MCP Server access',
        ],
        cta: 'Começar Trial',
        highlighted: true,
        icon: Crown,
    },
    {
        name: 'Enterprise',
        price: 'Custom',
        period: '',
        description: 'Para grandes organizações',
        features: [
            'Tudo do Pro',
            'Databases ilimitados',
            'Storage ilimitado',
            'SLA personalizado',
            'SSO / SAML',
            'Dedicated infrastructure',
            'Suporte 24/7',
            'On-premise option',
        ],
        cta: 'Falar com Vendas',
        highlighted: false,
        icon: Building2,
    },
];
</script>

<template>
    <Head title="DockaBase - Backend as a Service" />

    <div class="relative min-h-screen overflow-hidden bg-[#030304] text-white font-sans antialiased">
        <!-- Atmospheric Background -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <!-- Primary gradient mesh -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-cyan-900/20 via-transparent to-transparent" />
            <!-- Secondary orb -->
            <div class="absolute top-0 right-0 h-[800px] w-[800px] translate-x-1/3 -translate-y-1/4 rounded-full bg-fuchsia-900/10 blur-[150px]" />
            <!-- Tertiary orb -->
            <div class="absolute bottom-0 left-0 h-[600px] w-[600px] -translate-x-1/3 translate-y-1/4 rounded-full bg-violet-900/10 blur-[120px]" />
            <!-- Grid pattern -->
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:64px_64px]" />
        </div>

        <!-- Noise texture overlay -->
        <div
            class="pointer-events-none fixed inset-0 opacity-[0.03]"
            style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.8%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E')"
        />

        <!-- Header -->
        <header class="fixed left-0 right-0 top-0 z-50">
            <div class="mx-auto w-full max-w-7xl px-6 py-6">
                <nav class="flex items-center justify-between">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-4 group">
                        <img src="/logo.png" alt="DockaBase" class="h-14 w-auto transition-transform duration-300 group-hover:scale-105" />
                        <span class="text-2xl font-bold tracking-tight">
                            <span class="text-white">Docka</span><span class="text-cyan-400">Base</span>
                        </span>
                    </Link>

                    <!-- Nav Links -->
                    <div class="hidden items-center gap-10 lg:flex">
                        <a href="#features" class="text-sm font-medium text-white/50 transition-colors hover:text-white">
                            {{ __('Features') }}
                        </a>
                        <a href="#pricing" class="text-sm font-medium text-white/50 transition-colors hover:text-white">
                            {{ __('Pricing') }}
                        </a>
                        <a href="#tech" class="text-sm font-medium text-white/50 transition-colors hover:text-white">
                            {{ __('Stack') }}
                        </a>
                        <a href="https://github.com" target="_blank" class="text-sm font-medium text-white/50 transition-colors hover:text-white">
                            Docs
                        </a>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <!-- Language Selector -->
                        <div class="relative">
                            <button
                                @click="showLangMenu = !showLangMenu"
                                class="flex items-center gap-2 rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-white/70 backdrop-blur-sm transition-all hover:border-white/20 hover:bg-white/[0.05] hover:text-white"
                                title="Change language"
                            >
                                <Globe :size="16" />
                                <span>{{ currentLocale?.flag }}</span>
                                <Languages :size="14" class="opacity-50" />
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-if="showLangMenu"
                                class="absolute right-0 top-full mt-2 w-40 rounded-lg border border-white/10 bg-[#0a0a0c] p-1 shadow-xl backdrop-blur-sm"
                                @click.outside="showLangMenu = false"
                            >
                                <button
                                    v-for="lang in availableLocales"
                                    :key="lang.code"
                                    @click="console.log('Clicked locale:', lang.code); setLocale(lang.code); showLangMenu = false"
                                    class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-white/70 transition-colors hover:bg-white/5 hover:text-white"
                                    :class="{ 'bg-white/5 text-white': currentLocale?.code === lang.code }"
                                >
                                    <span>{{ lang.flag }}</span>
                                    <span>{{ lang.label }}</span>
                                </button>
                            </div>
                        </div>

                        <template v-if="canLogin">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')">
                                <Button
                                    variant="ghost"
                                    class="text-white/60 hover:text-white hover:bg-white/5"
                                >
                                    Dashboard
                                </Button>
                            </Link>
                            <template v-else>
                                <Link :href="route('login')">
                                    <Button
                                        variant="ghost"
                                        class="text-white/60 hover:text-white hover:bg-white/5"
                                    >
                                        {{ __('Sign in') }}
                                    </Button>
                                </Link>
                                <Link v-if="canRegister" :href="route('register')">
                                    <Button
                                        class="bg-white text-[#030304] hover:bg-white/90 shadow-lg shadow-white/5 font-medium"
                                    >
                                        {{ __('Start Free') }}
                                    </Button>
                                </Link>
                            </template>
                        </template>
                    </div>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative flex min-h-screen flex-col items-center justify-center px-6 pt-32 pb-20">
            <div class="mx-auto max-w-6xl text-center">
                <!-- Badge -->
                <div class="mb-10 inline-flex items-center gap-2.5 rounded-full border border-white/10 bg-white/[0.03] px-5 py-2.5 backdrop-blur-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-cyan-500"></span>
                    </span>
                    <span class="text-sm font-medium text-white/70">{{ __('Open Source BaaS') }}</span>
                </div>

                <!-- Main Title -->
                <h1 class="relative mb-8 leading-[1.1]">
                    <span class="block text-5xl font-bold tracking-tight text-white sm:text-6xl md:text-7xl lg:text-8xl">
                        {{ __('Your own') }}
                    </span>
                    <span class="block mt-2 text-5xl font-bold tracking-tight sm:text-6xl md:text-7xl lg:text-8xl bg-gradient-to-r from-cyan-400 via-fuchsia-400 to-violet-400 bg-clip-text text-transparent">
                        Supabase
                    </span>
                    <span class="block mt-2 text-5xl font-bold tracking-tight text-white/30 sm:text-6xl md:text-7xl lg:text-8xl">
                        {{ __('self-hosted') }}
                    </span>
                </h1>

                <!-- Subtitle -->
                <p class="mx-auto max-w-2xl text-lg leading-relaxed text-white/40 sm:text-xl mb-12">
                    {{ __('Complete Backend as a Service with PostgreSQL, authentication, dynamic REST API, realtime and storage.') }}
                    <span class="text-white/60">{{ __('Full control, no vendor lock-in.') }}</span>
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')">
                        <Button
                            size="lg"
                            class="group relative overflow-hidden bg-gradient-to-r from-cyan-500 to-fuchsia-500 text-white shadow-2xl shadow-fuchsia-500/20 hover:shadow-fuchsia-500/40 transition-all duration-500 h-14 px-8 text-base font-medium"
                        >
                            <span class="relative z-10 flex items-center gap-2">
                                {{ __('Get Started') }}
                                <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </span>
                        </Button>
                    </Link>
                    <a href="#features">
                        <Button
                            variant="outline"
                            size="lg"
                            class="border-white/10 bg-white/[0.02] text-white/70 hover:bg-white/[0.05] hover:border-white/20 hover:text-white h-14 px-8 text-base"
                        >
                            Conhecer Features
                        </Button>
                    </a>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-12 left-1/2 -translate-x-1/2">
                <div class="flex flex-col items-center gap-3">
                    <span class="text-[10px] uppercase tracking-[0.2em] text-white/20">Scroll</span>
                    <div class="h-16 w-px bg-gradient-to-b from-white/20 via-white/10 to-transparent" />
                </div>
            </div>
        </section>

        <!-- Tech Stack Marquee -->
        <section id="tech" class="relative border-y border-white/[0.05] py-6 overflow-hidden">
            <div class="absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-[#030304] to-transparent z-10" />
            <div class="absolute inset-y-0 right-0 w-32 bg-gradient-to-l from-[#030304] to-transparent z-10" />

            <div class="marquee-container">
                <div class="marquee-track">
                    <div v-for="i in 2" :key="i" class="flex items-center gap-16 px-8">
                        <div
                            v-for="tech in techStack"
                            :key="tech.name"
                            class="flex items-center gap-4 whitespace-nowrap group"
                        >
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/[0.03] text-sm font-bold text-white/40 border border-white/[0.05] transition-all group-hover:border-cyan-500/30 group-hover:text-cyan-400">
                                {{ tech.icon }}
                            </span>
                            <span class="text-sm font-medium text-white/30 transition-colors group-hover:text-white/60">
                                {{ tech.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bento Grid Features -->
        <section id="features" class="relative py-32 md:py-40">
            <div class="mx-auto max-w-7xl px-6">
                <!-- Section Header -->
                <div class="mx-auto mb-20 max-w-3xl text-center">
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-cyan-400 mb-4">Features</p>
                    <h2 class="text-4xl font-bold tracking-tight text-white sm:text-5xl mb-6">
                        Tudo que você precisa
                    </h2>
                    <p class="text-lg text-white/40">
                        Uma plataforma completa para construir aplicações modernas com controle total sobre seus dados.
                    </p>
                </div>

                <!-- Bento Grid -->
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4 lg:grid-cols-6 auto-rows-[minmax(180px,auto)]">
                    <!-- Database Manager - Large Card -->
                    <div
                        class="group relative col-span-1 md:col-span-2 lg:col-span-3 row-span-2 overflow-hidden rounded-2xl border border-white/[0.05] bg-gradient-to-br from-white/[0.03] to-transparent p-10 backdrop-blur-sm transition-all duration-500 hover:border-cyan-500/20"
                        :style="{
                            background: `radial-gradient(800px circle at ${mouseX}px ${mouseY}px, rgba(6,182,212,0.03), transparent 40%)`,
                        }"
                    >
                        <div class="relative z-10 h-full flex flex-col">
                            <div class="mb-8 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500/10 to-cyan-500/5 border border-cyan-500/10">
                                <Database class="h-8 w-8 text-cyan-400" />
                            </div>
                            <h3 class="mb-4 text-2xl font-bold text-white">
                                Database Manager
                            </h3>
                            <p class="mb-8 max-w-md text-white/40 leading-relaxed flex-grow">
                                Interface visual para gerenciar múltiplos databases PostgreSQL. Suporte completo a tipos avançados como UUID, JSONB e Arrays.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-cyan-500/10 px-3 py-1.5 text-xs font-medium text-cyan-400 border border-cyan-500/10">PostgreSQL 16+</span>
                                <span class="rounded-full bg-white/[0.03] px-3 py-1.5 text-xs font-medium text-white/50 border border-white/[0.05]">Multi-database</span>
                                <span class="rounded-full bg-white/[0.03] px-3 py-1.5 text-xs font-medium text-white/50 border border-white/[0.05]">Schema Builder</span>
                            </div>
                        </div>
                    </div>

                    <!-- Auth Provider -->
                    <div
                        class="group relative col-span-1 md:col-span-2 lg:col-span-3 overflow-hidden rounded-2xl border border-white/[0.05] bg-gradient-to-br from-white/[0.03] to-transparent p-8 backdrop-blur-sm transition-all duration-500 hover:border-fuchsia-500/20"
                        :style="{
                            background: `radial-gradient(500px circle at ${mouseX}px ${mouseY}px, rgba(217,70,239,0.03), transparent 40%)`,
                        }"
                    >
                        <div class="relative z-10 flex items-start gap-5">
                            <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-fuchsia-500/10 to-fuchsia-500/5 border border-fuchsia-500/10">
                                <Key class="h-6 w-6 text-fuchsia-400" />
                            </div>
                            <div>
                                <h3 class="mb-2 text-lg font-bold text-white">
                                    Auth Provider
                                </h3>
                                <p class="text-sm text-white/40 leading-relaxed">
                                    Autenticação multi-tenant com OTP, JWT e RBAC integrado usando Spatie Permission.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic REST API -->
                    <div
                        class="group relative col-span-1 md:col-span-2 lg:col-span-3 overflow-hidden rounded-2xl border border-white/[0.05] bg-gradient-to-br from-white/[0.03] to-transparent p-8 backdrop-blur-sm transition-all duration-500 hover:border-violet-500/20"
                        :style="{
                            background: `radial-gradient(500px circle at ${mouseX}px ${mouseY}px, rgba(139,92,246,0.03), transparent 40%)`,
                        }"
                    >
                        <div class="relative z-10 flex items-start gap-5">
                            <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500/10 to-violet-500/5 border border-violet-500/10">
                                <Globe class="h-6 w-6 text-violet-400" />
                            </div>
                            <div>
                                <h3 class="mb-2 text-lg font-bold text-white">
                                    Dynamic REST API
                                </h3>
                                <p class="text-sm text-white/40 leading-relaxed">
                                    API auto-gerada estilo PostgREST com query syntax poderosa para filtros e ordenação.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Realtime -->
                    <div
                        class="group relative col-span-1 md:col-span-1 lg:col-span-2 overflow-hidden rounded-2xl border border-white/[0.05] bg-gradient-to-br from-white/[0.03] to-transparent p-6 backdrop-blur-sm transition-all duration-500 hover:border-amber-500/20"
                        :style="{
                            background: `radial-gradient(400px circle at ${mouseX}px ${mouseY}px, rgba(245,158,11,0.03), transparent 40%)`,
                        }"
                    >
                        <div class="relative z-10 flex items-center gap-4">
                            <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500/10 to-amber-500/5 border border-amber-500/10">
                                <Zap class="h-5 w-5 text-amber-400" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">
                                    Realtime
                                </h3>
                                <p class="text-xs text-white/40">
                                    Websockets com LISTEN/NOTIFY
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Storage -->
                    <div
                        class="group relative col-span-1 md:col-span-1 lg:col-span-2 overflow-hidden rounded-2xl border border-white/[0.05] bg-gradient-to-br from-white/[0.03] to-transparent p-6 backdrop-blur-sm transition-all duration-500 hover:border-emerald-500/20"
                        :style="{
                            background: `radial-gradient(400px circle at ${mouseX}px ${mouseY}px, rgba(16,185,129,0.03), transparent 40%)`,
                        }"
                    >
                        <div class="relative z-10 flex items-center gap-4">
                            <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500/10 to-emerald-500/5 border border-emerald-500/10">
                                <Server class="h-5 w-5 text-emerald-400" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">
                                    Storage
                                </h3>
                                <p class="text-xs text-white/40">
                                    S3 / MinIO com políticas
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- RLS -->
                    <div
                        class="group relative col-span-1 md:col-span-2 lg:col-span-2 overflow-hidden rounded-2xl border border-white/[0.05] bg-gradient-to-br from-white/[0.03] to-transparent p-6 backdrop-blur-sm transition-all duration-500 hover:border-rose-500/20"
                        :style="{
                            background: `radial-gradient(400px circle at ${mouseX}px ${mouseY}px, rgba(244,63,94,0.03), transparent 40%)`,
                        }"
                    >
                        <div class="relative z-10 flex items-center gap-4">
                            <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-rose-500/10 to-rose-500/5 border border-rose-500/10">
                                <Shield class="h-5 w-5 text-rose-400" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">
                                    Row Level Security
                                </h3>
                                <p class="text-xs text-white/40">
                                    Segurança a nível de linha com roles
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature Flags -->
                    <div
                        class="group relative col-span-1 md:col-span-2 lg:col-span-3 overflow-hidden rounded-2xl border border-white/[0.05] bg-gradient-to-br from-white/[0.03] to-transparent p-8 backdrop-blur-sm transition-all duration-500 hover:border-blue-500/20"
                        :style="{
                            background: `radial-gradient(500px circle at ${mouseX}px ${mouseY}px, rgba(59,130,246,0.03), transparent 40%)`,
                        }"
                    >
                        <div class="relative z-10 flex items-start gap-5">
                            <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500/10 to-blue-500/5 border border-blue-500/10">
                                <GitBranch class="h-6 w-6 text-blue-400" />
                            </div>
                            <div>
                                <h3 class="mb-2 text-lg font-bold text-white">
                                    Feature Flags
                                </h3>
                                <p class="text-sm text-white/40 leading-relaxed">
                                    Laravel Pennant para rollout gradual de features com suporte a estratégias por porcentagem, usuários e databases.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Async Jobs -->
                    <div
                        class="group relative col-span-1 md:col-span-2 lg:col-span-3 overflow-hidden rounded-2xl border border-white/[0.05] bg-gradient-to-br from-white/[0.03] to-transparent p-8 backdrop-blur-sm transition-all duration-500 hover:border-teal-500/20"
                        :style="{
                            background: `radial-gradient(500px circle at ${mouseX}px ${mouseY}px, rgba(20,184,166,0.03), transparent 40%)`,
                        }"
                    >
                        <div class="relative z-10 flex items-start gap-5">
                            <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500/10 to-teal-500/5 border border-teal-500/10">
                                <Workflow class="h-6 w-6 text-teal-400" />
                            </div>
                            <div>
                                <h3 class="mb-2 text-lg font-bold text-white">
                                    Async Jobs
                                </h3>
                                <p class="text-sm text-white/40 leading-relaxed">
                                    Processamento assíncrono robusto com RabbitMQ para tarefas pesadas e notificações em tempo real.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="relative py-32 md:py-40">
            <div class="mx-auto max-w-7xl px-6">
                <!-- Section Header -->
                <div class="mx-auto mb-20 max-w-3xl text-center">
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-fuchsia-400 mb-4">Preços</p>
                    <h2 class="text-4xl font-bold tracking-tight text-white sm:text-5xl mb-6">
                        Planos para todos
                    </h2>
                    <p class="text-lg text-white/40">
                        Comece grátis self-hosted ou deixe o deploy conosco. Sem surpresas, sem limites ocultos.
                    </p>
                </div>

                <!-- Pricing Grid -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="plan in plans"
                        :key="plan.name"
                        :class="[
                            'group relative overflow-hidden rounded-2xl p-8 transition-all duration-500',
                            plan.highlighted
                                ? 'border-2 border-fuchsia-500/50 bg-gradient-to-b from-fuchsia-500/10 to-transparent scale-[1.02] shadow-2xl shadow-fuchsia-500/10'
                                : 'border border-white/[0.05] bg-gradient-to-b from-white/[0.03] to-transparent hover:border-white/[0.1]'
                        ]"
                    >
                        <!-- Highlighted badge -->
                        <div
                            v-if="plan.highlighted"
                            class="absolute -top-px left-0 right-0 h-1 bg-gradient-to-r from-cyan-500 via-fuchsia-500 to-violet-500"
                        />

                        <!-- Icon -->
                        <div
                            :class="[
                                'mb-6 inline-flex h-12 w-12 items-center justify-center rounded-xl border',
                                plan.highlighted
                                    ? 'bg-fuchsia-500/10 border-fuchsia-500/20'
                                    : 'bg-white/[0.03] border-white/[0.05]'
                            ]"
                        >
                            <component
                                :is="plan.icon"
                                :class="[
                                    'h-6 w-6',
                                    plan.highlighted ? 'text-fuchsia-400' : 'text-white/40'
                                ]"
                            />
                        </div>

                        <!-- Plan name -->
                        <h3 class="text-lg font-bold text-white mb-2">
                            {{ plan.name }}
                        </h3>

                        <!-- Price -->
                        <div class="mb-4">
                            <span class="text-3xl font-bold text-white">{{ plan.price }}</span>
                            <span class="text-sm text-white/40">{{ plan.period }}</span>
                        </div>

                        <!-- Description -->
                        <p class="text-sm text-white/40 mb-6 min-h-[40px]">
                            {{ plan.description }}
                        </p>

                        <!-- Features -->
                        <ul class="mb-8 space-y-3">
                            <li
                                v-for="feature in plan.features"
                                :key="feature"
                                class="flex items-start gap-3 text-sm"
                            >
                                <Check :class="['h-4 w-4 shrink-0 mt-0.5', plan.highlighted ? 'text-fuchsia-400' : 'text-cyan-400']" />
                                <span class="text-white/60">{{ feature }}</span>
                            </li>
                        </ul>

                        <!-- CTA -->
                        <Button
                            :variant="plan.highlighted ? 'default' : 'outline'"
                            :class="[
                                'w-full h-11',
                                plan.highlighted
                                    ? 'bg-gradient-to-r from-cyan-500 to-fuchsia-500 text-white hover:opacity-90'
                                    : 'border-white/10 bg-white/[0.02] text-white/70 hover:bg-white/[0.05] hover:text-white'
                            ]"
                        >
                            {{ plan.cta }}
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="relative py-32">
            <div class="mx-auto max-w-5xl px-6">
                <div
                    class="relative overflow-hidden rounded-3xl border border-white/[0.05] bg-gradient-to-br from-white/[0.03] to-transparent p-12 md:p-20"
                >
                    <!-- Background decoration -->
                    <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-cyan-500/5 blur-[100px]" />
                    <div class="absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-fuchsia-500/5 blur-[100px]" />

                    <div class="relative z-10 grid gap-12 lg:grid-cols-2 lg:items-center">
                        <div>
                            <h2 class="mb-6 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                                Pronto para começar?
                            </h2>
                            <p class="mb-10 text-lg text-white/40 leading-relaxed">
                                Suba sua própria instância do DockaBase em minutos com Docker.
                                Controle total, sem vendor lock-in, open source para sempre.
                            </p>
                            <ul class="mb-10 space-y-4">
                                <li class="flex items-center gap-4 text-white/60">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-cyan-500/10">
                                        <Check class="h-3.5 w-3.5 text-cyan-400" />
                                    </div>
                                    <span>Open source para sempre</span>
                                </li>
                                <li class="flex items-center gap-4 text-white/60">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-cyan-500/10">
                                        <Check class="h-3.5 w-3.5 text-cyan-400" />
                                    </div>
                                    <span>Deploy com um comando</span>
                                </li>
                                <li class="flex items-center gap-4 text-white/60">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-cyan-500/10">
                                        <Check class="h-3.5 w-3.5 text-cyan-400" />
                                    </div>
                                    <span>Sem limites de uso</span>
                                </li>
                            </ul>
                            <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')">
                                <Button
                                    size="lg"
                                    class="group bg-white text-[#030304] hover:bg-white/90 shadow-xl shadow-white/5 h-14 px-8 text-base font-medium"
                                >
                                    Criar conta gratuita
                                    <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                                </Button>
                            </Link>
                        </div>

                        <div class="hidden lg:flex items-center justify-center">
                            <div class="relative">
                                <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-cyan-500 to-fuchsia-500 blur-3xl opacity-10" />
                                <div class="relative flex h-56 w-56 items-center justify-center rounded-3xl bg-gradient-to-br from-white/[0.05] to-transparent border border-white/[0.05]">
                                    <img src="/logo.png" alt="DockaBase" class="h-32 w-auto opacity-90" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="relative border-t border-white/[0.05] py-16">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-col items-center justify-between gap-8 md:flex-row">
                    <div class="flex items-center gap-4">
                        <img src="/logo.png" alt="DockaBase" class="h-8 w-auto opacity-60" />
                        <span class="text-sm text-white/30">
                            Open Source Backend as a Service
                        </span>
                    </div>

                    <div class="flex items-center gap-8">
                        <a href="#" class="text-sm text-white/30 transition-colors hover:text-white/60">
                            Documentação
                        </a>
                        <a href="#" class="text-sm text-white/30 transition-colors hover:text-white/60">
                            GitHub
                        </a>
                        <a href="#" class="text-sm text-white/30 transition-colors hover:text-white/60">
                            Discord
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Marquee animation */
.marquee-container {
    overflow: hidden;
    width: 100%;
}

.marquee-track {
    display: flex;
    animation: marquee 40s linear infinite;
}

@keyframes marquee {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

/* Pause marquee on hover */
.marquee-container:hover .marquee-track {
    animation-play-state: paused;
}

/* Smooth scroll */
html {
    scroll-behavior: smooth;
}
</style>
