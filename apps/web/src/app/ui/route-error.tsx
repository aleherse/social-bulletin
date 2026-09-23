import { useTranslation } from '@/shared/i18n';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui';

/** Rendered by the router when a route throws, instead of a blank document. */
export function RouteError() {
  const { t } = useTranslation();

  return (
    <main className="flex min-h-svh items-center justify-center p-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle>{t('app.error.title')}</CardTitle>
          <CardDescription>{t('app.error.description')}</CardDescription>
        </CardHeader>
        <CardContent>
          <a className="text-sm underline" href="/">
            {t('app.error.reload')}
          </a>
        </CardContent>
      </Card>
    </main>
  );
}
