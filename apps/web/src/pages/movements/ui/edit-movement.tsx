import type { ReactNode } from 'react';

import { useMovement, useUpdateMovement } from '@/entities/movement';
import { MovementForm } from '@/features/propose-movement';
import { ApiError } from '@/shared/api';
import { useTranslation } from '@/shared/i18n';
import { Link, useNavigate, useParams } from '@/shared/routing';

export function EditMovement() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  // The `:id` route segment guarantees this parameter.
  const { id } = useParams();
  const movement = useMovement(id!);
  const updateMovement = useUpdateMovement();

  const apiError = updateMovement.error instanceof ApiError ? updateMovement.error : null;
  const serverError = formServerError(
    apiError,
    updateMovement.isError,
    t('movements.form.requestFailed'),
  );

  let body: ReactNode;

  if (movement.isPending) {
    body = <p className="text-sm text-muted-foreground">{t('movements.loading')}</p>;
  } else if (movement.isError || movement.data.status !== 'draft') {
    body = (
      <p role="alert" className="text-sm text-destructive">
        {t('movements.notFound')}
      </p>
    );
  } else {
    body = (
      <MovementForm
        initial={movement.data}
        pending={updateMovement.isPending}
        serverError={serverError}
        fieldErrors={apiError?.fieldErrors ?? {}}
        onSubmit={(input) => {
          updateMovement.mutate(
            { id: id!, input },
            {
              onSuccess: () => {
                navigate('/movements');
              },
            },
          );
        }}
      />
    );
  }

  return (
    <>
      <header className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('movements.form.editTitle')}</h1>
        <Link className="text-sm underline" to="/movements">
          {t('movements.backToList')}
        </Link>
      </header>
      {body}
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
