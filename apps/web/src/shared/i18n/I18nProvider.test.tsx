import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { I18nProvider, useTranslation } from './index.ts';

function AppName() {
  const { t } = useTranslation();

  return <h1>{t('appName')}</h1>;
}

describe('I18nProvider', () => {
  it('resolves translations through the shared/i18n public API', () => {
    render(
      <I18nProvider>
        <AppName />
      </I18nProvider>,
    );

    expect(screen.getByRole('heading', { name: 'Social Bulletin' })).toBeInTheDocument();
  });
});
