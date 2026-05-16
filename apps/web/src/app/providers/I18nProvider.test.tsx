import '@testing-library/jest-dom/vitest';
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { useTranslation } from '../../shared/i18n';
import { I18nProvider } from './I18nProvider';

function TranslatedTitle() {
  const { t } = useTranslation();
  return <span>{t('app.title')}</span>;
}

describe('I18nProvider', () => {
  it('should render children without error', () => {
    render(
      <I18nProvider>
        <p>child content</p>
      </I18nProvider>,
    );

    expect(screen.getByText('child content')).toBeInTheDocument();
  });

  it('should provide translation context to children', () => {
    render(
      <I18nProvider>
        <TranslatedTitle />
      </I18nProvider>,
    );

    expect(screen.getByText('Social Bulletin')).toBeInTheDocument();
  });
});
