# Feature-Sliced Design

`apps/web` is structured with Feature-Sliced Design.

| Layer     | Purpose                                             |
|-----------|-----------------------------------------------------|
| `app/`    | providers and application wiring                    |
| `pages/`  | page slices (route-level features)                  |
| `shared/` | infrastructure: `api`, `i18n`, `lib`, `ui`          |

## Rules

- Slices expose a public API through `index.ts`;
  import through it, never from slice internals.
- UI composes shadcn/ui primitives from `src/shared/ui`.
- Copy goes through the `shared/i18n` public API, never raw strings.
