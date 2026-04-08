import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

export interface LocaleOption {
  code: string
  label: string
  flag: string
}

export type TranslationParams = Record<string, string | number>

export function useLang() {
  const page = usePage()

  const locale = computed(() => (page.props.locale as string) || 'pt')

  const translations = computed(() => {
    return (page.props.translations || {}) as Record<string, string>
  })

  const availableLocales: LocaleOption[] = [
    { code: 'pt', label: 'Português', flag: '🇧🇷' },
    { code: 'en', label: 'English', flag: '🇺🇸' },
    { code: 'es', label: 'Español', flag: '🇪🇸' },
  ]

  const setLocale = (newLocale: string) => {
    // Check if user is authenticated
    const isAuthenticated = !!page.props.auth?.user

    if (isAuthenticated) {
      // Authenticated users: save to database via profile route
      router.patch(route('profile.locale.update'), { locale: newLocale })
    } else {
      // Guests: save to session via public route
      // Use fetch directly for guests since Inertia router doesn't return Promise
      fetch('/locale', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ locale: newLocale }),
      }).then(() => {
        // Force page reload to apply new locale
        window.location.reload()
      })
    }
  }

  const currentLocale = computed(() =>
    availableLocales.find((l) => l.code === locale.value)
  )

  return {
    locale,
    translations,
    availableLocales,
    currentLocale,
    setLocale,
  }
}

/**
 * Translate a key using the current locale's translations.
 * @param key - The translation key (original string)
 * @param params - Optional parameters to replace in the translation
 * @returns The translated string or the key if not found
 */
export function __(key: string, params?: TranslationParams): string {
  const page = usePage()
  const translations = (page.props.translations || {}) as Record<string, string>

  const translated = translations[key]

  if (!translated) {
    return key
  }

  // Replace parameters :name → actual value
  if (params) {
    return translated.replace(/:(\w+)/g, (_match, paramKey) => {
      return params[paramKey]?.toString() || `:${paramKey}`
    })
  }

  return translated
}
