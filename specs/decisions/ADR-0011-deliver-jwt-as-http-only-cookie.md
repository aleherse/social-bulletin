# ADR-0011: Deliver JWT as httpOnly Cookie

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
- `Secure`: transmitted over HTTPS only.
- `SameSite=Strict`: mitigates CSRF by restricting cookie transmission to same-site requests.
- `Path=/`: cookie applies to all API paths.

The cookie name is `token`. The frontend never reads or manages the token value; it relies on the browser to transmit it automatically. The `lexik/jwt-authentication-bundle` is configured to extract the token from this cookie.

Because the `Secure` cookie flag requires HTTPS, local development must provide trusted TLS certificates. Development certificates should be generated with `mkcert` (using `docker run alpine/mkcert`).

Existing nginx configuration must be updated to serve HTTPS with the generated TLS certificates. nginx must also listen on HTTP and redirect all HTTP requests to the equivalent HTTPS URL so browser requests consistently use the secure origin required for cookie delivery.

Frontend dev server must be updated to serve HTTPS with the generated TLS certificates.

## Consequences

Positive outcomes:

- Token is immune to XSS; no JavaScript can read or exfiltrate it.
- Browser transmits the cookie automatically; no frontend token management required.
- `SameSite=Strict` eliminates classical CSRF risk for this cookie.
- Local development can exercise the same `Secure` cookie behaviour as production by using trusted TLS certificates.
- HTTP-to-HTTPS redirection prevents accidental insecure local access paths where the cookie would not be sent.

Tradeoffs:

- `Secure` flag requires HTTPS in production; local development must use a self-signed certificate.
- Local development requires `mkcert` installation and a generated local certificate before secure cookie flows can be tested.
- nginx configuration must maintain both HTTPS serving and HTTP redirection paths.
- `SameSite=Strict` may break OAuth redirect flows or cross-origin scenarios in future features; an ADR must be created to change this flag.
- Cookie-bound tokens are not usable by non-browser API clients without custom header extraction.

Follow-ups:

- Generate local development certificates with `mkcert` and mount them into nginx.
- Update existing nginx configuration to serve HTTPS and redirect HTTP traffic to HTTPS.
- Verify `Secure` flag behaviour in the Docker development environment before production deployment.
- Document the HTTPS requirement in the deployment checklist.
- Add to the `README.md` instructions to install the certificate in a Windows and Linux machine.
