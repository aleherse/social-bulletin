import { useTranslation } from '@/shared/i18n';
import { Link } from '@/shared/routing';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui';

export function NotFoundPage() {
  const { t } = useTranslation();

  return (
    <main className="flex min-h-svh items-center justify-center p-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle>{t('app.notFound.title')}</CardTitle>
          <CardDescription>{t('app.notFound.description')}</CardDescription>
        </CardHeader>
        <CardContent>
          <Link className="text-sm underline" to="/">
            {t('app.notFound.home')}
          </Link>
        </CardContent>
      </Card>
    </main>
  );
}
