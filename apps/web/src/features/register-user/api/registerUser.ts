import { apiBaseUrl } from '../../../shared/config/api';

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

export class ApiConflictError extends ApiError {
  constructor() {
    super('Email address is already registered.', 409);
    this.name = 'ApiConflictError';
  }
}

export class ApiValidationError extends ApiError {
  constructor(public readonly errors: Array<{ field: string; message: string }>) {
    super('Validation failed.', 422);
    this.name = 'ApiValidationError';
  }
}

interface RegisterPayload {
  email: string;
  password: string;
  termsAccepted: boolean;
}

export async function registerUser(payload: RegisterPayload): Promise<{ userId: string }> {
  const response = await fetch(`${apiBaseUrl}/api/register`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  });

  if (response.status === 201) {
    return response.json() as Promise<{ userId: string }>;
  }

  if (response.status === 409) {
    throw new ApiConflictError();
  }

  if (response.status === 422) {
    const body = (await response.json()) as { errors: Array<{ field: string; message: string }> };
    throw new ApiValidationError(body.errors ?? []);
  }

  throw new ApiError(`Unexpected response: ${response.status}`, response.status);
}
