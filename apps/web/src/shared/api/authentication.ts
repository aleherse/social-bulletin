import { apiBaseUrl } from '@/shared/config/environment';

export type CurrentUser = {
  email: string;
};

async function readJsonResponse(response: Response): Promise<CurrentUser> {
  if (!response.ok) {
    throw new Error(`API request failed with status ${response.status}`);
  }

  return (await response.json()) as CurrentUser;
}

export async function registerOrAuthenticate(email: string): Promise<CurrentUser> {
  const response = await fetch(`${apiBaseUrl}/auth/register`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email }),
  });

  return readJsonResponse(response);
}

export async function fetchCurrentUser(): Promise<CurrentUser | null> {
  const response = await fetch(`${apiBaseUrl}/auth/me`, {
    credentials: 'include',
  });

  if (response.status === 401) {
    return null;
  }

  return readJsonResponse(response);
}
