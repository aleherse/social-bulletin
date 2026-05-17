# ADR-0010: Deliver JWT as httpOnly Cookie

- Status: Accepted
- Date: 2026-05-17

## Context

After successful registration (and future login), the API issues a JWT to the client. The token must be stored in the browser and transmitted automatically on subsequent requests. The choice of storage mechanism has direct security implications.

Options considered:
- `localStorage` / `sessionStorage`: accessible to JavaScript, vulnerable to XSS.
- `Authorization` header with manual token management: frontend must read, store, and attach the token; exposes token to JavaScript.
- `httpOnly` cookie: inaccessible to JavaScript, immune to XSS, transmitted automatically by the browser on same-origin requests.

## Decision

Deliver the JWT as an `httpOnly` cookie with the following flags:
- `HttpOnly`: prevents JavaScript access.
- `Secure`: transmitted over HTTPS only (relaxed to allow HTTP in the `dev` environment only).
- `SameSite=Strict`: mitigates CSRF by restricting cookie transmission to same-site requests.
- `Path=/`: cookie applies to all API paths.

The cookie name is `token`. The frontend never reads or manages the token value; it relies on the browser to transmit it automatically. The `lexik/jwt-authentication-bundle` is configured to extract the token from this cookie.

## Consequences

Positive outcomes:

- Token is immune to XSS; no JavaScript can read or exfiltrate it.
- Browser transmits the cookie automatically; no frontend token management required.
- `SameSite=Strict` eliminates classical CSRF risk for this cookie.

Tradeoffs:

- `Secure` flag requires HTTPS in production; local development must explicitly relax this flag or use a self-signed certificate.
- `SameSite=Strict` may break OAuth redirect flows or cross-origin scenarios in future features; an ADR must be created to change this flag.
- Cookie-bound tokens are not usable by non-browser API clients without custom header extraction.

Follow-ups:

- Verify `Secure` flag behaviour in the Docker development environment before production deployment.
- Document the HTTPS requirement in the deployment checklist.
