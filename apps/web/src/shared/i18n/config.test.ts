import { afterEach, describe, expect, it, vi } from 'vitest';

describe('i18n config', () => {
  afterEach(() => {
    vi.resetModules();
    localStorage.clear();
    vi.unstubAllGlobals();
  });

  it('should initialise with en when no locale is stored or detected', async () => {
    vi.stubGlobal('navigator', { language: '' });

    const { i18n } = await import('./config');

    await i18n.changeLanguage(undefined);

    expect(i18n.language).toBe('en');
  });

  it('should resolve to en when navigator language is unsupported', async () => {
    vi.stubGlobal('navigator', { language: 'zh-CN' });

    const { i18n } = await import('./config');

    expect(i18n.language).toBe('en');
  });

  it('should resolve to the locale stored in localStorage', async () => {
    localStorage.setItem('locale', 'en');

    const { i18n } = await import('./config');

    expect(i18n.language).toBe('en');
  });

  it('should have the common namespace loaded', async () => {
    const { i18n } = await import('./config');

    expect(i18n.hasLoadedNamespace('common')).toBe(true);
  });

  it('should return a key string when a translation key exists', async () => {
    const { i18n } = await import('./config');

    expect(i18n.t('app.title')).toBeTruthy();
  });
});
