# ADR-0601: Adopt lexik/jwt-authentication-bundle and httpOnly Cookie JWT Delivery

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

`mkcert` SHALL be installed inside the nginx container image.

The nginx entrypoint SHALL, on startup, run `mkcert -install` once and then issue a TLS certificate if it doesn't already exist:

- `mkcert -cert-file docker/certs/cert.pem -key-file docker/certs/cert-key.pem <DEV_TLS_HOSTNAME> localhost 127.0.0.1` for the API hostname.
- Copy `$(mkcert -CAROOT)/rootCA.pem` to `docker/certs/`
- Ensure files in `dockers/cert` have the right ownership

Nginx configuration SHALL server HTTPS using the generated certificate and redirect HTTP to HTTPS requests.

The `docker/certs/` directory SHALL be added to `.gitignore`.

Vite development environment SHALL serve HTTPS using `docker/certs/cert.pem` and `docker/certs/cert-key.pem`.

## Consequences

- API authentication uses signed JWTs.
- Security remains stateless.
- JWTs are stored only in an `httpOnly` cookie.
- The frontend never reads or manages token values.
- Local development needs `mkcert` installed in the nginx container image; the Node container consumes the certificates it produces.
- Both containers share `docker/certs/` via a bind mount so one CA root covers all local hostnames.
- The nginx container must start (or have run its entrypoint) before Vite attempts to read the web certificate.
- JWT keys and certificates must stay out of version control.
- Key rotation, cookie flags, and CSRF assumptions need ongoing review.
