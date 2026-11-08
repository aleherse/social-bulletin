# ADR-0024: Adopt AppSync Events And AsyncAPI For Realtime Push

- Status: Accepted
- Date: 2026-11-02

## Context

Users expect what they see to stay current:
a change made by one person should reach everyone else looking at it without a manual refresh.
The system needs a way to push server-side events to browsers as they happen.

The API runs on AWS Lambda (ADR-0016), which cannot hold a websocket open,
so pushing to browsers needs a managed pub/sub service.
The push channel needs its own documented contract and its own way to authenticate,
and it must keep local development and CI free of paid or cloud-only dependencies.

## Decision

- The websocket SHALL carry server events only: commands and queries stay on HTTP, and only `apps/api` publishes.
- Production SHALL use AWS AppSync Events, created by a realtime stack in `infrastructure/` for `live` and `preview`.
- `packages/core` SHALL define a port for publishing notifications.
  `apps/api` SHALL provide an AppSync adapter for production and a lightweight websocket container for local development and CI.
  LocalStack SHALL NOT be required.
- Handlers in `packages/core/src/Application/<Aggregate>/Event/` SHALL listen to domain events
  and publish a separate `final readonly` notification, kept beside them, carrying the ids a client needs to refetch,
  on channels scoped to the recipient, per user and per community.
- Browsers SHALL authenticate with a short-lived signed ticket issued by a new authenticated API endpoint.
  An AppSync Lambda authorizer SHALL verify it and decide which channels the user may subscribe to.
  The cookie domain SHALL NOT be widened.
- An AsyncAPI document SHALL describe the channels and payloads and SHALL be committed.
  `make docs-build` renders it at `/event/` of the documentation site (ADR-0023) and links it from the landing page.
  `make asyncapi-check` SHALL fail the merge gate (ADR-0015) when it differs from the code, like `make openapi-check`.
- Notifications SHALL be published in the request after the write, with no outbox (ADR-0021).
  Clients SHALL treat a notification as a hint to refetch, and SHALL refetch on reconnect.

## Consequences

- Commands, queries and their contracts do not change.
- AppSync Events has no request/response, correlation or error model,
  so commands and queries over the websocket would rebuild what JSON:API and TanStack Query already provide.
  Doing so, client publishing, or an asynchronous event transport would need their own ADR.
- A new AWS service, a Lambda authorizer, a second contract document and one more local container need maintenance.
- LocalStack is not used because AppSync is a paid feature there and its Events coverage is unverified.
  The local adapter is not AppSync, so authorizer behaviour is only exercised on `preview`.
- A new notification is one class plus one handler; domain events stay free to change.
- A failed publish fails the request after the write is stored, and a crash loses the notification.
  A missed notification costs freshness, never correctness, until a transactional outbox exists.
- The Lambda authorizer reads the API's JWT public key from the environment's `jwt-public-key` Parameter Store entry,
  which must exist before the realtime stack serves traffic.
- Connecting and subscribing through the Lambda authorizer while publishing with IAM is documented by AppSync only by implication,
  so it must be confirmed on `preview` before `live`.
