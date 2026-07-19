import { useCreateMovement, useMovements } from '@/entities/movement';
import type { Movement } from '@/entities/movement';
import { MovementForm } from '@/features/propose-movement';
import { ApiError } from '@/shared/api';
import { useTranslation } from '@/shared/i18n';
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui';

import { goToMovements, useMovementsRoute } from '../model/route.ts';

import { MovementDetail } from './movement-detail.tsx';
import { MovementStatusBadge } from './movement-status-badge.tsx';

export function MovementsPage() {
  const route = useMovementsRoute();

  return (
    <main className="mx-auto flex min-h-svh w-full max-w-2xl flex-col gap-6 p-4">
      {route.view === 'list' && <MovementList />}
      {route.view === 'new' && <NewMovement />}
      {route.view === 'detail' && <MovementDetail id={route.id} />}
    </main>
  );
}

function MovementList() {
  const { t } = useTranslation();
  const movements = useMovements();

  if (movements.error instanceof ApiError && movements.error.status === 401) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{t('movements.title')}</CardTitle>
        </CardHeader>
        <CardContent className="flex flex-col gap-2">
          <p className="text-sm text-muted-foreground">{t('movements.signInPrompt')}</p>
          <a className="text-sm underline" href="#/">
            {t('movements.goHome')}
          </a>
        </CardContent>
      </Card>
    );
  }

  return (
    <>
      <header className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('movements.title')}</h1>
        <a
          className="rounded-lg bg-primary px-2.5 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/80"
          href="#/movements/new"
        >
          {t('movements.new')}
        </a>
      </header>
      {movements.isPending ? (
        <p className="text-sm text-muted-foreground">{t('movements.loading')}</p>
      ) : movements.data !== undefined && movements.data.length > 0 ? (
        <ul className="flex flex-col gap-3">
          {movements.data.map((movement) => (
            <MovementRow key={movement.id} movement={movement} />
          ))}
        </ul>
      ) : (
        <p className="text-sm text-muted-foreground">{t('movements.empty')}</p>
      )}
    </>
  );
}

function MovementRow({ movement }: { movement: Movement }) {
  const { t } = useTranslation();

  return (
    <li>
      <Card>
        <CardContent className="flex items-center justify-between gap-3">
          <div className="flex min-w-0 flex-col gap-1">
            <a
              className="truncate font-medium underline-offset-4 hover:underline"
              href={`#/movements/${movement.id}`}
            >
              {movement.title}
            </a>
            <p className="text-xs text-muted-foreground">
              {t(`movements.area.${movement.area}`)}
              {movement.location !== null && ` · ${movement.location}`}
              {' · '}
              {t(`movements.category.${movement.category}`, { defaultValue: movement.category })}
            </p>
          </div>
          <MovementStatusBadge status={movement.status} />
        </CardContent>
      </Card>
    </li>
  );
}

function NewMovement() {
  const { t } = useTranslation();
  const createMovement = useCreateMovement();

  const apiError = createMovement.error instanceof ApiError ? createMovement.error : null;
  const serverError = createMovement.isError
    ? apiError === null || Object.keys(apiError.fieldErrors).length === 0
      ? (apiError?.message ?? t('movements.form.requestFailed'))
      : null
    : null;

  return (
    <>
      <header className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('movements.form.createTitle')}</h1>
        <a className="text-sm underline" href="#/movements">
          {t('movements.backToList')}
        </a>
      </header>
      <MovementForm
        pending={createMovement.isPending}
        serverError={serverError}
        fieldErrors={apiError?.fieldErrors ?? {}}
        onSubmit={(input) => {
          createMovement.mutate(input, { onSuccess: goToMovements });
        }}
      />
    </>
  );
}
