import type { ReactNode } from 'react';

import {
  useCreateMovement,
  useMovement,
  useMovements,
  useUpdateMovement,
} from '@/entities/movement';
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
      {route.view === 'edit' && <EditMovement id={route.id} />}
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

  let body: ReactNode;

  if (movements.isPending) {
    body = <p className="text-sm text-muted-foreground">{t('movements.loading')}</p>;
  } else if (movements.data !== undefined && movements.data.length > 0) {
    body = (
      <ul className="flex flex-col gap-3">
        {movements.data.map((movement) => (
          <MovementRow key={movement.id} movement={movement} />
        ))}
      </ul>
    );
  } else {
    body = <p className="text-sm text-muted-foreground">{t('movements.empty')}</p>;
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
      {body}
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
  const serverError = formServerError(
    apiError,
    createMovement.isError,
    t('movements.form.requestFailed'),
  );

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

function EditMovement({ id }: { id: string }) {
  const { t } = useTranslation();
  const movement = useMovement(id);
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
          updateMovement.mutate({ id, input }, { onSuccess: goToMovements });
        }}
      />
    );
  }

  return (
    <>
      <header className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('movements.form.editTitle')}</h1>
        <a className="text-sm underline" href="#/movements">
          {t('movements.backToList')}
        </a>
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
