import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { apiFetch } from '@/shared/api/client';
import { useTranslation } from '@/shared/i18n';

interface RegistrationPageProps {
  onSuccess: (email: string) => void;
}

interface RegisterResponse {
  email: string;
}

export function RegistrationPage({ onSuccess }: RegistrationPageProps) {
  const { t } = useTranslation();
  const [email, setEmail] = useState('');

  const mutation = useMutation({
    mutationFn: (emailValue: string) =>
      apiFetch<RegisterResponse>('/api/register', {
        method: 'POST',
        body: JSON.stringify({ email: emailValue }),
      }),
    onSuccess: (data) => {
      onSuccess(data.email);
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate(email);
  };

  return (
    <div className="flex min-h-screen items-center justify-center p-4">
      <div className="w-full max-w-sm space-y-6">
        <h1 className="text-2xl font-semibold text-center">{t('registration.title')}</h1>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <label htmlFor="email" className="block text-sm font-medium">
              {t('registration.emailLabel')}
            </label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder={t('registration.emailPlaceholder')}
              required
              className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>

          {mutation.isError && (
            <p className="text-sm text-destructive">{t('errors.generic')}</p>
          )}

          <button
            type="submit"
            disabled={mutation.isPending}
            className="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
          >
            {mutation.isPending ? t('registration.submitting') : t('registration.submitButton')}
          </button>
        </form>
      </div>
    </div>
  );
}
