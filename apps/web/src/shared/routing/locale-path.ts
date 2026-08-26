/** ADR-0018: every application URL carries its locale as the first segment. */
export function localePath(locale: string, to: string): string {
  const path = to.startsWith('/') ? to : `/${to}`;

  return path === '/' ? `/${locale}` : `/${locale}${path}`;
}
