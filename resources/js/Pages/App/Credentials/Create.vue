<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { __ } from '@/composables/useLang';
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
import { ArrowLeft, Loader2, X } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();

const form = useForm({
    name: '',
    permission: 'read-write',
    description: '',
    user_ids: [] as number[],
});

const submit = (): void => {
    form.post(route('app.credentials.store'), {
        onSuccess: () => {
            toast.success(__('Credential created successfully'));
        },
        onError: () => {
            toast.error(__('Error creating credential'));
        },
    });
};
</script>

<template>
    <Head :title="__('Create credential')" />

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
                        {{ __('Create credential') }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ __('Create a new access credential') }}
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-2xl bg-card shadow-sm rounded-lg border border-border p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="name">{{ __('Name') }} *</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        :placeholder="__('ex: Dev Team')"
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="permission">{{ __('Permission') }} *</Label>
                    <Select v-model="form.permission">
                        <SelectTrigger>
                            <SelectValue :placeholder="__('Select the permission')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="read">{{ __('Read Only') }}</SelectItem>
                            <SelectItem value="write">{{ __('Write Only') }}</SelectItem>
                            <SelectItem value="read-write">{{ __('Read & Write') }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.permission" class="text-sm text-destructive">{{ form.errors.permission }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="description">{{ __('Description') }}</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        :placeholder="__('Credential description')"
                        rows="3"
                    />
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <Link :href="route('app.credentials.index')">
                        <Button variant="outline" type="button">
                            <X class="w-4 h-4 mr-2" />
                            {{ __('Cancel') }}
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                        {{ __('Create Credential') }}
                    </Button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
