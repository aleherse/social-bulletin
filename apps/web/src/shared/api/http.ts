const API_URL: string =
  (import.meta.env.VITE_API_URL as string | undefined) ?? 'https://dev.api.social.aleherse.com';

interface ApiErrorBody {
  message?: string;
  errors?: Record<string, string>;
}

/** API rejected the request; `message` and `fieldErrors` are already translated by the backend. */
export class ApiError extends Error {
  readonly status: number;
  readonly fieldErrors: Record<string, string>;

  constructor(message: string, status: number, fieldErrors: Record<string, string> = {}) {
    super(message);
    this.status = status;
    this.fieldErrors = fieldErrors;
  }
}

export function apiRequest(path: string, init?: RequestInit): Promise<Response> {
  // The JWT lives in an httpOnly cookie, so every call must send credentials.
  return fetch(`${API_URL}${path}`, { credentials: 'include', ...init });
}

export async function apiJson<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await apiRequest(path, init);

  if (!response.ok) {
    const body = (await response.json().catch(() => null)) as ApiErrorBody | null;

    throw new ApiError(
      body?.message ?? `Unexpected ${path} response: ${String(response.status)}`,
      response.status,
      body?.errors ?? {},
    );
  }

  return (await response.json()) as T;
}
