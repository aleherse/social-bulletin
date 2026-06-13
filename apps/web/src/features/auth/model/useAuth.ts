import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { authApi } from '@/shared/api/auth';

export function useCurrentUser() {
  return useQuery({
    queryKey: ['auth', 'me'],
    queryFn: authApi.me,
    retry: false,
  });
}

export function useRegisterOrLogin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (email: string) => authApi.registerOrLogin(email),
    onSuccess: (user) => {
      queryClient.setQueryData(['auth', 'me'], user);
    },
  });
}

export function useLogout() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: authApi.logout,
    onSuccess: () => {
      queryClient.setQueryData(['auth', 'me'], null);
      void queryClient.invalidateQueries({ queryKey: ['auth', 'me'] });
    },
  });
}
