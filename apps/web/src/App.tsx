import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiFetch } from '@/shared/api/client';
import { RegistrationPage } from '@/pages/home/ui/RegistrationPage';
import { HelloPage } from '@/pages/home/ui/HelloPage';

interface MeResponse {
  id: string;
  email: string;
}

function App() {
  const [authenticatedEmail, setAuthenticatedEmail] = useState<string | null>(null);

  const { isLoading } = useQuery<MeResponse, Error>({
    queryKey: ['me'],
    queryFn: () => apiFetch<MeResponse>('/api/me'),
    retry: false,
    onSuccess: (data: MeResponse) => {
      setAuthenticatedEmail(data.email);
    },
  });

  if (isLoading) {
    return null;
  }

  if (authenticatedEmail) {
    return (
      <HelloPage
        email={authenticatedEmail}
        onLogout={() => setAuthenticatedEmail(null)}
      />
    );
  }

  return <RegistrationPage onSuccess={(email) => setAuthenticatedEmail(email)} />;
}

export default App;
