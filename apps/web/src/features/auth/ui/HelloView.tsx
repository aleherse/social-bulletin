import { Button } from '@/shared/ui/button';
import { useTranslation } from '@/shared/i18n';

import { useLogout } from '../model/useAuth';

type HelloViewProps = {
  email: string;
};

export function HelloView({ email }: HelloViewProps) {
  const { t } = useTranslation();
  const logout = useLogout();

  return (
    <section className="mx-auto flex w-full max-w-md flex-col gap-4 p-8">
      <h1 className="text-2xl font-semibold">{t('hello.greeting', { email })}</h1>
      <Button variant="ghost" onClick={() => logout.mutate()}>
        {t('auth.logout')}
      </Button>
    </section>
  );
}
