import { useTranslation } from '@/shared/i18n';
import { Button, Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui';

import { useLogout } from '../api/session.ts';

export function HelloView({ email }: { email: string }) {
  const { t } = useTranslation();
  const logout = useLogout();

  return (
    <Card className="w-full max-w-sm">
      <CardHeader>
        <CardTitle>{t('home.hello.greeting', { email })}</CardTitle>
        <CardDescription>{t('home.hello.description')}</CardDescription>
      </CardHeader>
      <CardContent className="flex items-center gap-3">
        <a
          className="rounded-lg bg-primary px-2.5 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/80"
          href="#/movements"
        >
          {t('home.hello.movements')}
        </a>
        <Button
          variant="outline"
          disabled={logout.isPending}
          onClick={() => {
            logout.mutate();
          }}
        >
          {t('home.hello.logout')}
        </Button>
      </CardContent>
    </Card>
  );
}
