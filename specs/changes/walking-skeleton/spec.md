# Walking Skeleton Specification

## Summary

The walking skeleton proves the smallest useful end-to-end flow for Social
Bulletin: an unauthenticated visitor registers or signs in with an email
address, receives a JWT in an httpOnly cookie, sees a personalised hello view,
and can log out.
The slice connects the React frontend, Symfony API, PostgreSQL persistence,
cookie-based authentication, nginx HTTPS routing, and the agreed test
toolchain.

## Architectural context

- Monorepo layout: `apps/web` (frontend), `apps/api` (Symfony HTTP), and
  `packages/core` (framework-free domain logic).
- Local development runs through Docker Compose and Makefile entrypoints.
- nginx terminates HTTPS for `DEV_FRONT_URL` and `DEV_API_URL`.
- Authentication uses Lexik JWT delivered as an httpOnly cookie named
  `token`; the frontend never reads the token value.
- Persistence uses PostgreSQL with Doctrine DBAL migrations in schema
  `DATABASE_SCHEMA` (`bulletin`).
- Frontend uses React, Vite, Feature-Sliced Design, TanStack Query, and
  shadcn/ui primitives for the registration form and hello view.
- Tests: Behat for API behaviour, Playwright for the browser journey against
  the real API, and Vitest where isolated frontend logic warrants it.

## Scope

### In scope

- Homepage behaviour for authenticated and unauthenticated visitors.
- Email-only registration-or-login form on the homepage when unauthenticated.
- API endpoint to accept an email address, find or create a user, and issue a
  JWT cookie.
- API endpoint to return the current authenticated user from the JWT cookie.
- API endpoint to clear the JWT cookie (logout).
- Minimal `users` table and migration in `bulletin`.
- User persistence and lookup logic in `packages/core`; HTTP wiring in
  `apps/api`.
- Basic validation and user-visible error states for invalid or empty email
  input.
- Behat scenarios covering register, returning-user login, current-user
  lookup, and logout.
- One Playwright end-to-end journey covering register, page refresh while
  authenticated, and logout.

### Out of scope

- Passwords, email verification, profile editing, and account recovery.
- Full i18n coverage beyond whatever minimal copy the skeleton needs to
  render.
- OpenAPI documentation beyond what Symfony bundles provide by default.
- Production deployment, AWS serverless packaging, and CI workflow wiring.
- nginx serving compiled frontend assets for this slice; Vite dev server at
  `DEV_FRONT_URL` is sufficient for local development and Playwright.
- Shared Behat `fixtures.feature` baseline data beyond what this slice
  requires; scenario-specific `Given` steps may seed their own users.

## Actors

- **Visitor** — an unauthenticated browser user on `DEV_FRONT_URL`.
- **Registered user** — a visitor who has submitted a valid email address and
  holds a valid JWT cookie.
- **API** — Symfony application at `DEV_API_URL`.
- **Database** — PostgreSQL storing user records.

## Acceptance scenarios

### Scenario 1: Unauthenticated homepage

GIVEN no valid JWT cookie is present for `DEV_API_URL`
WHEN the visitor opens `DEV_FRONT_URL`
THEN the homepage renders a registration form asking only for an email
  address
AND no authenticated hello view is shown.

### Scenario 2: Register with a new email

GIVEN no valid JWT cookie is present
AND no user exists with email `new.user@example.com`
WHEN the visitor submits the registration form with
  `new.user@example.com`
THEN the API creates a user record with that email
AND the API sets an httpOnly `token` cookie on the API domain
AND the frontend shows an authenticated hello view greeting
  `new.user@example.com`
AND the registration form is no longer shown.

### Scenario 3: Sign in with an existing email

GIVEN a user already exists with email `existing.user@example.com`
AND no valid JWT cookie is present
WHEN the visitor submits the registration form with
  `existing.user@example.com`
THEN the API does not create a duplicate user
AND the API sets an httpOnly `token` cookie
AND the frontend shows an authenticated hello view greeting
  `existing.user@example.com`.

### Scenario 4: Restore session on page load

