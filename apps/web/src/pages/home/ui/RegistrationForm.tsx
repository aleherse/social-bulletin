import { useState } from 'react';
import type { FormEvent } from 'react';

import { Button } from '@/shared/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card';
import { Field, FieldGroup, FieldLabel } from '@/shared/ui/field';
import { Input } from '@/shared/ui/input';
import { useTranslation } from '@/shared/i18n';

type RegistrationFormProps = {
  onSubmit: (email: string) => Promise<void>;
};

export function RegistrationForm({ onSubmit }: RegistrationFormProps) {
  const { t } = useTranslation();
  const [email, setEmail] = useState('');
  const [isSubmitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);

    try {
      await onSubmit(email);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t('appName')}</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit}>
          <FieldGroup>
            <Field>
              <FieldLabel htmlFor="email">{t('registration.emailLabel')}</FieldLabel>
              <Input
                id="email"
                name="email"
                type="email"
                autoComplete="email"
                required
                value={email}
                onChange={(event) => setEmail(event.target.value)}
              />
            </Field>
            <Button type="submit" disabled={isSubmitting}>
              {t('registration.submit')}
            </Button>
          </FieldGroup>
        </form>
      </CardContent>
    </Card>
  );
}
