import type { ComponentProps } from 'react';
import { Link as RouterLink, NavLink as RouterNavLink } from 'react-router';

import { localePath } from './locale-path.ts';
import { useLocale } from './use-locale.ts';

type LinkProps = Omit<ComponentProps<typeof RouterLink>, 'to'> & { to: string };
type NavLinkProps = Omit<ComponentProps<typeof RouterNavLink>, 'to'> & { to: string };

/** ADR-0018: callers pass locale-free paths; the locale segment is added here. */
export function Link({ to, ...props }: LinkProps) {
  const locale = useLocale();

  return <RouterLink to={localePath(locale, to)} {...props} />;
}

export function NavLink({ to, ...props }: NavLinkProps) {
  const locale = useLocale();

  return <RouterNavLink to={localePath(locale, to)} {...props} />;
}
