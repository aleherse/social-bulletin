import i18next from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import { initReactI18next } from 'react-i18next';
import en from './locales/en/common.json';

export const i18n = i18next.createInstance();

void i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    fallbackLng: 'en',
    supportedLngs: ['en'],
    interpolation: {
      escapeValue: false,
    },
    resources: {
      en: {
        common: en,
      },
    },
    defaultNS: 'common',
  });
