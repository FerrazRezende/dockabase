<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { __ } from '@/composables/useLang';
import { ref, computed } from 'vue';
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
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Rocket, RotateCcw } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { FeatureCollection } from '@/types/feature';
import { useToast } from '@/composables/useToast';

const props = defineProps<{
    features: FeatureCollection;
}>();

const page = usePage();
const appEnv = computed(() => page.props.appEnv as string);
const isDev = computed(() => appEnv.value === 'local');

const activeFeatures = computed(() =>
    props.features.data.filter(f => !f.launched),
);

const launchedFeatures = computed(() =>
    props.features.data.filter(f => f.launched),
);

const toggling = ref<string | null>(null);
const launching = ref<string | null>(null);
const showLaunchDialog = ref(false);
const launchTarget = ref<string | null>(null);
const restoring = ref(false);
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

const confirmLaunch = (featureName: string) => {
    launchTarget.value = featureName;
    showLaunchDialog.value = true;
};

const launchFeature = () => {
    if (!launchTarget.value) return;

    launching.value = launchTarget.value;
    router.delete(route('system.features.launch', launchTarget.value), {
        preserveScroll: true,
        only: ['features'],
        onSuccess: () => {
            toast.success(__('Feature launched successfully'));
        },
        onError: () => {
            toast.error(__('Error launching feature'));
        },
        onFinish: () => {
            launching.value = null;
            launchTarget.value = null;
            showLaunchDialog.value = false;
        },
    });
};

const restoreFlags = () => {
    restoring.value = true;
    router.post(route('system.features.restore'), {}, {
        preserveScroll: true,
        only: ['features'],
        onSuccess: () => {
            toast.success(__('Flags restored successfully'));
        },
        onError: () => {
            toast.error(__('Error restoring flags'));
        },
        onFinish: () => {
            restoring.value = false;
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
                <Button
                    v-if="isDev && launchedFeatures.length > 0"
                    variant="outline"
                    class="gap-2"
                    :disabled="restoring"
                    @click="restoreFlags"
                >
                    <RotateCcw class="h-4 w-4" />
                    {{ restoring ? __('Restoring...') : __('Restore Flags') }}
                </Button>
            </div>
        </template>

        <Tabs default-value="features" class="space-y-4">
            <TabsList>
                <TabsTrigger value="features">
                    {{ __('Features') }}
                </TabsTrigger>
                <TabsTrigger value="launched">
                    {{ __('Launched') }}
                    <Badge v-if="launchedFeatures.length > 0" variant="secondary" class="ml-2">
                        {{ launchedFeatures.length }}
                    </Badge>
                </TabsTrigger>
            </TabsList>

            <!-- Features Tab -->
            <TabsContent value="features">
                <div class="bg-card shadow-sm rounded-lg border border-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[200px]">{{ __('Feature') }}</TableHead>
                                <TableHead>{{ __('Description') }}</TableHead>
                                <TableHead class="w-[100px]">{{ __('Status') }}</TableHead>
                                <TableHead class="w-[150px]">{{ __('Strategy') }}</TableHead>
                                <TableHead class="w-[100px]">{{ __('Rollout') }}</TableHead>
                                <TableHead class="w-[80px]" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="feature in activeFeatures"
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
                                    <span v-else-if="feature.strategy === 'all'" class="text-sm text-success">
                                        100%
                                    </span>
                                    <span v-else class="text-sm text-muted-foreground">
                                        -
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Button
                                        v-if="feature.implemented && feature.is_active"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1 text-xs"
                                        :disabled="launching === feature.name"
                                        @click="confirmLaunch(feature.name)"
                                    >
                                        <Rocket class="h-3.5 w-3.5" />
                                        {{ __('Launch') }}
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="activeFeatures.length === 0">
                                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                                    {{ __('No features available') }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </TabsContent>

            <!-- Launched Tab -->
            <TabsContent value="launched">
                <div class="bg-card shadow-sm rounded-lg border border-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[200px]">{{ __('Feature') }}</TableHead>
                                <TableHead>{{ __('Description') }}</TableHead>
                                <TableHead class="w-[100px]">{{ __('Status') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="feature in launchedFeatures"
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
                                    <Badge variant="default" class="badge-success">
                                        {{ __('Launched') }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="launchedFeatures.length === 0">
                                <TableCell colspan="3" class="text-center py-8 text-muted-foreground">
                                    {{ __('No launched features') }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </TabsContent>
        </Tabs>

        <!-- Launch Confirmation Dialog -->
        <Dialog v-model:open="showLaunchDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ __('Launch Feature') }}</DialogTitle>
                    <DialogDescription>
                        {{ __('Are you sure you want to launch this feature?') }}
                    </DialogDescription>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    {{ __('This will remove the feature flag. The feature stays active for all users.') }}
                </p>
                <DialogFooter>
                    <Button variant="outline" @click="showLaunchDialog = false">
                        {{ __('Cancel') }}
                    </Button>
                    <Button
                        @click="launchFeature"
                        :disabled="launching !== null"
                    >
                        <Rocket class="h-4 w-4 mr-2" />
                        {{ launching ? __('Launching...') : __('Launch') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
