<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';

const form = ref({
    name: '',
    permission: 'read-write',
    description: '',
    user_ids: [] as number[],
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
        const response = await fetch(route('app.credentials.store'), {
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

        router.visit(route('app.credentials.show', data.data.id));
    } catch (error) {
        console.error('Failed to create credential:', error);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Criar Credencial" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('app.credentials.index')">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        Criar Credencial
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Crie uma nova credencial de acesso
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-2xl bg-card shadow-sm rounded-lg border border-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="name">Nome *</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="ex: Dev Team"
                        :class="{ 'border-destructive': errors.name }"
                    />
                    <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="permission">Permissão *</Label>
                    <Select v-model="form.permission">
                        <SelectTrigger>
                            <SelectValue placeholder="Selecione a permissão" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="read">Read Only</SelectItem>
                            <SelectItem value="write">Write Only</SelectItem>
                            <SelectItem value="read-write">Read & Write</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="errors.permission" class="text-sm text-destructive">{{ errors.permission }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="description">Descrição</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Descrição da credencial"
                        rows="3"
                    />
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <Link :href="route('app.credentials.index')">
                        <Button variant="outline" type="button">Cancelar</Button>
                    </Link>
                    <Button type="submit" :disabled="loading">
                        <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                        Criar Credencial
                    </Button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