GIVEN the visitor previously authenticated successfully
AND a valid JWT cookie is still present
WHEN the visitor reloads `DEV_FRONT_URL`
THEN the frontend requests the current user from the API
AND the authenticated hello view is shown without submitting the form again.

### Scenario 5: Logout

GIVEN the visitor is authenticated and sees the hello view
WHEN the visitor clicks the logout link
THEN the API clears the JWT cookie
AND the homepage returns to the registration form
AND a subsequent page load shows the registration form, not the hello view.

### Scenario 6: Invalid email submission

GIVEN no valid JWT cookie is present
WHEN the visitor submits the registration form with an invalid email value
THEN the API responds with a client error
AND the frontend shows a visible validation or error message
AND no user record is created
AND no JWT cookie is set.

## Interface notes

### Frontend routes

- `/` — homepage; shows registration form or authenticated hello view
  depending on session state.

### API endpoints

All endpoints are served over HTTPS at `DEV_API_URL`.
Cross-origin requests from `DEV_FRONT_URL` must succeed per CORS
configuration.

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| POST | `/api/session` | Public | Accept `{ "email": string }`, find or create user, set JWT cookie |
| GET | `/api/me` | Cookie JWT | Return current user `{ "email": string }` or 401 |
| POST | `/api/logout` | Cookie JWT (optional) | Clear JWT cookie |

Request and response bodies use JSON.
The frontend uses TanStack Query (or equivalent fetch wrapper) with
`credentials: 'include'` so cookies flow on same-site HTTPS requests.

### JWT cookie

- Name: `token`
- Flags: `HttpOnly`, `Secure`, `SameSite=Strict`, `Path=/`
- Issued by Lexik JWT Authentication Bundle on successful session creation.
- Cleared on logout by expiring or removing the cookie in the API response.

### Frontend session check

On homepage load, the frontend calls `GET /api/me`.
A 200 response drives the hello view; 401 drives the registration form.
The frontend must not read or store the raw JWT value.

### UI composition

- Registration form and hello view use shadcn/ui primitives (for example
  `Input`, `Button`, and layout/card components) in the appropriate FSD
  layers.
- The hello view shows the user's email and a logout link or button.

## Data notes

- Table: `bulletin.users` (exact name may follow project naming
  conventions).
- Minimum columns: primary key (UUID v7), unique `email`, and created
  timestamp.
- Email comparison should be case-insensitive for lookup and uniqueness.
- User creation and lookup live in `packages/core`; the API controller
  delegates to core services or use cases.

## Test expectations

### Behat (API)

Restore the DSLR snapshot before each scenario.
Cover at minimum:

- POST `/api/session` creates a user and returns a `Set-Cookie` header for
  `token`.
- POST `/api/session` with an existing email reuses the user and sets
  `token`.
- GET `/api/me` returns the authenticated email when a valid cookie is
  present.
- GET `/api/me` returns 401 when no cookie is present.
- POST `/api/logout` clears authentication; a subsequent GET `/api/me`
  returns 401.
- POST `/api/session` with invalid email returns 4xx without creating a
  user.

Use JMESPath in `Then` steps for JSON assertions where applicable.
Scenario-specific `Given` steps may insert users directly through application
code.

### Playwright (browser E2E)

Restore the DSLR snapshot before each scenario.
Build or serve the frontend as required by the test setup.
One journey should:

1. Open `DEV_FRONT_URL` and see the registration form.
2. Submit a new email and see the hello view with that email.
3. Reload the page and still see the hello view.
4. Click logout and return to the registration form.

Playwright runs against the real API with HTTPS certificates from the local
mkcert setup.

### Vitest (frontend unit)

Add unit or component tests only where they give clear value for validation
logic or conditional rendering of the registration form versus hello view.
Full session behaviour belongs in Playwright and Behat.

### Makefile entrypoints

Implementation should wire or extend targets consistent with ADR-0015 and
ADR-0003, including `make api-tests`, `make web-e2e`, and database setup via
`make db` / `make init` as needed for the skeleton to run locally and in CI
later.

## Open questions

- None intentionally left open; adopt sensible defaults during build (for
  example exact route prefix `/api`, minimal email validation rule, and FSD
  slice placement for auth UI).
