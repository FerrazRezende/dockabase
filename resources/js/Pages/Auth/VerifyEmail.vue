<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { __ } from '@/composables/useLang';
import { Button } from '@/components/ui/button';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head :title="__('Email Verification')" />

        <div class="mb-4 text-sm text-muted-foreground">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </div>

        <div
            class="mb-4 text-sm font-medium text-success"
            v-if="verificationLinkSent"
        >
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ __('Resend Verification Email') }}
                </Button>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-muted-foreground underline hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                >
                    {{ __('Log Out') }}
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
