<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { MoreHorizontal, Play, Square, Settings, ExternalLink } from 'lucide-vue-next';

defineProps<{
    features: FeatureCollection;
}>();

const activating = ref<string | null>(null);

const getCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    return meta?.content || '';
};

const activateFeature = async (featureName: string, strategy: string = 'all'): Promise<void> => {
    activating.value = featureName;
    try {
        const response = await fetch(route('system.features.activate', featureName), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ strategy }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        router.reload({ only: ['features'] });
    } catch (error) {
        console.error('Failed to activate feature:', error);
    } finally {
        activating.value = null;
    }
};

const deactivateFeature = async (featureName: string): Promise<void> => {
    activating.value = featureName;
    try {
        const response = await fetch(route('system.features.deactivate', featureName), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        router.reload({ only: ['features'] });
    } catch (error) {
        console.error('Failed to deactivate feature:', error);
    } finally {
        activating.value = null;
    }
};

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
                                    <Link
                                        :href="route('system.features.show', feature.name)"
                                        class="hover:underline"
                                    >
                                        {{ feature.display_name }}
                                    </Link>
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
                                            <DropdownMenuItem
                                                v-if="!feature.is_active"
                                                @click="activateFeature(feature.name)"
                                                :disabled="activating === feature.name"
                                            >
                                                <Play class="mr-2 h-4 w-4" />
                                                Ativar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="feature.is_active"
                                                @click="deactivateFeature(feature.name)"
                                                :disabled="activating === feature.name"
                                            >
                                                <Square class="mr-2 h-4 w-4" />
                                                Desativar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem as-child>
                                                <Link
                                                    :href="route('system.features.show', feature.name)"
                                                    class="flex items-center w-full"
                                                >
                                                    <Settings class="mr-2 h-4 w-4" />
                                                    Configurar
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
    </AuthenticatedLayout>
</template>
