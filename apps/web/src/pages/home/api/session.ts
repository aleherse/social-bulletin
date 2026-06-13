import { apiRequest } from '@/shared/api/client';
import type { RegistrationRequest, SessionResponse } from '../model/session';

export async function fetchCurrentUser(): Promise<SessionResponse | null> {
  try {
    return await apiRequest<SessionResponse>('/api/me');
  } catch {
    return null;
  }
}

export function registerOrLogin(payload: RegistrationRequest): Promise<SessionResponse> {
  return apiRequest<SessionResponse>('/api/auth/register-or-login', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export function logout(): Promise<void> {
  return apiRequest<void>('/api/auth/logout', {
    method: 'POST',
  });
}
