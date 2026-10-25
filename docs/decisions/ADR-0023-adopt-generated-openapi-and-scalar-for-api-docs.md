# ADR-0023: Adopt Generated OpenAPI And Scalar For API Docs

- Status: Accepted
- Date: 2026-10-25

## Context

The API contract needs documentation that cannot drift from the code
and that lets a developer try requests, including the cookie session.

## Decision

The OpenAPI document SHALL be generated from the routes and rendered by Scalar.

- `make openapi` dumps the document with `nelmio/api-doc-bundle` into `apps/docs/openapi.json`, which is committed.
  `make openapi-check` fails the merge gate when the file differs from the routes.
- `JsonApiRouteDescriber` derives each response from `#[JsonApi]` and each request body from the command named in `#[JsonApiPayload]`.
  No request DTO or hand-written schema repeats the fields.
- Hand-written parts (security scheme, `Set-Cookie` headers, error schema) live in `nelmio_api_doc.yaml`.
- `make docs-lint` lints the document with Redocly (`@redocly/cli`).
- `make docs-build` renders `apps/docs/dist` with Scalar (`@scalar/api-reference`); nginx serves it at `DEV_DOCS_URL`, and `make init` builds it.
- Scalar sends its requests with `credentials: 'include'` (`customFetch`),
  and CORS allows `DEV_DOCS_URL` with credentials, so signing in stores the `token` cookie.

## Consequences

- Changing a route, command or transformer requires running `make openapi` and committing the result.
- Scalar replaces Redoc because it offers a console; Swagger UI was not chosen for its heavier, older interface.
- The console's cookie value field is unused; the security scheme description tells users to sign in first.
- Two documentation tools (Scalar, Redocly) need to be kept current.
- Hosting the docs outside local development is not decided yet.
