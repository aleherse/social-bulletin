import { apiResource, apiResources, jsonApiRequest } from '@/shared/api';

import type { Category, Movement, MovementDraftInput } from '../model/types.ts';

export function fetchCategories(): Promise<Category[]> {
  return apiResources<Category>('/api/categories');
}

export function fetchMovements(): Promise<Movement[]> {
  return apiResources<Movement>('/api/movements');
}

export function fetchMovement(id: string): Promise<Movement> {
  return apiResource<Movement>(`/api/movements/${id}`);
}

export function createMovement(input: MovementDraftInput): Promise<Movement> {
  return apiResource<Movement>('/api/movements', jsonApiRequest('POST', 'movements', input));
}

export function submitMovement(id: string): Promise<Movement> {
  return apiResource<Movement>(`/api/movements/${id}/submit`, { method: 'POST' });
}

export function updateMovement(id: string, input: MovementDraftInput): Promise<Movement> {
  return apiResource<Movement>(`/api/movements/${id}`, jsonApiRequest('PATCH', 'movements', input));
}
