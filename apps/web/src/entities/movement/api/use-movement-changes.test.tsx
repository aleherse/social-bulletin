import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderHook } from '@testing-library/react';
import type { ReactNode } from 'react';
import { describe, expect, it, vi } from 'vitest';

import { watchRealtime } from '@/shared/realtime';
import type { RealtimeWatch } from '@/shared/realtime';

import { useMovementChanges } from './hooks.ts';

vi.mock('@/shared/realtime', () => ({ watchRealtime: vi.fn() }));

function setup() {
  const stop = vi.fn();
  let watch: RealtimeWatch | undefined;
  vi.mocked(watchRealtime).mockImplementation((options) => {
    watch = options;

    return stop;
  });
  const queryClient = new QueryClient();
  const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
  const wrapper = ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );

  return { stop, invalidate, wrapper, watch: () => watch as RealtimeWatch };
}

describe('useMovementChanges', () => {
  it('refetches the movements when one changes', () => {
    const { invalidate, wrapper, watch } = setup();
    renderHook(
      () => {
        useMovementChanges();
      },
      { wrapper },
    );

    watch().onNotification('/users/u-1/movements', { change: 'created', movementId: 'm-1' });

    expect(invalidate).toHaveBeenCalledWith({ queryKey: ['movements'] });
  });

  it('refetches the movements after a reconnection', () => {
    const { invalidate, wrapper, watch } = setup();
    renderHook(
      () => {
        useMovementChanges();
      },
      { wrapper },
    );

    watch().onResync();

    expect(invalidate).toHaveBeenCalledWith({ queryKey: ['movements'] });
  });

  it('stops listening when the component goes away', () => {
    const { stop, wrapper } = setup();
    const { unmount } = renderHook(
      () => {
        useMovementChanges();
      },
      { wrapper },
    );

    unmount();

    expect(stop).toHaveBeenCalledOnce();
  });
});
