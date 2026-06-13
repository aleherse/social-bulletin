# ADR-0013: Adopt Walking Skeleton Registration Flow

- Status: Deprecated
- Date: 2026-05-25

## Context

The project needs a minimal end-to-end flow to prove frontend, API, database, and cookie authentication work together.

## Decision

Adopt a minimum registration-or-login walking skeleton.

When the current browser user is not authenticated, the homepage must render a registration form that asks only for an email address.

When the form is submitted, the frontend sends the email address to the API. The API checks whether a user with that email already exists.

If the user exists, the API issues a JWT for that user and delivers it using the configured cookie-based JWT mechanism.

If the user does not exist, the API creates the user first, then issues a JWT for the new user and delivers it using the same cookie-based JWT mechanism.

After successful submission, the frontend must navigate to, or re-render as, the authenticated hello view. The hello view must greet the authenticated user using the user's email address.

On page load, the frontend must be able to determine whether the browser already has a valid authenticated user by calling the API. If the API confirms authentication, the frontend shows the hello view. If not, the frontend shows the email registration form.

If the user is logged in a logout link is shown, when clicked the JWT cookie is cleared and frontend must navigate to, or re-render as, the registration form.

The API contract should remain intentionally small:

- One endpoint to submit an email address and create-or-authenticate the user.
- One endpoint to return the current authenticated user from the JWT cookie.
- One endpoint to clear the JWT cookie.

## Consequences

- Proves the main stack with one small user journey.
- Supports email-only create-or-login, current-user readback, and logout.
- Keeps the API surface intentionally small.
- Provides a basis for backend, frontend, and E2E tests.
- Passwords, email verification, refresh tokens, roles, and account management stay out of scope.
- This remains a walking skeleton, not complete production authentication.


