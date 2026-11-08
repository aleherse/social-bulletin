import { useMovementChanges } from '@/entities/movement';
import { Outlet } from '@/shared/routing';

export function MovementsLayout() {
  useMovementChanges();

  return (
    <main className="mx-auto flex min-h-svh w-full max-w-2xl flex-col gap-6 p-4">
      <Outlet />
    </main>
  );
}
