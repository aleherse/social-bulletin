# Style guide — SocialBulletin

## Colors
| Token | Value | Semantic Use |
|---|---|---|
| `--background` | `0 0% 100%` | Application background |
| `--foreground` | `240 10% 3.9%` | Primary text |
| `--card` | `0 0% 100%` | Card surfaces |
| `--card-foreground` | `240 10% 3.9%` | Text on cards |
| `--popover` | `0 0% 100%` | Floating surfaces |
| `--popover-foreground` | `240 10% 3.9%` | Text on floating surfaces |
| `--primary` | `240 5.9% 10%` | Primary actions |
| `--primary-foreground` | `0 0% 98%` | Text on primary actions |
| `--secondary` | `240 4.8% 95.9%` | Secondary actions |
| `--secondary-foreground` | `240 5.9% 10%` | Text on secondary actions |
| `--muted` | `240 4.8% 95.9%` | Muted page areas |
| `--muted-foreground` | `240 3.8% 46.1%` | Secondary/helper text |
| `--accent` | `240 4.8% 95.9%` | Hover and subtle emphasis |
| `--accent-foreground` | `240 5.9% 10%` | Text on accent surfaces |
| `--destructive` | `0 84.2% 60.2%` | Error and destructive feedback |
| `--destructive-foreground` | `0 0% 98%` | Text on destructive surfaces |
| `--border` | `240 5.9% 90%` | Borders and dividers |
| `--input` | `240 5.9% 90%` | Form control borders |
| `--ring` | `240 5.9% 10%` | Focus rings |

## Typography
| Level | Family | Size | Weight | Line Height |
|---|---|---|---|---|
| Body | Tailwind default sans | `text-base` | `font-normal` | Tailwind default |
| Body small | Tailwind default sans | `text-sm` | `font-normal` | Tailwind default |
| Button | Tailwind default sans | `text-sm` | `font-medium` | Tailwind default |
| Card title | Tailwind default sans | inherited | `font-semibold` | `leading-none` |
| Field label | Tailwind default sans | `text-sm` | `font-medium` | `leading-none` |

## Spacing
Tailwind spacing tokens are used through semantic component composition. Current recurring values are `gap-1.5`, `gap-2`, `gap-4`, `p-6`, `pt-0`, `px-3`, `px-4`, `py-1`, and `py-2`. Form layouts use `FieldGroup` and `Field` with flex and `gap-*`, not `space-*` utilities.

## Interaction States
- Hover: button variants use semantic token opacity or accent background changes, e.g. `hover:bg-primary/90`, `hover:bg-accent`.
- Focus: controls use `focus-visible:ring-1 focus-visible:ring-ring` and remove default outline.
- Active: [pending analysis]. No dedicated active-state token has appeared yet.
- Disabled: buttons and inputs use `disabled:pointer-events-none disabled:opacity-50`; inputs also use `disabled:cursor-not-allowed`.
- Loading: pending actions disable the button and change visible button text.
- Error: fields set `data-invalid`; controls set `aria-invalid`; error text uses `text-destructive`.

## Project Notes
- shadcn-compatible UI primitives live under `apps/web/src/shared/ui`.
- Tailwind CSS variables live in `apps/web/src/app/styles/globals.css`.
- Shared UI primitives use semantic tokens; product pages should not use raw colour utilities when semantic tokens exist.
- The current application uses only the minimal FSD layers required by the walking skeleton: `app`, `pages`, and `shared`.
