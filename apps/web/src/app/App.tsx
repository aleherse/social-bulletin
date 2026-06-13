import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import { RegistrationForm } from '@/features/auth/ui/RegistrationForm';
import { HelloView } from '@/features/auth/ui/HelloView';
import { useCurrentUser } from '@/features/auth/model/useAuth';
import { I18nProvider } from '@/shared/i18n';

const queryClient = new QueryClient();

function HomePage() {
  const { data: user, isLoading, isError } = useCurrentUser();

  if (isLoading) {
    return <main className="p-8">Loading…</main>;
  }

  if (user && !isError) {
    return (
      <main>
        <HelloView email={user.email} />
      </main>
    );
  }

  return (
    <main>
      <RegistrationForm />
    </main>
  );
}

export function AppProviders({ children }: { children: React.ReactNode }) {
  return (
    <I18nProvider>
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    </I18nProvider>
  );
}

export function App() {
  return (
    <AppProviders>
      <HomePage />
    </AppProviders>
  );
}
