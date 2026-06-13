import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { type FormEvent, useId, useState } from 'react';
import {
  Button,
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
  Input,
} from '@/shared/ui';
import { useTranslation } from '@/shared/i18n';
import { fetchCurrentUser, logout, registerOrLogin } from '../api/session';

const sessionQueryKey = ['current-user'];

export function HomePage() {
  const { t } = useTranslation();
  const emailId = useId();
  const queryClient = useQueryClient();
  const [email, setEmail] = useState('');

  const sessionQuery = useQuery({
    queryKey: sessionQueryKey,
    queryFn: fetchCurrentUser,
  });

  const registerMutation = useMutation({
    mutationFn: registerOrLogin,
    onSuccess: (session) => {
      queryClient.setQueryData(sessionQueryKey, session);
    },
  });

  const logoutMutation = useMutation({
    mutationFn: logout,
    onSuccess: () => {
      setEmail('');
      queryClient.setQueryData(sessionQueryKey, null);
    },
  });

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    registerMutation.mutate({ email });
  };

  if (sessionQuery.isLoading) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-muted p-6">
        <Card className="w-full max-w-md">
          <CardContent className="pt-6 text-sm text-muted-foreground">
            {t('auth.loading')}
          </CardContent>
        </Card>
      </main>
    );
  }

  const user = sessionQuery.data?.user;

  return (
    <main className="flex min-h-screen items-center justify-center bg-muted p-6">
      <Card className="w-full max-w-md">
        {user ? (
          <>
            <CardHeader>
              <CardTitle>{t('auth.hello.title', { email: user.email })}</CardTitle>
              <CardDescription>{t('auth.hello.description')}</CardDescription>
            </CardHeader>
            <CardContent>
              <Button
                type="button"
                variant="outline"
                onClick={() => logoutMutation.mutate()}
                disabled={logoutMutation.isPending}
              >
                {t('auth.logout')}
              </Button>
            </CardContent>
          </>
        ) : (
          <>
            <CardHeader>
              <CardTitle>{t('auth.form.title')}</CardTitle>
              <CardDescription>{t('auth.form.description')}</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                <FieldGroup>
                  <Field data-invalid={registerMutation.isError || undefined}>
                    <FieldLabel htmlFor={emailId}>{t('auth.form.emailLabel')}</FieldLabel>
                    <Input
                      id={emailId}
                      type="email"
                      autoComplete="email"
                      value={email}
                      onChange={(event) => setEmail(event.target.value)}
                      aria-invalid={registerMutation.isError}
                      required
                    />
                    <FieldDescription>{t('auth.form.emailHelp')}</FieldDescription>
                  </Field>
                </FieldGroup>
                {registerMutation.isError ? (
                  <p className="text-sm text-destructive" role="alert">
                    {t('auth.form.error')}
                  </p>
                ) : null}
                <Button type="submit" disabled={registerMutation.isPending}>
                  {registerMutation.isPending ? t('auth.form.submitting') : t('auth.form.submit')}
                </Button>
              </form>
            </CardContent>
          </>
        )}
      </Card>
    </main>
  );
}
