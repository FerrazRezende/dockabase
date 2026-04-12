<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { __ } from '@/composables/useLang';
import { computed } from 'vue';

defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
}>();

const user = usePage().props.auth.user as { name: string; email: string; email_verified_at?: string | null; bio?: string | null };

const form = useForm({
    name: user.name,
    email: user.email,
    bio: user.bio ?? '',
});

const MAX_BIO_LENGTH = 500;

const remainingCharacters = computed(() => {
    return MAX_BIO_LENGTH - (form.bio?.length ?? 0);
});
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-medium text-foreground">
                {{ __('Profile Information') }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ __('Update your account\'s profile information and email address.') }}
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-6">
            <div class="space-y-2">
                <Label for="name">{{ __('Name') }}</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    :disabled="form.processing"
                    :placeholder="__('Your name')"
                    autocomplete="name"
                    required
                />
                <p v-if="form.errors.name" class="text-sm text-destructive">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="email">{{ __('Email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    v-model="form.email"
                    :disabled="form.processing"
                    :placeholder="__('your@email.com')"
                    autocomplete="username"
                    required
                />
                <p v-if="form.errors.email" class="text-sm text-destructive">
                    {{ form.errors.email }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="bio">{{ __('About me') }}</Label>
                <Textarea
                    id="bio"
                    v-model="form.bio"
                    :disabled="form.processing"
                    :placeholder="__('Tell us a little about yourself...')"
                    rows="4"
                    :maxlength="MAX_BIO_LENGTH"
                    class="resize-none"
                />
                <div class="flex items-center justify-between">
                    <p v-if="form.errors.bio" class="text-sm text-destructive">
                        {{ form.errors.bio }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        {{ __('Optional') }}
                    </p>
                    <p :class="[
                        'text-sm',
                        remainingCharacters < 0 ? 'text-destructive' : remainingCharacters < 50 ? 'text-warning' : 'text-muted-foreground'
                    ]">
                        {{ remainingCharacters }}/{{ MAX_BIO_LENGTH }}
                    </p>
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <Alert>
                    <AlertDescription>
                        <span class="text-sm">
                            {{ __('Your email address is unverified.') }}
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-sm underline hover:text-primary focus:outline-none"
                            >
                                {{ __('Click here to re-send the verification email.') }}
                            </Link>
                        </span>
                    </AlertDescription>
                </Alert>

                <Alert v-if="status === 'verification-link-sent'" class="mt-2">
                    <AlertDescription>
                        <span class="text-sm font-medium">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </span>
                    </AlertDescription>
                </Alert>
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? __('Saving...') : __('Save') }}
                </Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">
                        {{ __('Saved.') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
