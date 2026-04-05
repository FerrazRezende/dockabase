<script setup>
import { useLang } from '@/composables/useLang';
import { useForm, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

const { locale, availableLocales, setLocale } = useLang();
const page = usePage();

const form = useForm({
    locale: locale.value,
});

// Watch for locale changes from useLang
watch(locale, (newLocale) => {
    form.locale = newLocale;
});

function updateLocale() {
    setLocale(form.locale);
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Language') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Choose your preferred language for the application.') }}
            </p>
        </header>

        <form
            @submit.prevent="updateLocale()"
            class="mt-6 space-y-6"
        >
            <div>
                <label for="locale" class="block text-sm font-medium text-gray-700">
                    {{ __('Language') }}
                </label>

                <select
                    id="locale"
                    v-model="form.locale"
                    @change="updateLocale()"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option
                        v-for="lang in availableLocales"
                        :key="lang.code"
                        :value="lang.code"
                    >
                        {{ lang.flag }} {{ lang.label }}
                    </option>
                </select>
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                >
                    {{ __('Save') }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        {{ __('Saved.') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
