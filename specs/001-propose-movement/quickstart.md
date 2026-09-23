# Quickstart: Propose a Movement

How to run and verify this feature locally once implemented.

## Prerequisites

- Docker environment up: `make up` (nginx, PHP-FPM, Node, PostgreSQL).
- Database rebuilt with the new migration and fixtures snapshot:
  `make db` (creates DB, runs migrations, loads `fixtures.feature`,
  snapshots via DSLR — ADR-0015).

## Try it in the browser

1. Open `https://dev.app.social.aleherse.com` (DEV_FRONT_URL).
2. Sign in with any email (email session sets the `token` cookie).
3. Go to the movements page, create a draft: title, category, area,
   location (skip location for `international`), description optional.
4. Save — the draft appears in "my movements" with status `draft`.
5. Edit the draft, add a markdown description, and submit it:
   status becomes `proposed`.
6. Try submitting a draft with an empty description — the API refuses
   and the form explains a description is required.

This walkthrough is automated in `apps/web/e2e/movements.spec.ts`;
run `make web-e2e` to check it without clicking through by hand.

## Try it against the API

```bash
# Sign in (sets the token cookie in cookies.txt)
curl -k -c cookies.txt -X POST \
  https://dev.api.social.aleherse.com/api/session \
  -H 'Content-Type: application/json' \
  -d '{"email": "me@example.com"}'

# Create a draft
curl -k -b cookies.txt -X POST \
  https://dev.api.social.aleherse.com/api/movements \
  -H 'Content-Type: application/json' \
  -d '{"title": "Community Gardens", "category": "cooperative",
       "area": "municipality", "location": "Sheffield"}'

# Submit it (replace {id}; fails with 400 while description is empty)
curl -k -b cookies.txt -X POST \
  https://dev.api.social.aleherse.com/api/movements/{id}/submit
```

## Run the tests

```bash
make php-unit   # PHPSpec: Movement + MovementService specs (core)
make api-tests  # Behat: features/movements.feature (api)
make web-unit   # Vitest: form + list component tests (web)
make web-e2e    # Playwright: e2e/movements.spec.ts browser journeys
make lint       # deptrac, phpstan, ecs, tsc, eslint, knip, prettier
```

`make tests` runs the four test suites above in that order
(`make lint` stays separate).
`make web-e2e` rebuilds the frontend first
and drives the compiled bundle nginx serves against the real API,
so `make up` must be running.

Behat and Playwright scenarios restore the DSLR `fixtures` snapshot
between runs;
never recreate the snapshot from a test run (ADR-0015).
