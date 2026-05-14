import { apiBaseUrl } from '../config/api';

type HealthResponse = {
  status?: unknown;
};

export async function checkApiHealth(): Promise<boolean> {
  try {
    const response = await fetch(`${apiBaseUrl}/health`, {
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      return false;
    }

    const body = (await response.json()) as HealthResponse;

    return body.status === 'ok';
  } catch {
    return false;
  }
}
