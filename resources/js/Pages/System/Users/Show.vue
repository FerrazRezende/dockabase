<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-vue-next';

interface Permission {
    name: string;
    source: string;
}

interface Role {
    id: number;
    name: string;
    permissions: { id: number; name: string }[];
}

interface Credential {
    id: number;
    name: string;
    permission: string;
}

interface Database {
    id: number;
    name: string;
    credential: string;
    permission: string;
}

interface UserProfile {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    active: boolean;
    password_changed_at: string | null;
    created_at: string;
    roles: Role[];
    direct_permissions: { id: number; name: string }[];
    all_permissions: Permission[];
    features: string[];
    credentials: Credential[];
    databases: Database[];
}

defineProps<{
    user: UserProfile;
}>();

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getSourceLabel = (source: string): string => {
    if (source === 'direct') return 'Direta';
    return source.replace('role:', 'Role: ');
};

const getSourceVariant = (source: string): 'default' | 'secondary' | 'outline' => {
    if (source === 'direct') return 'default';
    return 'secondary';
};
</script>

<template>
    <Head :title="`Perfil: ${user.name}`" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('system.users.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="w-4 h-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        {{ user.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ user.email }}
                    </p>
                </div>
            </div>
        </template>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Informações Básicas -->
            <Card>
                <CardHeader>
                    <CardTitle>Informações</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Email</span>
                        <span>{{ user.email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Status</span>
                        <Badge :variant="user.active ? 'default' : 'outline'">
                            {{ user.active ? 'Ativo' : 'Inativo' }}
                        </Badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Tipo</span>
                        <Badge v-if="user.is_admin" variant="default" class="bg-primary">
                            Admin
                        </Badge>
                        <span v-else class="text-muted-foreground">Usuário</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Criado em</span>
                        <span>{{ formatDate(user.created_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Senha trocada</span>
                        <span v-if="user.password_changed_at">
                            {{ formatDate(user.password_changed_at) }}
                        </span>
                        <Badge v-else variant="outline" class="text-yellow-500">
                            Pendente
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- Roles -->
            <Card>
                <CardHeader>
                    <CardTitle>Roles</CardTitle>
                    <CardDescription>Grupos de permissões atribuídos ao usuário</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="user.roles.length > 0" class="flex flex-wrap gap-2">
                        <Badge
                            v-for="role in user.roles"
                            :key="role.id"
                            variant="secondary"
                        >
                            {{ role.name }}
                        </Badge>
                    </div>
                    <p v-else class="text-muted-foreground text-sm">
                        Nenhuma role atribuída
                    </p>
                </CardContent>
            </Card>

            <!-- Permissões -->
            <Card>
                <CardHeader>
                    <CardTitle>Permissões</CardTitle>
                    <CardDescription>Todas as permissões efetivas (roles + diretas)</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="user.all_permissions.length > 0" class="space-y-2">
                        <div
                            v-for="permission in user.all_permissions"
                            :key="permission.name"
                            class="flex items-center justify-between"
                        >
                            <span class="text-sm">{{ permission.name }}</span>
                            <Badge :variant="getSourceVariant(permission.source)" class="text-xs">
                                {{ getSourceLabel(permission.source) }}
                            </Badge>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground text-sm">
                        Nenhuma permissão
                    </p>
                </CardContent>
            </Card>

            <!-- Features -->
            <Card>
                <CardHeader>
                    <CardTitle>Features Visíveis</CardTitle>
                    <CardDescription>Features ativas para este usuário</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="user.features.length > 0" class="flex flex-wrap gap-2">
                        <Badge
                            v-for="feature in user.features"
                            :key="feature"
                            variant="outline"
                        >
                            {{ feature }}
                        </Badge>
                    </div>
                    <p v-else class="text-muted-foreground text-sm">
                        Nenhuma feature ativa
                    </p>
                </CardContent>
            </Card>

            <!-- Credentials -->
            <Card>
                <CardHeader>
                    <CardTitle>Credentials</CardTitle>
                    <CardDescription>Credenciais de acesso à API</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="user.credentials.length > 0" class="space-y-2">
                        <div
                            v-for="credential in user.credentials"
                            :key="credential.id"
                            class="flex items-center justify-between"
                        >
                            <span>{{ credential.name }}</span>
                            <Badge variant="secondary">
                                {{ credential.permission }}
                            </Badge>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground text-sm">
                        Nenhuma credential
                    </p>
                </CardContent>
            </Card>

            <!-- Databases -->
            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle>Databases</CardTitle>
                    <CardDescription>Bancos de dados acessíveis via credentials</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="user.databases.length > 0" class="grid gap-2">
                        <div
                            v-for="database in user.databases"
                            :key="database.id"
                            class="flex items-center justify-between p-2 rounded-lg bg-muted/50"
                        >
                            <div>
                                <span class="font-medium">{{ database.name }}</span>
                                <span class="text-muted-foreground text-sm ml-2">
                                    via {{ database.credential }}
                                </span>
                            </div>
                            <Badge variant="secondary">
                                {{ database.permission }}
                            </Badge>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground text-sm">
                        Nenhum database acessível
                    </p>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
