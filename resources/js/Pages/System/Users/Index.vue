<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import type { SystemUserCollection } from '@/types/user';

defineProps<{
    users: SystemUserCollection;
}>();

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Usuários" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        Usuários
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Lista de todos os usuários do sistema
                    </p>
                </div>
            </div>
        </template>

        <div class="bg-card shadow-sm rounded-lg border border-border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nome</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead class="w-[120px]">Tipo</TableHead>
                        <TableHead class="w-[140px]">Criado em</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="user in users.data"
                        :key="user.id"
                    >
                        <TableCell class="font-medium">
                            {{ user.name }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ user.email }}
                        </TableCell>
                        <TableCell>
                            <Badge
                                v-if="user.is_admin"
                                variant="default"
                                class="bg-primary"
                            >
                                Admin
                            </Badge>
                            <Badge v-else variant="outline">
                                Usuário
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(user.created_at) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </AuthenticatedLayout>
</template>
