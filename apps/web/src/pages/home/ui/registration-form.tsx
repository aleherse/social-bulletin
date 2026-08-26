import { useState } from 'react';
import type { FormEvent } from 'react';

import { useCreateSession } from '@/entities/session';
import { SessionError } from '@/shared/api';
import { useTranslation } from '@/shared/i18n';
import { useNavigate, useSearchParams } from '@/shared/routing';
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

import { isValidEmail } from '../model/email.ts';

export function RegistrationForm() {
  const { t } = useTranslation();
  const [email, setEmail] = useState('');
  const [validationError, setValidationError] = useState<string | null>(null);
  const createSession = useCreateSession();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const serverError = sessionErrorMessage(
    createSession.error,
    createSession.isError,
    t('home.form.requestFailed'),
  );
  const errorMessage = validationError ?? serverError;

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!isValidEmail(email)) {
      setValidationError(t('home.form.invalidEmail'));

      return;
    }

    setValidationError(null);
    createSession.mutate(email.trim(), {
      onSuccess: () => {
        // ADR-0018: a guard sent the visitor here, so return them to where they were going.
        const next = searchParams.get('next');

        if (next !== null) {
          navigate(next);
        }
      },
    });
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

function sessionErrorMessage(error: unknown, isError: boolean, fallback: string): string | null {
  if (!isError) {
    return null;
  }

  return error instanceof SessionError ? error.message : fallback;
}
