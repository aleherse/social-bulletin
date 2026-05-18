import type { ReactNode } from 'react';

import { I18nProvider } from '@/shared/i18n';

import { QueryProvider } from './query.tsx';

export function AppProviders({ children }: { children: ReactNode }) {
  return (
    <I18nProvider>
      <QueryProvider>{children}</QueryProvider>
    </I18nProvider>
  );
}
