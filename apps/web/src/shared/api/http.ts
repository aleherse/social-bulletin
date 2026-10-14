const API_URL: string =
  (import.meta.env.VITE_API_URL as string | undefined) ?? 'https://dev.api.social.aleherse.com';

interface JsonApiError {
  title?: string;
  detail?: string;
  source?: { pointer?: string };
}

interface ApiErrorBody {
  errors?: JsonApiError[];
}

/** API rejected the request; `message` and `fieldErrors` come from a JSON:API error document, already translated. */
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

/** Reads a JSON:API error document: the first title is the message, pointers to request fields are field errors. */
export async function readApiError(response: Response, fallback: string): Promise<ApiError> {
  const body = (await response.json().catch(() => null)) as ApiErrorBody | null;
  const errors = body?.errors ?? [];
  const fieldErrors: Record<string, string> = {};

  for (const error of errors) {
    const field = error.source?.pointer?.replace(/^\//, '');

    if (field !== undefined && field !== '' && error.detail !== undefined) {
      fieldErrors[field] = error.detail;
    }
  }

  return new ApiError(errors[0]?.title ?? fallback, response.status, fieldErrors);
}

export async function apiJson<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await apiRequest(path, init);

  if (!response.ok) {
    throw await readApiError(response, `Unexpected ${path} response: ${String(response.status)}`);
  }

  return (await response.json()) as T;
}
