import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import type { MovementDraftInput } from '../model/types.ts';

import {
  createMovement,
  fetchCategories,
  fetchMovement,
  fetchMovements,
  submitMovement,
  updateMovement,
} from './client.ts';

const movementsKey = ['movements'] as const;
const categoriesKey = ['categories'] as const;

export function useCategories() {
  return useQuery({ queryKey: categoriesKey, queryFn: fetchCategories, retry: false });
}

export function useMovements() {
  return useQuery({ queryKey: movementsKey, queryFn: fetchMovements, retry: false });
}

export function useMovement(id: string) {
  return useQuery({
    queryKey: [...movementsKey, id],
    queryFn: () => fetchMovement(id),
    retry: false,
  });
}

export function useCreateMovement() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: createMovement,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: movementsKey }),
  });
}

export function useSubmitMovement() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: submitMovement,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: movementsKey }),
  });
}

export function useUpdateMovement() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, input }: { id: string; input: MovementDraftInput }) =>
      updateMovement(id, input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: movementsKey }),
  });
}
