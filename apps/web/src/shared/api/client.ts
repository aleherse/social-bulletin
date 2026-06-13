const API_BASE = import.meta.env.VITE_API_URL ?? '';

export async function apiFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`${API_BASE}${path}`, {
    ...init,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...init?.headers,
    },
  });

  if (!res.ok) {
    const error = new Error(`API error ${res.status}`);
    (error as Error & { status: number }).status = res.status;
    throw error;
  }

  return res.json() as Promise<T>;
}
