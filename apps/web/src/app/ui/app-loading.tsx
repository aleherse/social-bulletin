import { useTranslation } from '@/shared/i18n';

/** Shown while the router resolves a lazily loaded route chunk. */
export function AppLoading() {
  const { t } = useTranslation();

  return (
    <main className="flex min-h-svh items-center justify-center p-4">
      <p className="text-sm text-muted-foreground">{t('app.loading')}</p>
    </main>
  );
}
