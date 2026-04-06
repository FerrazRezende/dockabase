<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { ArrowLeft, Loader2, Shield, Key } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { __ } from '@/utils/lang';

interface Permission {
    id: number;
    name: string;
    guard_name: string;
}

interface Role {
    id?: number;
    name?: string;
    permissions?: Permission[];
}

interface Props {
    role?: Role;
    allPermissions: Permission[];
}

const props = defineProps<Props>();

const toast = useToast();

const isEditing = computed(() => !!props.role?.id);
const form = ref({
    name: props.role?.name ?? '',
    permission_ids: props.role?.permissions?.map((p) => p.id) ?? [],
});
const isSaving = ref(false);

// Accordion state - all categories open by default
const openCategories = ref<string[]>(
    Object.keys(
        (props.allPermissions ?? []).reduce((groups: Record<string, Permission[]>, p) => {
            const cat = p.name.split('.')[0];
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(p);
            return groups;
        }, {})
    )
);

// Group permissions by category
const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};

    (props.allPermissions ?? []).forEach((permission) => {
        const category = permission.name.split('.')[0];
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(permission);
    });

    return groups;
});

const getCategoryLabel = (category: string): string => {
    const labels: Record<string, string> = {
        databases: 'Databases',
        schemas: 'Schemas',
        credentials: 'Credentials',
        tables: 'Tables',
        users: 'Users',
    };
    return labels[category] || category.charAt(0).toUpperCase() + category.slice(1);
};

const isPermissionSelected = (permissionId: number): boolean => {
    return form.value.permission_ids.includes(permissionId);
};

const togglePermission = (permissionId: number): void => {
    const index = form.value.permission_ids.indexOf(permissionId);
    if (index > -1) {
        form.value.permission_ids.splice(index, 1);
    } else {
        form.value.permission_ids.push(permissionId);
    }
};

const selectAllInCategory = (category: string): void => {
    const permissions = groupedPermissions.value[category] ?? [];
    const allSelected = permissions.every((p) => form.value.permission_ids.includes(p.id));

    if (allSelected) {
        // Deselect all
        permissions.forEach((p) => {
            const index = form.value.permission_ids.indexOf(p.id);
            if (index > -1) {
                form.value.permission_ids.splice(index, 1);
            }
        });
    } else {
        // Select all
        permissions.forEach((p) => {
            if (!form.value.permission_ids.includes(p.id)) {
                form.value.permission_ids.push(p.id);
            }
        });
    }
};

const isAllSelectedInCategory = (category: string): boolean => {
    const permissions = groupedPermissions.value[category] ?? [];
    if (permissions.length === 0) return false;
    return permissions.every((p) => form.value.permission_ids.includes(p.id));
};

const isSomeSelectedInCategory = (category: string): boolean => {
    const permissions = groupedPermissions.value[category] ?? [];
    return permissions.some((p) => form.value.permission_ids.includes(p.id));
};

const submit = (): void => {
    isSaving.value = true;

    if (isEditing.value) {
        router.put(
            route('system.roles.update', props.role?.id),
            {
                name: form.value.name,
                permissions: form.value.permission_ids,
            },
            {
                onSuccess: () => {
                    toast.success(__('Role updated successfully'));
                },
                onError: () => {
                    toast.error(__('Error updating role. Please try again.'));
                },
                onFinish: () => {
                    isSaving.value = false;
                },
            }
        );
    } else {
        router.post(
            route('system.roles.store'),
            {
                name: form.value.name,
                permissions: form.value.permission_ids,
            },
            {
                onSuccess: () => {
                    toast.success(__('Role created successfully'));
                },
                onError: () => {
                    toast.error(__('Error updating role. Please try again.'));
                },
                onFinish: () => {
                    isSaving.value = false;
                },
            }
        );
    }
};
</script>

<template>
    <Head :title="isEditing ? __('Edit Role') : __('New Role')" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('system.permissions.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="w-4 h-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        {{ isEditing ? __('Edit Role') : __('New Role') }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ isEditing ? __('Change the name and permissions of the role.') : __('Create a new role and define its permissions.') }}
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl">
            <div class="grid gap-6 lg:grid-cols-4">
                <!-- Left Column - Permissions -->
                <div class="lg:col-span-3 space-y-4 order-2 lg:order-1">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Key class="w-5 h-5" />
                                {{ __('Permissions') }}
                            </CardTitle>
                            <CardDescription>
                                {{ __('Select the permissions this role should have.') }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="Object.keys(groupedPermissions).length > 0">
                                <Accordion type="multiple" v-model="openCategories" class="space-y-4">
                                    <AccordionItem
                                        v-for="(permissions, category) in groupedPermissions"
                                        :key="category"
                                        :value="category"
                                    >
                                        <AccordionTrigger class="hover:no-underline px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <h4 class="text-sm font-semibold text-foreground">
                                                    {{ getCategoryLabel(category) }}
                                                </h4>
                                                <Badge variant="secondary" class="text-xs">
                                                    {{ permissions.filter(p => form.permission_ids.includes(p.id)).length }}/{{ permissions.length }}
                                                </Badge>
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent class="px-4 pb-4 pt-0">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs text-muted-foreground">
                                                    {{ __('Select :category permissions', { category: getCategoryLabel(category) }) }}
                                                </span>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 text-xs"
                                                    @click="selectAllInCategory(category)"
                                                >
                                                    {{ isAllSelectedInCategory(category) ? __('Unmark all') : __('Mark all') }}
                                                </Button>
                                            </div>
                                            <div class="grid gap-2 md:grid-cols-2">
                                                <label
                                                    v-for="permission in permissions"
                                                    :key="permission.id"
                                                    class="flex items-center gap-3 p-3 rounded border transition-colors cursor-pointer hover:bg-muted/30"
                                                    :class="[
                                                        isPermissionSelected(permission.id)
                                                            ? 'bg-primary/10 border-primary'
                                                            : 'bg-background',
                                                    ]"
                                                >
                                                    <Checkbox
                                                        :modelValue="isPermissionSelected(permission.id)"
                                                        :disabled="isSaving"
                                                        @update:modelValue="() => togglePermission(permission.id)"
                                                    />
                                                    <span class="text-sm font-mono">{{ permission.name }}</span>
                                                </label>
                                            </div>
                                        </AccordionContent>
                                    </AccordionItem>
                                </Accordion>
                            </div>
                            <p v-else class="text-muted-foreground text-sm text-center py-4">
                                {{ __('No permission available') }}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column - Name Card -->
                <div class="lg:col-span-1 order-1 lg:order-2">
                    <Card class="sticky top-24">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Shield class="w-5 h-5" />
                                {{ __('Role Information') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-2">
                                <label class="text-sm font-medium">{{ __('Name') }}</label>
                                <Input
                                    v-model="form.name"
                                    :placeholder="__('ex: Developer, Manager, Admin')"
                                    :disabled="isSaving"
                                />
                            </div>

                            <!-- Summary of selected permissions -->
                            <div class="pt-4 border-t">
                                <p class="text-sm text-muted-foreground mb-2">
                                    {{ form.permission_ids.length }} {{ __('permissions selected') }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col gap-2 pt-4 border-t">
                                <Link :href="route('system.permissions.index')" class="w-full">
                                    <Button variant="outline" class="w-full" :disabled="isSaving">
                                        Cancelar
                                    </Button>
                                </Link>
                                <Button @click="submit" class="w-full" :disabled="isSaving || !form.name.trim()">
                                    <Loader2 v-if="isSaving" class="w-4 h-4 mr-2 animate-spin" />
                                    {{ isEditing ? 'Salvar' : 'Criar' }} Role
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
