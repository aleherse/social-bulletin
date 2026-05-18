# ADR-0010: Adopt lexik/jwt-authentication-bundle for Stateless JWT Auth

- Status: Accepted
- Date: 2026-05-17

## Context

The API needs to authenticate requests without server-side session state. The application is an SPA + API architecture where the frontend and backend are separate. Stateless authentication allows horizontal scaling without sticky sessions or a shared session store.

Symfony's SecurityBundle firewall must be configured to validate tokens on each request rather than reading from a server session. LexikJWT is the de-facto standard JWT bundle for Symfony, maintained actively, and compatible with Symfony 7.x. It integrates with the SecurityBundle user provider mechanism and supports token extraction from multiple sources including httpOnly cookies.

## Decision

Adopt `lexik/jwt-authentication-bundle` v3.x as the JWT issuance and validation layer for the API.

Configure the SecurityBundle firewall as `stateless: true`. Token extraction is configured to read from the `token` httpOnly cookie only; the `Authorization` header extractor is disabled. RSA key pairs are stored under `apps/api/config/jwt/`; they are generated as part of `make init`, but only if the key files do not already exist (idempotent). The `apps/api/config/jwt/` directory is excluded from version control via `.gitignore`.

The `User` aggregate does not implement `UserInterface` to keep the domain free of Symfony coupling. A dedicated `SecurityUser` bridge class adapts the aggregate for the Symfony user provider required by LexikJWT.

A `AuthTokenPort` application port abstracts JWT issuance. The concrete `LexikJwtTokenIssuer` adapter in `Infrastructure/Security/` is the only implementation.

## Consequences

Positive outcomes:

- No server-side session storage required; API scales horizontally without shared state.
- Token is self-contained; expiry and claims are embedded.
- `AuthTokenPort` keeps the use case independently testable without the JWT library.

Tradeoffs:

- RSA private key must be kept secure and excluded from version control in production.
- Token revocation requires additional infrastructure (e.g., a denylist) not present in this feature.
- Cookie-based delivery means the token is bound to the domain and not usable by non-browser clients without additional configuration.

Follow-ups:

- Consider token refresh strategy before token TTL becomes a user-experience concern.
- Evaluate token revocation if account deletion or forced logout is required.