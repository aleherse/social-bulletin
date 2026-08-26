export { Link, NavLink } from './link.tsx';
export { localePath } from './locale-path.ts';
export { useLocale } from './use-locale.ts';
export { useNavigate } from './use-navigate.ts';
// ADR-0018: slices reach react-router only through this public API.
export { Outlet, useParams, useSearchParams } from 'react-router';
export type { RouteObject } from 'react-router';
