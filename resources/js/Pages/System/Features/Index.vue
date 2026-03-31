<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { FeatureCollection } from '@/types/feature';

defineProps<{
    features: FeatureCollection;
}>();

const toggling = ref<string | null>(null);

const toggleFeature = (featureName: string, currentlyActive: boolean): void => {
    toggling.value = featureName;

    const url = currentlyActive
        ? route('system.features.deactivate', featureName)
        : route('system.features.activate', featureName);

    router.post(url, { strategy: 'all' }, {
        preserveScroll: true,
        only: ['features'],
        onFinish: () => {
            toggling.value = null;
        },
    });
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
                        <TableHead class="w-[100px]">Status</TableHead>
                        <TableHead class="w-[150px]">Estratégia</TableHead>
                        <TableHead class="w-[100px]">Rollout</TableHead>
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
                            <Switch
                                :model-value="feature.is_active"
                                :disabled="toggling === feature.name"
                                @update:model-value="toggleFeature(feature.name, feature.is_active)"
                            />
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
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </AuthenticatedLayout>
</template>
