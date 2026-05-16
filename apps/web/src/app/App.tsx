import { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
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
    <Box component="main" role="alert">
      <Typography variant="h4" component="h1">{t('error.service_unavailable')}</Typography>
      <Typography variant="body1">{t('error.service_unavailable_detail')}</Typography>
    </Box>
  );
}
