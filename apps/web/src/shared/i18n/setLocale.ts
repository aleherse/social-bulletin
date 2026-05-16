import { i18n } from './config';

export async function setLocale(lang: string): Promise<void> {
  localStorage.setItem('locale', lang);
  await i18n.changeLanguage(lang);
}
