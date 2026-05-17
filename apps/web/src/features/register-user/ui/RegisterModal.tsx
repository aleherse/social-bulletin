import { useState } from 'react';
import Alert from '@mui/material/Alert';
import Dialog from '@mui/material/Dialog';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import { useTranslation } from '../../../shared/i18n';
import { RegistrationForm } from './RegistrationForm';
import { ApiConflictError, ApiValidationError, registerUser as defaultRegisterUser } from '../api/registerUser';

type RegisterUserFn = typeof defaultRegisterUser;

interface Props {
  open: boolean;
  onClose: () => void;
  onSuccess?: (userId: string) => void;
  registerUser?: RegisterUserFn;
}

interface FieldErrors {
  email?: string;
  password?: string;
  termsAccepted?: string;
}

export function RegisterModal({
  open,
  onClose,
  onSuccess,
  registerUser = defaultRegisterUser,
}: Props) {
  const { t } = useTranslation();
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (values: {
    email: string;
    password: string;
    termsAccepted: boolean;
  }) => {
    setGeneralError(null);
    setFieldErrors({});
    setIsSubmitting(true);

    try {
      const result = await registerUser(values);
      onSuccess?.(result.userId);
      onClose();
    } catch (err) {
      if (err instanceof ApiConflictError) {
        setGeneralError(t('registration.error_duplicate_email'));
      } else if (err instanceof ApiValidationError) {
        const errors: FieldErrors = {};
        for (const { field, message } of err.errors) {
          if (field === 'email' || field === 'password' || field === 'termsAccepted') {
            errors[field] = message;
          }
        }
        setFieldErrors(errors);
      } else {
        setGeneralError(t('registration.error_generic'));
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Dialog open={open} onClose={onClose} fullWidth maxWidth="sm">
      <DialogTitle>{t('registration.modal_title')}</DialogTitle>
      <DialogContent>
        {generalError && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {generalError}
          </Alert>
        )}
        <RegistrationForm
          onSubmit={handleSubmit}
          fieldErrors={fieldErrors}
          isSubmitting={isSubmitting}
        />
      </DialogContent>
    </Dialog>
  );
}
