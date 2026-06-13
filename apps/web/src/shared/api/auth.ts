const API_URL = import.meta.env.VITE_API_URL ?? 'https://api.dev.social.aleherse.com';

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    ...init,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...(init?.headers ?? {}),
    },
  });

  if (!response.ok) {
    throw new Error(`Request failed: ${response.status}`);
  }

  if (response.status === 204) {
    return undefined as T;
  }

  return (await response.json()) as T;
}

export type CurrentUser = { email: string };

export const authApi = {
  registerOrLogin: (email: string) =>
    request<CurrentUser>('/api/auth/register', {
      method: 'POST',
      body: JSON.stringify({ email }),
    }),
  me: () => request<CurrentUser>('/api/auth/me'),
  logout: () => request<void>('/api/auth/logout', { method: 'POST' }),
};
