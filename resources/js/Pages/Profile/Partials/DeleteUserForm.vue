<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { __ } from '@/composables/useLang';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-foreground">
                {{ __('Delete Account') }}
            </h2>

            <p class="mt-1 text-sm text-muted-foreground">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
            </p>
        </header>

        <Button variant="destructive" @click="confirmUserDeletion">
            {{ __('Delete Account') }}
        </Button>

        <Dialog :open="confirmingUserDeletion" @update:open="(val) => { if (!val) closeModal() }">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ __('Are you sure you want to delete your account?') }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2">
                    <Label for="password" class="sr-only">{{ __('Password') }}</Label>
                    <Input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        :placeholder="__('Password')"
                        @keyup.enter="deleteUser"
                    />
                    <p v-if="form.errors.password" class="text-sm text-destructive">
                        {{ form.errors.password }}
                    </p>
                </div>

                <DialogFooter>
                    <Button variant="secondary" @click="closeModal">
                        {{ __('Cancel') }}
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ __('Delete Account') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </section>
</template>
