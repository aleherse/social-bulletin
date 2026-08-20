/** ADR-0018: the `:lang` URL segment is authoritative for the rendered language. */

export const SUPPORTED_LOCALES = ['en'] as const;

export type Locale = (typeof SUPPORTED_LOCALES)[number];

export const DEFAULT_LOCALE: Locale = 'en';

export function isSupportedLocale(value: string | undefined): value is Locale {
  return value !== undefined && (SUPPORTED_LOCALES as readonly string[]).includes(value);
}
