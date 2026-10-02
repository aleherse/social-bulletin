# Plan: Add a Hero section to the homepage

## Architecture
Frontend only, in the `pages/home` slice of `apps/web` (Feature-Sliced Design). No API, backend or `packages/core` change.

- `HomePage` (`apps/web/src/pages/home/ui/home-page.tsx`) today renders one `<main className="flex min-h-svh items-center justify-center p-4">` holding a loading line, `HelloView` or `RegistrationForm`.
- Add `Hero` (`pages/home/ui/hero.tsx`), rendered by `HomePage` above the state-dependent content, outside the `isPending` branch, so it shows while loading, signed out and signed in.
- `main` becomes a column (`flex-col`, centred, with a gap) so the Hero precedes the form/greeting in DOM and visual order.
- Copy goes through `@/shared/i18n` (`fsd.md`; `application-frontend-0001`), keys added to `shared/i18n/locales/en/common.json`.
- UI composes plain elements; the headline is an `<h1>`, the description a `<p>` beneath it. `CardTitle` is a `div`, so the form has no heading element and the Hero's `<h1>` is the page's only main heading.
- Layout uses wrapping text, `max-w-*` and padding only, no fixed widths, so 320 px does not scroll horizontally.

## Data Models
None. Two new translation keys:

- `home.hero.title` = "Welcome to Social Bulletin"
- `home.hero.description` = "Your place to stay up to date with the latest social movements events" (verbatim, no added full stop)

One changed key: `home.form.title` from "Welcome to Social Bulletin" to "Register or sign in" (clarification 1a).

## Interface Contracts
- `Hero(): JSX.Element` — no props; internal to the `home` slice, not exported from `pages/home/index.ts` (knip flags unused exports).
- `HomePage` public API unchanged.

## Implementation Phases
1. Copy — add the Hero keys and reword `home.form.title` in `common.json`.
2. Hero component — `hero.tsx`, tests first (`home-page.test.tsx`).
3. Composition — column layout in `home-page.tsx`, Hero rendered in every state.
4. Browser check — Playwright spec for the 320 px and desktop viewports.

## Decisions & Rationale
| Decision | Rationale | Alternatives considered |
|----------|-----------|-------------------------|
| Form heading reads "Register or sign in" | Clarification 1a recommended a task-focused heading; the wording is a planning default and matches the form description "register or sign in" | Remove heading (1b) or keep (1c): not chosen by the person |
| Hero outside the loading branch | Spec requires it while loading and in both signed-in states | Render per state: triplicates it |
| `Hero` is a separate component in `pages/home/ui` | Single responsibility; mirrors `HelloView`/`RegistrationForm` | Inline in `HomePage`: grows the page component |
| Hero on the page, not a `shared/ui` primitive | One use; no reuse yet | Shared primitive: premature |
| English only | Only `locales/en` exists; spec Non-Goal | Adding locales: out of scope |

## Constraints & Risks
- Merge-stage work (no narrowable form): `make web-tsc` (`tsc -b`) and `make web-knip`. The implementing stage still runs any single tool with no narrower invocation itself and logs the friction, per the focussed-checks rule.
- No routed-guidance content changes, so `make check-guidance` is not owed.
- Risk: the sign-in e2e (`apps/web/e2e/session.spec.ts`) selects by label and button name, not the form heading; grep of `Welcome` in `apps/web/e2e` finds no match, so the rename does not break it. Searched: `grep -rn "Welcome to Social Bulletin" apps/web` (excluding `node_modules`) finds only `common.json`.
- Risk: the 320 px criterion cannot be proven by jsdom; it is covered by the Playwright spec (T07), which needs the stack (`make db`).
- Risk: `min-h-svh` centring with a taller column could push content below the fold on small phones; the Hero keeps compact text sizes so the two Hero texts stay in the first viewport (checked in T07 at 320×568).
