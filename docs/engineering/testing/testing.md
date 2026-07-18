# Testing conventions

- API behaviour: Behat scenarios in `apps/api/features/`,
  JMESPath assertions in `Then` steps,
  `Given` steps create data through application code.
  Every scenario starts from the DSLR `fixtures` snapshot;
  `@fixtures`-tagged features run only via `make db`.
- Core logic: PHPSpec specs in `packages/core`.
- Frontend logic: Vitest + Testing Library next to the source.
- Browser journeys: Playwright specs in `apps/web/e2e/`
  run against the compiled frontend and real API.
