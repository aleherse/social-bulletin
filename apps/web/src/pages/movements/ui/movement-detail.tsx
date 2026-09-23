import type { ReactNode } from 'react';

import { MovementDescription, useMovement } from '@/entities/movement';
import { SubmitMovementButton } from '@/features/propose-movement';
import { useTranslation } from '@/shared/i18n';
import { Link, useParams } from '@/shared/routing';
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui';

import { MovementStatusBadge } from './movement-status-badge.tsx';

export function MovementDetail() {
  const { t } = useTranslation();
  // The `:id` route segment guarantees this parameter.
  const { id } = useParams();
  const movement = useMovement(id!);

  let body: ReactNode;

  if (movement.isPending) {
    body = <p className="text-sm text-muted-foreground">{t('movements.loading')}</p>;
  } else if (movement.isError) {
    body = (
      <p role="alert" className="text-sm text-destructive">
        {t('movements.notFound')}
      </p>
    );
  } else {
    body = (
      <Card>
        <CardHeader className="flex items-start justify-between gap-3">
          <CardTitle>{movement.data.title}</CardTitle>
          <MovementStatusBadge status={movement.data.status} />
        </CardHeader>
        <CardContent className="flex flex-col gap-4">
          <p className="text-xs text-muted-foreground">
            {t(`movements.area.${movement.data.area}`)}
            {movement.data.location !== null && ` · ${movement.data.location}`}
            {' · '}
            {t(`movements.category.${movement.data.category}`, {
              defaultValue: movement.data.category,
            })}
          </p>
          {movement.data.description !== '' && (
            <MovementDescription markdown={movement.data.description} />
          )}
          <SubmitMovementButton movement={movement.data} />
        </CardContent>
      </Card>
    );
  }

  return (
    <>
      <header className="flex items-center justify-between">
        <Link className="text-sm underline" to="/movements">
          {t('movements.backToList')}
        </Link>
        {movement.data?.status === 'draft' && (
          <Link className="text-sm underline" to={`/movements/${id!}/edit`}>
            {t('movements.edit')}
          </Link>
        )}
      </header>
      {body}
    </>
  );
}
