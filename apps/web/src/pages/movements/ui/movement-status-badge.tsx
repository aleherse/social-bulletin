import type { MovementStatus } from '@/entities/movement';
import { useTranslation } from '@/shared/i18n';
import { cn } from '@/shared/lib/utils';

const statusClasses: Record<MovementStatus, string> = {
  draft: 'bg-muted text-muted-foreground',
  proposed: 'bg-primary/10 text-primary',
  published: 'bg-secondary text-secondary-foreground',
};

export function MovementStatusBadge({ status }: { status: MovementStatus }) {
  const { t } = useTranslation();

  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
        statusClasses[status],
      )}
    >
      {t(`movements.status.${status}`)}
    </span>
  );
}
