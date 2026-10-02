import { useTranslation } from '@/shared/i18n';

import { useCurrentUser } from '../api/session.ts';

import { HelloView } from './hello-view.tsx';
import { Hero } from './hero.tsx';
import { RegistrationForm } from './registration-form.tsx';

export function HomePage() {
  const { t } = useTranslation();
  const currentUser = useCurrentUser();

  return (
    <main className="flex min-h-svh flex-col items-center justify-center gap-8 p-4">
      <Hero />
      {currentUser.isPending ? (
        <p className="text-sm text-muted-foreground">{t('home.loading')}</p>
      ) : currentUser.data ? (
        <HelloView email={currentUser.data.email} />
      ) : (
        <RegistrationForm />
      )}
    </main>
  );
}
