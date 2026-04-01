<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AlertCircle } from 'lucide-vue-next';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.post(route('password.force-change'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Trocar Senha" />

    <div class="min-h-screen flex items-center justify-center bg-background p-4">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                    <AlertCircle class="w-6 h-6 text-yellow-600" />
                </div>
                <CardTitle>Troque sua Senha</CardTitle>
                <CardDescription>
                    Por segurança, você precisa definir uma nova senha antes de continuar.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Nova senha</label>
                        <Input
                            v-model="form.password"
                            type="password"
                            placeholder="Digite a nova senha"
                            required
                            autofocus
                        />
                        <p v-if="form.errors.password" class="text-sm text-destructive">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Confirmar nova senha</label>
                        <Input
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Confirme a nova senha"
                            required
                        />
                        <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                    >
                        Trocar Senha
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
