import { afterEach, describe, expect, it } from 'vitest';

describe('setLocale', () => {
  afterEach(() => {
    localStorage.clear();
  });

  it('should change the i18n language', async () => {
    const { i18n } = await import('./config');
    const { setLocale } = await import('./setLocale');

    await setLocale('en');

    expect(i18n.language).toBe('en');
  });

  it('should persist the locale to localStorage', async () => {
    const { setLocale } = await import('./setLocale');

    await setLocale('en');

    expect(localStorage.getItem('locale')).toBe('en');
  });
});
