# ADR-0012: Adopt Walking Skeleton Registration Flow

- Status: Deprecated
- Date: 2026-05-25

## Context

The project has decisions for a Symfony API, React web app, PostgreSQL persistence, Docker-based runtime, nginx serving, and JWT authentication. The next implementation step needs the smallest useful product behaviour that proves these layers work together without building a complete authentication product too early.

A walking skeleton should exercise the full stack end to end:

- Browser-rendered frontend
- API request from frontend to backend
- Database-backed user lookup and creation
- JWT issuance through the existing authentication decision
- Cookie delivery through the browser
- Authenticated frontend state after the cookie is set

This flow is intentionally not a full account, login, password, verification, profile, or session-management feature. It exists to validate vertical integration and establish the first observable user journey.

## Decision

Adopt a minimum registration-or-login walking skeleton.

When the current browser user is not authenticated, the homepage must render a registration form that asks only for an email address.

When the form is submitted, the frontend sends the email address to the API. The API checks whether a user with that email already exists.

If the user exists, the API issues a JWT for that user and delivers it using the configured cookie-based JWT mechanism.

If the user does not exist, the API creates the user first, then issues a JWT for the new user and delivers it using the same cookie-based JWT mechanism.

After successful submission, the frontend must navigate to, or re-render as, the authenticated hello view. The hello view must greet the authenticated user using the user's email address.

On page load, the frontend must be able to determine whether the browser already has a valid authenticated user by calling the API. If the API confirms authentication, the frontend shows the hello view. If not, the frontend shows the email registration form.

The API contract should remain intentionally small:

- One endpoint to submit an email address and create-or-authenticate the user.
- One endpoint to return the current authenticated user from the JWT cookie.

The walking skeleton must use the existing Docker, nginx, Symfony, React, PostgreSQL, and JWT decisions. It must not introduce host-level runtime requirements, a second authentication mechanism, password handling, email verification, refresh tokens, logout, roles, or account-management screens.

## Consequences

Positive outcomes:

- Proves the repository can deliver one complete browser-to-database-to-browser product slice.
- Exercises frontend rendering, API transport, persistence, JWT creation, cookie delivery, and authenticated readback.
- Keeps first user journey small enough to build, test, and change safely.
- Establishes reusable seams for future authentication and account features without committing to full auth complexity now.

Tradeoffs:

- Email-only create-or-login is not production-grade authentication.
- Anyone who knows an email address can obtain a token for that address until a stronger auth flow is introduced.
- No logout, token refresh, password, verification, or account recovery behaviour exists in this skeleton.
- Future authentication work must replace or harden this flow before any real user data is protected.

Follow-ups:

- Add automated tests that cover unauthenticated homepage rendering, existing-user login, new-user creation, JWT cookie issuance, and authenticated hello rendering.
- Document the walking skeleton as a development-only or pre-production authentication flow until a stronger authentication ADR supersedes it.
- Create a new ADR before adding passwords, magic links, OAuth, email verification, token refresh, logout, or account recovery.
- Update `specs/features/` when this walking skeleton is implemented because it creates observable developer and user-facing behaviour.

## Deprecation

Deprecated on 2026-06-03 after the walking skeleton was implemented and verified. No superseding ADR exists yet; create a new ADR before replacing or hardening this development-only authentication flow.
