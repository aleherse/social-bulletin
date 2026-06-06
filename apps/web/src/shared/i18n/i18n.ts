import i18next from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import { initReactI18next } from 'react-i18next';

import common from './locales/en/common.json';

export const resources = {
  en: {
    common,
  },
} as const;

export const i18n = i18next.createInstance();

void i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources,
    fallbackLng: 'en',
    supportedLngs: ['en'],
    defaultNS: 'common',
    interpolation: {
      escapeValue: false,
    },
  });
