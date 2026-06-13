import { API_BASE_URL } from '@/shared/config/environment';

class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

async function parseJson<T>(response: Response): Promise<T> {
  if (response.status === 204) {
    return undefined as T;
  }

  return (await response.json()) as T;
}

export async function apiRequest<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...init.headers,
    },
  });

  if (!response.ok) {
    const body: { message?: string } = await parseJson<{ message?: string }>(response).catch(
      () => ({}),
    );
    throw new ApiError(
      body.message ?? `Request failed with status ${response.status}`,
      response.status,
    );
  }

  return parseJson<T>(response);
}
