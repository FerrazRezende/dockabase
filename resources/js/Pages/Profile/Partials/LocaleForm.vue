<script setup lang="ts">
import { useLang, __ } from '@/composables/useLang';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ref, watch } from 'vue';

const { locale, availableLocales, setLocale } = useLang();

const activeLocale = ref(locale.value);

watch(locale, (newLocale) => {
    activeLocale.value = newLocale;
});

function onLocaleChange(newLocale: string) {
    activeLocale.value = newLocale;
    setLocale(newLocale);
}
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-medium text-foreground">
                {{ __('Language') }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ __('Choose your preferred language for the application.') }}
            </p>
        </header>

        <Tabs v-model="activeLocale" @update:model-value="onLocaleChange">
            <TabsList class="grid w-full grid-cols-3">
                <TabsTrigger
                    v-for="lang in availableLocales"
                    :key="lang.code"
                    :value="lang.code"
                    class="gap-2"
                >
                    <span class="text-base">{{ lang.flag }}</span>
                    <span>{{ lang.label }}</span>
                </TabsTrigger>
            </TabsList>
        </Tabs>
    </section>
</template>
