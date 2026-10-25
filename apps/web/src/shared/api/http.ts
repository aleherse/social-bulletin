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

async function apiJson<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await apiRequest(path, init);

  if (!response.ok) {
    throw await readApiError(response, `Unexpected ${path} response: ${String(response.status)}`);
  }

  return (await response.json()) as T;
}

interface JsonApiResource<T> {
  id: string;
  attributes: Omit<T, 'id'>;
}

function flatten<T extends { id: string }>(resource: JsonApiResource<T>): T {
  return { id: resource.id, ...resource.attributes } as T;
}

/** Reads a successful JSON:API response holding one resource and returns it as its id plus attributes. */
export async function readResource<T extends { id: string }>(response: Response): Promise<T> {
  const body = (await response.json()) as { data: JsonApiResource<T> };

  return flatten(body.data);
}

/** Request options sending `attributes` as the JSON:API resource object of the given `type`. */
export function jsonApiRequest(
  method: 'POST' | 'PATCH',
  type: string,
  attributes: object,
): RequestInit {
  return {
    method,
    headers: { 'Content-Type': 'application/vnd.api+json' },
    body: JSON.stringify({ data: { type, attributes } }),
  };
}

/** Fetches a JSON:API document holding one resource and returns it as its id plus attributes. */
export async function apiResource<T extends { id: string }>(
  path: string,
  init?: RequestInit,
): Promise<T> {
  const body = await apiJson<{ data: JsonApiResource<T> }>(path, init);

  return flatten(body.data);
}

/** Fetches a JSON:API document holding a list of resources and returns each as its id plus attributes. */
export async function apiResources<T extends { id: string }>(
  path: string,
  init?: RequestInit,
): Promise<T[]> {
  const body = await apiJson<{ data: JsonApiResource<T>[] }>(path, init);

  return body.data.map(flatten);
}
