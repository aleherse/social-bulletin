import { apiBaseUrl } from '../config/api';

export async function fetchMe(): Promise<{ userId: string } | null> {
  try {
    const response = await fetch(`${apiBaseUrl}/api/me`, {
      credentials: 'include',
      headers: { Accept: 'application/json' },
    });

    if (response.status === 200) {
      return (await response.json()) as { userId: string };
    }

    return null;
  } catch {
    return null;
  }
}
