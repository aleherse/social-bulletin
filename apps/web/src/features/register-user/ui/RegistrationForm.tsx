import { useState } from 'react';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import FormHelperText from '@mui/material/FormHelperText';
import Link from '@mui/material/Link';
import TextField from '@mui/material/TextField';
import { Link as RouterLink } from 'react-router-dom';
import { useTranslation } from '../../../shared/i18n';

interface FieldErrors {
  email?: string;
  password?: string;
  termsAccepted?: string;
}

interface FormValues {
  email: string;
  password: string;
  termsAccepted: boolean;
}

interface Props {
  onSubmit: (values: FormValues) => void | Promise<void>;
  fieldErrors?: FieldErrors;
  isSubmitting?: boolean;
}

export function RegistrationForm({ onSubmit, fieldErrors = {}, isSubmitting = false }: Props) {
  const { t } = useTranslation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [termsAccepted, setTermsAccepted] = useState(false);

  const passwordsMatch = password !== '' && password === passwordConfirmation;
  const canSubmit = passwordsMatch && termsAccepted && !isSubmitting;

  const passwordMismatch =
    passwordConfirmation !== '' && password !== passwordConfirmation;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (canSubmit) {
      void onSubmit({ email, password, termsAccepted });
    }
  };

  return (
    <Box component="form" onSubmit={handleSubmit} noValidate sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
      <TextField
        id="email"
        label={t('registration.email_label')}
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        error={Boolean(fieldErrors.email)}
        helperText={fieldErrors.email}
        fullWidth
      />

      <TextField
        id="password"
        label={t('registration.password_label')}
        type="password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        error={Boolean(fieldErrors.password)}
        helperText={fieldErrors.password}
        fullWidth
      />

      <TextField
        id="passwordConfirmation"
        label={t('registration.password_confirm_label')}
        type="password"
        value={passwordConfirmation}
        onChange={(e) => setPasswordConfirmation(e.target.value)}
        error={passwordMismatch}
        helperText={passwordMismatch ? t('registration.password_mismatch') : undefined}
        fullWidth
      />

      <Box>
        <FormControlLabel
          control={
              <Checkbox
              id="termsAccepted"
              checked={termsAccepted}
              onChange={(e) => setTermsAccepted(e.target.checked)}
            />
          }
          label={
            <span>
              {t('registration.terms_label')}{' '}
              <Link component={RouterLink} to="/terms">
                {t('registration.terms_link')}
              </Link>
            </span>
          }
        />
        {fieldErrors.termsAccepted && (
          <FormHelperText error>{fieldErrors.termsAccepted}</FormHelperText>
        )}
      </Box>

      <Button
        type="submit"
        variant="contained"
        disabled={!canSubmit}
        fullWidth
      >
        {t('registration.submit_button')}
      </Button>
    </Box>
  );
}
