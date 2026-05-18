import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { createSession, deleteSession, fetchCurrentUser } from '@/shared/api';
import type { SessionUser } from '@/shared/api';

const currentUserKey = ['session', 'current-user'] as const;

export function useCurrentUser() {
  return useQuery({
    queryKey: currentUserKey,
    queryFn: fetchCurrentUser,
    retry: false,
  });
}

export function useCreateSession() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: createSession,
    onSuccess: (user: SessionUser) => {
      queryClient.setQueryData(currentUserKey, user);
    },
  });
}

export function useLogout() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: deleteSession,
    onSuccess: () => {
      queryClient.setQueryData(currentUserKey, null);
    },
  });
}
