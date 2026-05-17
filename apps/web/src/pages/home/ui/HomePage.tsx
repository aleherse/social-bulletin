import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import AppBar from '@mui/material/AppBar';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Toolbar from '@mui/material/Toolbar';
import Typography from '@mui/material/Typography';
import { useTranslation } from '../../../shared/i18n';
import { fetchMe as defaultFetchMe } from '../../../shared/api/me';
import { RegisterModal } from '../../../features/register-user/ui/RegisterModal';

type FetchMeFn = () => Promise<{ userId: string } | null>;

interface Props {
  fetchMe?: FetchMeFn;
}

export function HomePage({ fetchMe = defaultFetchMe }: Props) {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [isAuthenticated, setIsAuthenticated] = useState<boolean | null>(null);
  const [modalOpen, setModalOpen] = useState(false);

  useEffect(() => {
    let active = true;

    fetchMe().then((result) => {
      if (active) {
        setIsAuthenticated(result !== null);
      }
    });

    return () => {
      active = false;
    };
  }, [fetchMe]);

  const handleRegistrationSuccess = (_userId: string) => {
    setIsAuthenticated(true);
    setModalOpen(false);
    void navigate('/');
  };

  return (
    <>
      <AppBar position="static">
        <Toolbar>
          <Typography variant="h6" component="div" sx={{ flexGrow: 1 }}>
            {t('app.title')}
          </Typography>
          {isAuthenticated === false && (
            <Button color="inherit" onClick={() => setModalOpen(true)}>
              {t('registration.button')}
            </Button>
          )}
        </Toolbar>
      </AppBar>
      <Box component="main" sx={{ p: 3 }} />
      <RegisterModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        onSuccess={handleRegistrationSuccess}
      />
    </>
  );
}
