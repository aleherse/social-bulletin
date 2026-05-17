import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import { useTranslation } from '../../../shared/i18n';

export function TermsPage() {
  const { t } = useTranslation();

  return (
    <Box component="main" sx={{ maxWidth: 800, mx: 'auto', p: 4 }}>
      <Typography variant="h4" component="h1" gutterBottom>
        {t('terms.page_title')}
      </Typography>
      <Typography variant="body1" paragraph>
        {t('terms.intro')}
      </Typography>
      <Typography variant="h6" gutterBottom>
        1. Use of the Service
      </Typography>
      <Typography variant="body1" paragraph>
        By registering for Social Bulletin, you agree to use the service in accordance with
        applicable laws and regulations. You are responsible for maintaining the confidentiality
        of your account credentials.
      </Typography>
      <Typography variant="h6" gutterBottom>
        2. Privacy
      </Typography>
      <Typography variant="body1" paragraph>
        Your personal information is used solely to provide and improve the service.
        We do not sell or share your data with third parties without your consent.
      </Typography>
      <Typography variant="h6" gutterBottom>
        3. Acceptable Use
      </Typography>
      <Typography variant="body1" paragraph>
        You agree not to use Social Bulletin for any unlawful purpose or in any way that
        could harm others or the integrity of the platform.
      </Typography>
    </Box>
  );
}
