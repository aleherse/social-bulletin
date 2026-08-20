import { useCreateMovement } from '@/entities/movement';
import { MovementForm } from '@/features/propose-movement';
import { ApiError } from '@/shared/api';
import { useTranslation } from '@/shared/i18n';
import { Link, useNavigate } from '@/shared/routing';

export function NewMovement() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const createMovement = useCreateMovement();

  const apiError = createMovement.error instanceof ApiError ? createMovement.error : null;
  const serverError = formServerError(
    apiError,
    createMovement.isError,
    t('movements.form.requestFailed'),
  );

  return (
    <>
      <header className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('movements.form.createTitle')}</h1>
        <Link className="text-sm underline" to="/movements">
          {t('movements.backToList')}
        </Link>
      </header>
      <MovementForm
        pending={createMovement.isPending}
        serverError={serverError}
        fieldErrors={apiError?.fieldErrors ?? {}}
        onSubmit={(input) => {
          createMovement.mutate(input, {
            onSuccess: () => {
              navigate('/movements');
            },
          });
        }}
      />
    </>
  );
}

/** The form owns field-level errors, so a server message is shown only when there are none. */
function formServerError(
  apiError: ApiError | null,
  isError: boolean,
  fallback: string,
): string | null {
  if (!isError) {
    return null;
  }

  if (apiError !== null && Object.keys(apiError.fieldErrors).length > 0) {
    return null;
  }

  return apiError?.message ?? fallback;
}
