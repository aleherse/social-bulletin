import { createInstance } from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import { initReactI18next } from 'react-i18next';

import { DEFAULT_LOCALE, SUPPORTED_LOCALES } from './locale.ts';
import commonEn from './locales/en/common.json';

export const i18n = createInstance().use(LanguageDetector).use(initReactI18next);

void i18n.init({
  resources: {
    en: {
      common: commonEn,
    },
  },
  supportedLngs: SUPPORTED_LOCALES,
  fallbackLng: DEFAULT_LOCALE,
  defaultNS: 'common',
  // ADR-0018: the first path segment is the locale, so it outranks the browser.
  detection: {
    order: ['path', 'navigator'],
    lookupFromPathIndex: 0,
  },
  interpolation: {
    // React already escapes rendered values.
    escapeValue: false,
  },
});
