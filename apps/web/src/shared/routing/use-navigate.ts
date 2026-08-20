import { useCallback } from 'react';
import { useNavigate as useRouterNavigate } from 'react-router';
import type { NavigateOptions } from 'react-router';

import { localePath } from './locale-path.ts';
import { useLocale } from './use-locale.ts';

/** Navigates to a locale-free path; the active locale segment is added here. */
export function useNavigate() {
  const navigate = useRouterNavigate();
  const locale = useLocale();

  return useCallback(
    (to: string, options?: NavigateOptions) => {
      void navigate(localePath(locale, to), options);
    },
    [navigate, locale],
  );
}
