import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

export interface LocaleOption {
  code: string
  label: string
  flag: string
}

export function useLang() {
  const page = usePage()

  const locale = computed(() => (page.props.locale as string) || 'pt')

  const availableLocales: LocaleOption[] = [
    { code: 'pt', label: 'Português', flag: '🇧🇷' },
    { code: 'en', label: 'English', flag: '🇺🇸' },
    { code: 'es', label: 'Español', flag: '🇪🇸' },
  ]

  const setLocale = (newLocale: string) => {
    router.patch(route('profile.locale.update'), { locale: newLocale })
  }

  const currentLocale = computed(() =>
    availableLocales.find((l) => l.code === locale.value)
  )

  return {
    locale,
    availableLocales,
    currentLocale,
    setLocale,
  }
}
