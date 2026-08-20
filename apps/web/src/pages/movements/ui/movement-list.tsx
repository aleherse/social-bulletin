import type { ReactNode } from 'react';

import { useMovements } from '@/entities/movement';
import { useTranslation } from '@/shared/i18n';
import { Link } from '@/shared/routing';

import { MovementRow } from './movement-row.tsx';

export function MovementList() {
  const { t } = useTranslation();
  const movements = useMovements();

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
        <Link
          className="rounded-lg bg-primary px-2.5 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/80"
          to="/movements/new"
        >
          {t('movements.new')}
        </Link>
      </header>
      {body}
    </>
  );
}
