import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { HelloView, RegistrationForm } from '@/pages/home';
import { fetchCurrentUser, registerOrAuthenticate } from '@/shared/api';

export function App() {
  const queryClient = useQueryClient();
  const currentUser = useQuery({
    queryKey: ['current-user'],
    queryFn: fetchCurrentUser,
    retry: false,
  });
  const registration = useMutation({
    mutationFn: registerOrAuthenticate,
    onSuccess: (user) => queryClient.setQueryData(['current-user'], user),
  });

  if (currentUser.isLoading) {
    return <main aria-busy="true">Loading</main>;
  }

  if (!currentUser.data) {
    return (
      <RegistrationForm
        onSubmit={async (email) => {
          await registration.mutateAsync(email);
        }}
      />
    );
  }

  return <HelloView email={currentUser.data.email} />;
}
