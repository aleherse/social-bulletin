export interface SessionUser {
  id: string;
  email: string;
}

/** API rejected the session request; `message` is already translated by the backend. */
export class SessionError extends Error {}

import { apiRequest as request, jsonApiRequest, readApiError, readResource } from './http.ts';

export async function fetchCurrentUser(): Promise<SessionUser | null> {
  const response = await request('/api/me');

  if (response.status === 401) {
    return null;
  }

  if (!response.ok) {
    throw new Error(`Unexpected /api/me response: ${String(response.status)}`);
  }

  return readResource<SessionUser>(response);
}

export async function createSession(email: string): Promise<SessionUser> {
  const response = await request('/api/session', jsonApiRequest('POST', 'sessions', { email }));

  if (!response.ok) {
    const error = await readApiError(
      response,
      `Unexpected /api/session response: ${String(response.status)}`,
    );

    throw new SessionError(error.message);
  }

  return readResource<SessionUser>(response);
}

export async function deleteSession(): Promise<void> {
  const response = await request('/api/logout', { method: 'POST' });

  if (!response.ok) {
    throw new Error(`Unexpected /api/logout response: ${String(response.status)}`);
  }
}
