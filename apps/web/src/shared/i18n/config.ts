import i18next from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import { initReactI18next } from 'react-i18next';
import enCommon from './locales/en/common.json';

export const i18n = i18next
  .createInstance()
  .use(LanguageDetector)
  .use(initReactI18next);

i18n.init({
  fallbackLng: 'en',
  supportedLngs: ['en'],
  defaultNS: 'common',
  resources: {
    en: {
      common: enCommon,
    },
  },
  detection: {
    order: ['localStorage', 'navigator'],
    lookupLocalStorage: 'locale',
    caches: ['localStorage'],
  },
  interpolation: {
    escapeValue: false,
  },
});

export default i18n;
