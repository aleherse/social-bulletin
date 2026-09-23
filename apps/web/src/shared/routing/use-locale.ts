import { useParams } from 'react-router';

import { DEFAULT_LOCALE, isSupportedLocale } from '@/shared/i18n';

/** The locale from the `:lang` segment, or the default outside a localised route. */
export function useLocale(): string {
  const { lang } = useParams();

  return isSupportedLocale(lang) ? lang : DEFAULT_LOCALE;
}
