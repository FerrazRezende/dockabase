import { usePage } from '@inertiajs/vue3'

export type TranslationParams = Record<string, string | number>

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

/**
 * Translate a key with pluralization support.
 * @param key - The translation key
 * @param count - The count to determine pluralization
 * @param params - Optional parameters to replace
 * @returns The translated string for the count
 */
export function transChoice(key: string, count: number, params?: TranslationParams): string {
  const translated = translations.value[key]

  if (!translated) {
    console.warn(`[Translation] Missing key: ${key}`)
    return key
  }

  const parts = translated.split('|')

  // Simple pluralization: first part is singular, second is plural
  if (parts.length === 1) {
    return parts[0]
  }

  // Handle count-based pluralization: {0}|{1}|[2,*]
  const singular = parts[0]
  const plural = parts[1] || parts[0]

  if (count === 1) {
    return replaceParams(singular, params)
  }
  return replaceParams(plural, params)
}

function replaceParams(text: string, params?: TranslationParams): string {
  if (!params) return text

  return text.replace(/:(\w+)/g, (_match, paramKey) => {
    return params[paramKey]?.toString() || `:${paramKey}`
  })
}
