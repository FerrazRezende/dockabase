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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Credential } from '@/types/credential';
import { ArrowLeft, Key, Shield, Users, Database, Calendar, Mail } from 'lucide-vue-next';

defineProps<{
    credential: Credential;
}>();

const getPermissionBadgeVariant = (permission: string): 'default' | 'secondary' | 'outline' => {
    if (permission === 'read-write') return 'default';
    if (permission === 'read') return 'outline';
    return 'secondary';
};

const getPermissionBadgeClass = (permission: string): string => {
    if (permission === 'read-write') return 'bg-green-500/10 text-green-500';
    if (permission === 'write') return 'bg-blue-500/10 text-blue-500';
    return '';
};
</script>

<template>
    <Head :title="`Credencial: ${credential.name}`" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('app.credentials.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground flex items-center gap-2">
                        <Key class="h-6 w-6 text-muted-foreground" />
                        {{ credential.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Detalhes da credencial
                    </p>
                </div>
            </div>
        </template>

        <div class="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Informações</CardTitle>
                    <CardDescription>Detalhes da credencial</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Nome</span>
                        <span class="font-medium">{{ credential.name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Shield class="h-4 w-4" />
                            Permissão
                        </span>
                        <Badge
                            :variant="getPermissionBadgeVariant(credential.permission)"
                            :class="getPermissionBadgeClass(credential.permission)"
                        >
                            {{ credential.permission_label }}
                        </Badge>
                    </div>
                    <div v-if="credential.description" class="pt-2 border-t">
                        <span class="text-muted-foreground text-sm">Descrição</span>
                        <p class="mt-1">{{ credential.description }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Estatísticas</CardTitle>
                    <CardDescription>Resumo de uso</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Users class="h-4 w-4" />
                            Usuários
                        </span>
                        <Badge variant="secondary">{{ credential.users_count ?? 0 }}</Badge>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Database class="h-4 w-4" />
                            Databases
                        </span>
                        <Badge variant="secondary">{{ credential.databases_count ?? 0 }}</Badge>
                    </div>
                </CardContent>
            </Card>

            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle>Usuários</CardTitle>
                    <CardDescription>Usuários com esta credencial</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table v-if="credential.users && credential.users.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>Email</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in credential.users" :key="user.id">
                                <TableCell class="font-medium">{{ user.name }}</TableCell>
                                <TableCell>
                                    <span class="flex items-center gap-1">
                                        <Mail class="h-3 w-3 text-muted-foreground" />
                                        {{ user.email }}
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <p v-else class="text-muted-foreground text-center py-4">
                        Nenhum usuário vinculado
                    </p>
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
                        <span class="text-sm">{{ new Date(credential.created_at).toLocaleString('pt-BR') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-muted-foreground flex items-center gap-2">
                            <Calendar class="h-4 w-4" />
                            Atualizado em
                        </span>
                        <span class="text-sm">{{ new Date(credential.updated_at).toLocaleString('pt-BR') }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
