import { createInstance } from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import { initReactI18next } from 'react-i18next';

import commonEn from './locales/en/common.json';

export const i18n = createInstance().use(LanguageDetector).use(initReactI18next);

void i18n.init({
  resources: {
    en: {
      common: commonEn,
    },
  },
  fallbackLng: 'en',
  defaultNS: 'common',
  interpolation: {
    // React already escapes rendered values.
    escapeValue: false,
  },
});
