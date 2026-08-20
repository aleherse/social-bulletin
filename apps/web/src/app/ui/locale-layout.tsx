import { useEffect } from 'react';
import { Outlet, useParams } from 'react-router';

import { NotFoundPage } from '@/pages/not-found';
import { isSupportedLocale, useTranslation } from '@/shared/i18n';

/** ADR-0018: the `:lang` segment is authoritative for the rendered language. */
export function LocaleLayout() {
  const { lang } = useParams();
  const { i18n } = useTranslation();
  const supported = isSupportedLocale(lang);

  useEffect(() => {
    if (supported && i18n.resolvedLanguage !== lang) {
      void i18n.changeLanguage(lang);
    }
  }, [supported, lang, i18n]);

  // An unsupported locale is reported, never silently rewritten.
  return supported ? <Outlet /> : <NotFoundPage />;
}
