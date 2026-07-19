import { MovementDescription, useMovement } from '@/entities/movement';
import { SubmitMovementButton } from '@/features/propose-movement';
import { useTranslation } from '@/shared/i18n';
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui';

import { MovementStatusBadge } from './movement-status-badge.tsx';

export function MovementDetail({ id }: { id: string }) {
  const { t } = useTranslation();
  const movement = useMovement(id);

  return (
    <>
      <header className="flex items-center justify-between">
        <a className="text-sm underline" href="#/movements">
          {t('movements.backToList')}
        </a>
        {movement.data?.status === 'draft' && (
          <a className="text-sm underline" href={`#/movements/${id}/edit`}>
            {t('movements.edit')}
          </a>
        )}
      </header>
      {movement.isPending ? (
        <p className="text-sm text-muted-foreground">{t('movements.loading')}</p>
      ) : movement.isError ? (
        <p role="alert" className="text-sm text-destructive">
          {t('movements.notFound')}
        </p>
      ) : (
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
      )}
    </>
  );
}
