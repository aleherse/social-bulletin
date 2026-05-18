export interface SessionUser {
  email: string;
}

/** API rejected the session request; `message` is already translated by the backend. */
export class SessionError extends Error {}

const API_URL: string =
  (import.meta.env.VITE_API_URL as string | undefined) ?? 'https://dev.api.social.aleherse.com';

function request(path: string, init?: RequestInit): Promise<Response> {
  // The JWT lives in an httpOnly cookie, so every call must send credentials.
  return fetch(`${API_URL}${path}`, { credentials: 'include', ...init });
}

export async function fetchCurrentUser(): Promise<SessionUser | null> {
  const response = await request('/api/me');

  if (response.status === 401) {
    return null;
  }

  if (!response.ok) {
    throw new Error(`Unexpected /api/me response: ${String(response.status)}`);
  }

  return (await response.json()) as SessionUser;
}

export async function createSession(email: string): Promise<SessionUser> {
  const response = await request('/api/session', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email }),
  });

  if (!response.ok) {
    const body = (await response.json().catch(() => null)) as { message?: string } | null;

    throw new SessionError(
      body?.message ?? `Unexpected /api/session response: ${String(response.status)}`,
    );
  }

  return (await response.json()) as SessionUser;
}

export async function deleteSession(): Promise<void> {
  const response = await request('/api/logout', { method: 'POST' });

  if (!response.ok) {
    throw new Error(`Unexpected /api/logout response: ${String(response.status)}`);
  }
}
