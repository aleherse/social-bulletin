import { useSubmitMovement } from '@/entities/movement';
import type { Movement } from '@/entities/movement';
import { ApiError } from '@/shared/api';
import { useTranslation } from '@/shared/i18n';
import { Button } from '@/shared/ui';

/** Submits a draft as a proposal; renders nothing once the movement left `draft`. */
export function SubmitMovementButton({ movement }: { movement: Movement }) {
  const { t } = useTranslation();
  const submit = useSubmitMovement();

  if (movement.status !== 'draft') {
    return null;
  }

  const error = submitErrorMessage(submit.error, submit.isError, t('movements.form.requestFailed'));

  return (
    <div className="flex flex-col items-start gap-2">
      <Button
        disabled={submit.isPending}
        onClick={() => {
          submit.mutate(movement.id);
        }}
      >
        {t('movements.submitAction')}
      </Button>
      {error !== null && (
        <p role="alert" className="text-sm text-destructive">
          {error}
        </p>
      )}
    </div>
  );
}

function submitErrorMessage(error: unknown, isError: boolean, fallback: string): string | null {
  if (error instanceof ApiError) {
    return error.fieldErrors.description ?? error.message;
  }

  return isError ? fallback : null;
}
