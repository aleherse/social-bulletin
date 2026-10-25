# ADR-0022: Adopt JSON:API For The HTTP Contract

- Status: Accepted
- Date: 2026-10-25

## Context

The API needs one request and response shape that clients, controllers and the OpenAPI document all share,
instead of each route inventing its own JSON.

## Decision

Every `/api` request and response that carries a resource, and every error, SHALL be a JSON:API document
served as `application/vnd.api+json`.

- `league/fractal` with its `JsonApiSerializer` builds response documents.
  It lives in `apps/api` only; `packages/core` stays framework-free.
- A controller returns the `Core\Application` model (or a list of them) and carries `#[JsonApi(<Transformer>::class, '<type>')]`.
  `JsonApiViewListener` renders it on `kernel.view`.
- A controller reads its body through `#[JsonApiPayload('<type>', <Command>::class)] array $payload`.
  `JsonApiPayloadResolver` unwraps `data.attributes` and refuses other media types (415),
  malformed documents (400) and a wrong `type` or `id` (409) (`application-framework-0007`).
- Query models hold scalars and dates only, so transformers and the OpenAPI describers read them as they are.
- Domain failures and authentication failures (missing, invalid or expired token) are JSON:API error documents.

## Consequences

- Clients unwrap `data` and `attributes`; the web client does it in `shared/api/http.ts`.
- Controllers are one dispatch and a return, and the wire shape lives in one transformer per resource.
- The HTTP shape is less visible in the controller body; the attribute above `__invoke` is where to look.
- Fractal is a runtime dependency to keep current.
- Error titles are message keys until the backend has a translation catalogue.
