import type { ReactNode } from 'react';

import { useCurrentUser } from '@/entities/session';
import { useTranslation } from '@/shared/i18n';

import { HelloView } from './hello-view.tsx';
import { RegistrationForm } from './registration-form.tsx';

export function HomePage() {
  const { t } = useTranslation();
  const currentUser = useCurrentUser();

  let content: ReactNode;

  if (currentUser.isPending) {
    content = <p className="text-sm text-muted-foreground">{t('home.loading')}</p>;
  } else if (currentUser.data) {
    content = <HelloView email={currentUser.data.email} />;
  } else {
    content = <RegistrationForm />;
  }

  return <main className="flex min-h-svh items-center justify-center p-4">{content}</main>;
}
