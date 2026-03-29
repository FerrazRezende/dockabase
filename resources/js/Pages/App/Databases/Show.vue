<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Database } from '@/types/database';
import { ArrowLeft, Server, Database as DatabaseIcon, Calendar, Link2 } from 'lucide-vue-next';

defineProps<{
    database: Database;
}>();
</script>

<template>
    <Head :title="`Database: ${database.name}`" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('app.databases.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground flex items-center gap-2">
                        <DatabaseIcon class="h-6 w-6 text-muted-foreground" />
                        {{ database.display_name || database.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Detalhes do database
                    </p>
                </div>
            </div>
        </template>

        <div class="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Informações</CardTitle>
                    <CardDescription>Detalhes do database</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Nome</span>
                        <span class="font-medium">{{ database.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Database Name</span>
                        <span class="font-medium font-mono text-sm">{{ database.database_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Status</span>
                        <Badge
                            :variant="database.is_active ? 'default' : 'outline'"
                            :class="database.is_active ? 'bg-green-500/10 text-green-500' : ''"
                        >
                            {{ database.is_active ? 'Ativo' : 'Inativo' }}
                        </Badge>
                    </div>
                    <div v-if="database.description" class="pt-2 border-t">
                        <span class="text-muted-foreground text-sm">Descrição</span>
                        <p class="mt-1">{{ database.description }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Conexão</CardTitle>
                    <CardDescription>Configurações de conexão</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Server class="h-4 w-4" />
                            Host
                        </span>
                        <span class="font-medium font-mono text-sm">{{ database.host }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Port</span>
                        <span class="font-medium">{{ database.port }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Credentials</CardTitle>
                    <CardDescription>Credenciais com acesso a este database</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Total de credenciais</span>
                        <Badge variant="secondary">
                            <Link2 class="h-3 w-3 mr-1" />
                            {{ database.credentials_count ?? 0 }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Metadados</CardTitle>
                    <CardDescription>Informações de criação</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Calendar class="h-4 w-4" />
                            Criado em
                        </span>
                        <span class="text-sm">{{ new Date(database.created_at).toLocaleString('pt-BR') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Calendar class="h-4 w-4" />
                            Atualizado em
                        </span>
                        <span class="text-sm">{{ new Date(database.updated_at).toLocaleString('pt-BR') }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
