<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { FeatureCollection } from '@/types/feature';
import { MoreHorizontal, Play, Square, Settings } from 'lucide-vue-next';

defineProps<{
    features: FeatureCollection;
}>();

const getStrategyBadgeVariant = (strategy: string): 'default' | 'secondary' | 'outline' => {
    if (strategy === 'all') return 'default';
    if (strategy === 'inactive') return 'outline';
    return 'secondary';
};
</script>

<template>
    <Head title="Feature Flags" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        Feature Flags
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Gerencie as features disponíveis na sua instância
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-card shadow-sm rounded-lg border border-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[200px]">Feature</TableHead>
                                <TableHead>Descrição</TableHead>
                                <TableHead class="w-[120px]">Status</TableHead>
                                <TableHead class="w-[150px]">Estratégia</TableHead>
                                <TableHead class="w-[100px]">Rollout</TableHead>
                                <TableHead class="w-[80px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="feature in features.data"
                                :key="feature.name"
                            >
                                <TableCell class="font-medium">
                                    {{ feature.display_name }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ feature.description }}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="feature.is_active ? 'default' : 'outline'"
                                        :class="feature.is_active ? 'bg-green-500/10 text-green-500 hover:bg-green-500/20' : ''"
                                    >
                                        {{ feature.is_active ? 'Ativo' : 'Inativo' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getStrategyBadgeVariant(feature.strategy)">
                                        {{ feature.strategy_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <span v-if="feature.strategy === 'percentage'" class="text-sm">
                                        {{ feature.percentage }}%
                                    </span>
                                    <span v-else-if="feature.strategy === 'all'" class="text-sm text-green-500">
                                        100%
                                    </span>
                                    <span v-else class="text-sm text-muted-foreground">
                                        -
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem v-if="!feature.is_active">
                                                <Play class="mr-2 h-4 w-4" />
                                                Ativar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem v-if="feature.is_active">
                                                <Square class="mr-2 h-4 w-4" />
                                                Desativar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem>
                                                <Settings class="mr-2 h-4 w-4" />
                                                Configurar
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
