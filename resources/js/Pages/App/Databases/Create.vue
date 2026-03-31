<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CredentialCollection } from '@/types/credential';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const props = defineProps<{
    credentials?: CredentialCollection;
}>();

const toast = useToast();

const form = useForm({
    name: '',
    display_name: '',
    description: '',
    host: 'localhost',
    port: 5432,
    database_name: '',
    is_active: true,
    credential_ids: [] as string[],
});

const submit = (): void => {
    form.post(route('app.databases.store'));
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
                            :class="{ 'border-destructive': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        <p class="text-xs text-muted-foreground">Apenas letras minúsculas, números, underline e hífen</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="database_name">Database Name *</Label>
                        <Input
                            id="database_name"
                            v-model="form.database_name"
                            placeholder="ex: dockabase_dev"
                            :class="{ 'border-destructive': form.errors.database_name }"
                        />
                        <p v-if="form.errors.database_name" class="text-sm text-destructive">{{ form.errors.database_name }}</p>
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
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                        Criar Database
                    </Button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
