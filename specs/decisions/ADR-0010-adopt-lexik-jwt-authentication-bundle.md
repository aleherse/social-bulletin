# ADR-0010: Adopt lexik/jwt-authentication-bundle and httpOnly Cookie JWT Delivery

- Status: Accepted
- Date: 2026-06-12

## Context

The API needs stateless authentication with signed tokens. Browser storage must keep tokens away from JavaScript while still sending them automatically on API requests.

## Decision

Adopt `lexik/jwt-authentication-bundle` as the JWT issuance and validation layer for the API.

Configure the SecurityBundle firewall as `stateless: true`. RSA key pairs are stored under `apps/api/config/jwt/`; they are generated as part of `make init` using Symfony console command `lexik:jwt:generate-keypair`, but only if the key files do not already exist. The `apps/api/config/jwt/` directory is excluded from version control via `.gitignore`.

Deliver the JWT as an `httpOnly` cookie named `token` with these flags:

- `HttpOnly`
- `Secure`
- `SameSite=Strict`
- `Path=/`

The frontend never reads or manages the token value. The browser sends it automatically on same-site requests. The `Authorization` header extractor is disabled, and Lexik reads tokens only from the `token` cookie.

Docker PHP entrypoint SHALL generate a CA root certificate via `symfony server:ca:install` if not generated already.

Generated certificates SHALL be available with no root ownership in `docker/php/certs` and the folder added to `.gitignore`.

Vite development environment SHALL serve HTTPS using the generated certificate.

## Consequences

- API authentication uses signed JWTs.
- Security remains stateless.
- JWTs are stored only in an `httpOnly` cookie.
- The frontend never reads or manages token values.
- Local development needs generated HTTPS certificates.
- JWT keys and certificates must stay out of version control.
- Key rotation, cookie flags, and CSRF assumptions need ongoing review.
