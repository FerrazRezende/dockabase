<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { __ } from '@/composables/useLang';
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
import { useToast } from '@/composables/useToast';

defineProps<{
    features: FeatureCollection;
}>();

const toggling = ref<string | null>(null);
const toast = useToast();

const toggleFeature = (featureName: string, currentlyActive: boolean): void => {
    toggling.value = featureName;

    const url = currentlyActive
        ? route('system.features.deactivate', featureName)
        : route('system.features.activate', featureName);

    router.post(url, { strategy: 'all' }, {
        preserveScroll: true,
        only: ['features'],
        onSuccess: () => {
            const message = currentlyActive
                ? __('Feature disabled successfully')
                : __('Feature enabled successfully');
            toast.success(message);
        },
        onError: () => {
            const errorMessage = currentlyActive
                ? __('Error deactivating feature')
                : __('Error activating feature');
            toast.error(errorMessage);
        },
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
    <Head :title="__('Feature Flags')" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        {{ __('Feature Flags') }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ __('Manage available features') }}
                    </p>
                </div>
            </div>
        </template>

        <div class="bg-card shadow-sm rounded-lg border border-border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[200px]">{{ __('Feature') }}</TableHead>
                        <TableHead>{{ __('Description') }}</TableHead>
                        <TableHead class="w-[100px]">{{ __('Status') }}</TableHead>
                        <TableHead class="w-[150px]">{{ __('Strategy') }}</TableHead>
                        <TableHead class="w-[100px]">{{ __('Rollout') }}</TableHead>
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
                            <template v-if="!feature.implemented">
                                <Badge variant="outline" class="bg-yellow-500/10 text-yellow-500">
                                    {{ __('Not Implemented') }}
                                </Badge>
                            </template>
                            <Switch
                                v-else
                                :model-value="feature.is_active"
                                :disabled="toggling === feature.name"
                                @update:model-value="toggleFeature(feature.name, feature.is_active)"
                            />
                        </TableCell>
                        <TableCell>
                            <Badge v-if="!feature.implemented" variant="outline" class="text-muted-foreground">
                                {{ __('Planned') }}
                            </Badge>
                            <Badge v-else :variant="getStrategyBadgeVariant(feature.strategy)">
                                {{ feature.strategy_label }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <span v-if="!feature.implemented" class="text-sm text-muted-foreground">
                                -
                            </span>
                            <span v-else-if="feature.strategy === 'percentage'" class="text-sm">
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
