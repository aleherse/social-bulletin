import type { Movement } from '@/entities/movement';
import { useTranslation } from '@/shared/i18n';
import { Link } from '@/shared/routing';
import { Card, CardContent } from '@/shared/ui';

import { MovementStatusBadge } from './movement-status-badge.tsx';

export function MovementRow({ movement }: { movement: Movement }) {
  const { t } = useTranslation();

  return (
    <li>
      <Card>
        <CardContent className="flex items-center justify-between gap-3">
          <div className="flex min-w-0 flex-col gap-1">
            <Link
              className="truncate font-medium underline-offset-4 hover:underline"
              to={`/movements/${movement.id}`}
            >
              {movement.title}
            </Link>
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
