import { apiJson } from '@/shared/api';

import type { Category, Movement, MovementDraftInput } from '../model/types.ts';

export function fetchCategories(): Promise<Category[]> {
  return apiJson<{ categories: Category[] }>('/api/categories').then((body) => body.categories);
}

export function fetchMovements(): Promise<Movement[]> {
  return apiJson<{ movements: Movement[] }>('/api/movements').then((body) => body.movements);
}

export function fetchMovement(id: string): Promise<Movement> {
  return apiJson<Movement>(`/api/movements/${id}`);
}

export function createMovement(input: MovementDraftInput): Promise<Movement> {
  return apiJson<Movement>('/api/movements', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(input),
  });
}
