import { useMutation } from '@tanstack/react-query';
import { apiFetch } from '@/shared/api/client';
import { useTranslation } from '@/shared/i18n';

interface HelloPageProps {
  email: string;
  onLogout: () => void;
}

export function HelloPage({ email, onLogout }: HelloPageProps) {
  const { t } = useTranslation();

  const mutation = useMutation({
    mutationFn: () => apiFetch('/api/logout', { method: 'POST' }),
    onSuccess: () => {
      onLogout();
    },
  });

  return (
    <div className="flex min-h-screen items-center justify-center p-4">
      <div className="w-full max-w-sm space-y-6 text-center">
        <h1 className="text-2xl font-semibold">{t('hello.greeting', { email })}</h1>

        <button
          onClick={() => mutation.mutate()}
          disabled={mutation.isPending}
          className="rounded-md border border-input px-4 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground disabled:opacity-50"
        >
          {t('hello.logoutButton')}
        </button>
      </div>
    </div>
  );
}
