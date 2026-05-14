import { useEffect, useState } from 'react';
import { checkApiHealth } from '../shared/api/health';

type HealthState = 'checking' | 'ok' | 'error';

export function App() {
  const [healthState, setHealthState] = useState<HealthState>('checking');

  useEffect(() => {
    let isMounted = true;

    checkApiHealth().then((isHealthy) => {
      if (isMounted) {
        setHealthState(isHealthy ? 'ok' : 'error');
      }
    });

    return () => {
      isMounted = false;
    };
  }, []);

  if (healthState !== 'error') {
    return null;
  }

  return (
    <main role="alert">
      <h1>Service unavailable</h1>
      <p>Social Bulletin cannot reach the API. Try again later.</p>
    </main>
  );
}
