# Tasks: Add a Hero section to the homepage

## Phase 1: Setup & foundations
- [x] [T01] Add `home.hero.title` and `home.hero.description` and reword `home.form.title` to "Register or sign in" in `apps/web/src/shared/i18n/locales/en/common.json` (US1, US2)

## Phase 2: Tests first
- [x] [T02] Add tests in `apps/web/src/pages/home/ui/home-page.test.tsx`: Hero headline is the level-1 heading and the description is visible when signed out (US1)
- [x] [T03] Add tests in `apps/web/src/pages/home/ui/home-page.test.tsx`: the Hero is visible while the current-user query is pending and when signed in, and appears before the form or greeting in document order (US1, US2)
- [x] [T04] Add a test in `apps/web/src/pages/home/ui/home-page.test.tsx`: the signed-out form no longer shows a second "Welcome to Social Bulletin" and shows "Register or sign in" (US1)

## Phase 3: Implementation
- [x] [T05] Create `Hero` in `apps/web/src/pages/home/ui/hero.tsx`: `<h1>` and supporting `<p>` via `useTranslation`, wrapping text, no fixed widths (US1, US3)
- [x] [T06] Render `Hero` above the state content and switch `main` to a centred column in `apps/web/src/pages/home/ui/home-page.tsx` (US1, US2, US3)

## Phase 4: Integration & validation
- [x] [T07] Add `apps/web/e2e/hero.spec.ts`: at 320×568 and at a desktop viewport the headline and description are visible without scrolling, and the page has no horizontal overflow (US3)
- [x] [T08] Run focussed checks: `make web-unit PATHS=apps/web/src/pages/home/ui/home-page.test.tsx`, then `make lint FILES="apps/web/src/pages/home/ui/hero.tsx apps/web/src/pages/home/ui/home-page.tsx apps/web/src/pages/home/ui/home-page.test.tsx apps/web/e2e/hero.spec.ts apps/web/src/shared/i18n/locales/en/common.json"`; run `make web-e2e PATHS=apps/web/e2e/hero.spec.ts` when the stack is available (US1, US2, US3)

## Dependency Graph
T01 → T02 → T05 → T06 → T08
T01 → T03 ↗
T01 → T04 ↗
T06 → T07 → T08

## MVP Scope
T01–T06 and the unit run in T08: the Hero in every state and the reworded form heading.
