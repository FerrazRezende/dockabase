<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CredentialCollection } from '@/types/credential';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';

const props = defineProps<{
    credentials?: CredentialCollection;
}>();

const form = ref({
    name: '',
    display_name: '',
    description: '',
    host: 'localhost',
    port: 5432,
    database_name: '',
    is_active: true,
    credential_ids: [] as string[],
});

const loading = ref(false);
const errors = ref<Record<string, string>>({});

const getCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    return meta?.content || '';
};

const submit = async (): Promise<void> => {
    loading.value = true;
    errors.value = {};

    try {
        const response = await fetch(route('app.databases.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422) {
                errors.value = data.errors || {};
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        router.visit(route('app.databases.show', data.data.id));
    } catch (error) {
        console.error('Failed to create database:', error);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Criar Database" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('app.databases.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        Criar Database
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Adicione um novo database PostgreSQL
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-2xl bg-card shadow-sm rounded-lg border border-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="name">Nome *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="ex: dev"
                            :class="{ 'border-destructive': errors.name }"
                        />
                        <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
                        <p class="text-xs text-muted-foreground">Apenas letras minúsculas, números, underline e hífen</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="database_name">Database Name *</Label>
                        <Input
                            id="database_name"
                            v-model="form.database_name"
                            placeholder="ex: dockabase_dev"
                            :class="{ 'border-destructive': errors.database_name }"
                        />
                        <p v-if="errors.database_name" class="text-sm text-destructive">{{ errors.database_name }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="display_name">Display Name</Label>
                    <Input
                        id="display_name"
                        v-model="form.display_name"
                        placeholder="ex: Development"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="description">Descrição</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Descrição do database"
                        rows="3"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="host">Host</Label>
                        <Input
                            id="host"
                            v-model="form.host"
                            placeholder="localhost"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="port">Port</Label>
                        <Input
                            id="port"
                            v-model.number="form.port"
                            type="number"
                            placeholder="5432"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="space-y-0.5">
                        <Label>Ativo</Label>
                        <p class="text-xs text-muted-foreground">Database disponível para uso</p>
                    </div>
                    <Switch v-model:checked="form.is_active" />
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <Link :href="route('app.databases.index')">
                        <Button variant="outline" type="button">Cancelar</Button>
                    </Link>
                    <Button type="submit" :disabled="loading">
                        <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                        Criar Database
                    </Button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
