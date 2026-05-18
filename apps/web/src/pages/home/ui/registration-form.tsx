import { useState } from 'react';
import type { FormEvent } from 'react';

import { SessionError } from '@/shared/api';
import { useTranslation } from '@/shared/i18n';
import {
  Button,
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Input,
  Label,
} from '@/shared/ui';

import { useCreateSession } from '../api/session.ts';
import { isValidEmail } from '../model/email.ts';

export function RegistrationForm() {
  const { t } = useTranslation();
  const [email, setEmail] = useState('');
  const [validationError, setValidationError] = useState<string | null>(null);
  const createSession = useCreateSession();

  const serverError = createSession.isError
    ? createSession.error instanceof SessionError
      ? createSession.error.message
      : t('home.form.requestFailed')
    : null;
  const errorMessage = validationError ?? serverError;

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!isValidEmail(email)) {
      setValidationError(t('home.form.invalidEmail'));

      return;
    }

    setValidationError(null);
    createSession.mutate(email.trim());
  };

  return (
    <Card className="w-full max-w-sm">
      <CardHeader>
        <CardTitle>{t('home.form.title')}</CardTitle>
        <CardDescription>{t('home.form.description')}</CardDescription>
      </CardHeader>
      <CardContent>
        <form noValidate className="flex flex-col gap-6" onSubmit={handleSubmit}>
          <div className="flex flex-col gap-2">
            <Label htmlFor="email">{t('home.form.emailLabel')}</Label>
            <Input
              id="email"
              type="email"
              placeholder={t('home.form.emailPlaceholder')}
              value={email}
              aria-invalid={errorMessage !== null}
              onChange={(event) => setEmail(event.target.value)}
            />
            {errorMessage !== null && (
              <p role="alert" className="text-sm text-destructive">
                {errorMessage}
              </p>
            )}
          </div>
          <Button type="submit" disabled={createSession.isPending}>
            {t('home.form.submit')}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
