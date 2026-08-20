import { Navigate, Outlet, useLocation } from 'react-router';

import { useCurrentUser } from '@/entities/session';
import { useTranslation } from '@/shared/i18n';
import { localePath, useLocale } from '@/shared/routing';

/** ADR-0018: only a settled `null` session redirects; a pending one waits. */
export function RequireSession() {
  const { t } = useTranslation();
  const locale = useLocale();
  const location = useLocation();
  const currentUser = useCurrentUser();

  if (currentUser.isPending) {
    return <p className="text-sm text-muted-foreground">{t('app.loading')}</p>;
  }

  if (currentUser.data === null || currentUser.data === undefined) {
    const prefix = `/${locale}`;
    const attempted = location.pathname.startsWith(prefix)
      ? location.pathname.slice(prefix.length) || '/'
      : location.pathname;

    return (
      <Navigate to={`${localePath(locale, '/')}?next=${encodeURIComponent(attempted)}`} replace />
    );
  }

  return <Outlet />;
}
