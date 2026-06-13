import { FormEvent, useState } from 'react';

import { Button } from '@/shared/ui/button';
import { Input } from '@/shared/ui/input';
import { Label } from '@/shared/ui/label';
import { useTranslation } from '@/shared/i18n';

import { useRegisterOrLogin } from '../model/useAuth';

export function RegistrationForm() {
  const { t } = useTranslation();
  const [email, setEmail] = useState('');
  const register = useRegisterOrLogin();

  const onSubmit = (event: FormEvent) => {
    event.preventDefault();
    register.mutate(email);
  };

  return (
    <form onSubmit={onSubmit} className="mx-auto flex w-full max-w-md flex-col gap-4 p-8">
      <h1 className="text-2xl font-semibold">{t('app.title')}</h1>
      <div className="grid gap-2">
        <Label htmlFor="email">{t('auth.emailLabel')}</Label>
        <Input
          id="email"
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          required
        />
      </div>
      <Button type="submit" disabled={register.isPending}>
        {t('auth.submit')}
      </Button>
    </form>
  );
}
