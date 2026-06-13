# Registration flow

## Overview

Email-only create-or-login walking skeleton proving frontend, API, database, and cookie JWT authentication.

## Behaviour

### Unauthenticated visitor

- Homepage renders a registration form asking only for an email address.

### Submit email

- Frontend sends email to `POST /api/auth/register`.
- API creates user if missing, otherwise authenticates existing user.
- API issues JWT in `httpOnly`, `Secure`, `SameSite=Strict` cookie named `token`.

### Authenticated visitor

- Frontend calls `GET /api/auth/me` on load.
- Hello view greets user by email.
- Logout link calls `POST /api/auth/logout`, clears cookie, returns to registration form.

## API contract

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/api/auth/register` | Create or authenticate user by email |
| GET | `/api/auth/me` | Return current authenticated user |
| POST | `/api/auth/logout` | Clear JWT cookie |

## Out of scope

Passwords, email verification, refresh tokens, roles, and account management.
