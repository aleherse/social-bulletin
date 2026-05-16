import { useEffect, useState } from 'react';
import { checkApiHealth } from '../shared/api/health';
import { useTranslation } from '../shared/i18n';

type HealthState = 'checking' | 'ok' | 'error';

export function App() {
  const { t } = useTranslation();
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
      <h1>{t('error.service_unavailable')}</h1>
      <p>{t('error.service_unavailable_detail')}</p>
    </main>
  );
}
