# ADR-0017: Adopt Symfony Messenger As The Command Bus

- Status: Accepted
- Date: 2026-08-10

## Context

Write operations were methods on domain services
(`MovementService::create()`, `::update()`, `::submit()`),
so every new write grew a service that already mixed reads and writes,
and nothing named the use cases the API supports in one place.
ADR-0005 reserved `packages/core` for "future domain and application code",
but only a `Domain` namespace ever grew there.

`apps/api` needs one entry point into the write use cases
that does not drag Symfony into `packages/core`.

## Decision

### Messenger owns the bus

`symfony/messenger` SHALL carry write use cases from `apps/api` into `packages/core`,
configured as a single bus named `command.bus` and set as the default bus.

No Messenger transport SHALL be configured:
commands are handled synchronously in the request that dispatched them,
so the write has landed by the time the dispatching controller reads the aggregate back
and existing HTTP responses are unchanged.

### Handlers live in `Core\Application` and stay framework-free

`packages/core/src/Application/<Aggregate>/` SHALL hold the write use cases,
one command plus one handler per use case,
with the business rules staying in the aggregate.

Handlers SHALL return `void`.
A write use case reports failure by throwing,
and a caller that needs the affected aggregate reads it back through a domain service,
so the command SHALL carry the aggregate's identity:
`CreateMovementCommand` mints its own `MovementId`
rather than leaving the handler to generate one the caller could never learn.

Handlers SHALL NOT carry `#[AsMessageHandler]`.
They are registered from `apps/api/config/services.yaml`
with the `messenger.message_handler` tag on the `command.bus` bus,
which keeps `packages/core` free of Symfony (ADR-0005).

### `apps/api` dispatches only through `CommandBus`

`apps/api` SHALL reach handlers only through `App\Messenger\CommandBus`,
a `HandleTrait` wrapper that returns nothing —
keeping `HandleTrait`'s guarantee that exactly one handler ran, synchronously —
and rethrows the exception nested inside Messenger's `HandlerFailedException`,
so controllers keep catching domain exceptions
(`MovementNotFound`, `MovementNotDraft`, `InvalidMovement`, `InvalidEmailAddress`)
and keep mapping them to 404, 409, 400, and 422.

### Reads are out of scope

Queries SHALL keep their current shape:
a domain service method reached directly from the controller.
`MovementService` keeps `byAuthor()` and `authorMovement()` and loses its write methods;
no query bus, no query objects, no read models.
Write handlers MAY reuse a query service
for the ownership-scoped lookup that guards them.

## Consequences

- The write use cases the API supports are enumerable:
  one command class per use case under `Core\Application`.
- `packages/core` stays framework-free;
  only `apps/api` knows Messenger exists.
- Handlers are specced with PHPSpec under `packages/core/spec/Application/`,
  where the write examples of `MovementServiceSpec` moved.
- Dispatching is indirect:
  a reader following a controller now goes controller → bus → handler,
  and `debug:messenger` is the map.
- The bus stays synchronous, so nothing is queued and nothing retries.
  Moving a command to an async transport later would need its own ADR,
  but no longer changes what a caller can do with the dispatch itself.
- `CommandBus::dispatch()` returns `void`,
  so a controller costs one extra read — `MovementService::authorMovement()` —
  to render the aggregate it just wrote,
  and that read sits inside the same `try` as the dispatch.
- Reads and writes are now asymmetric by design;
  a future decision to introduce a query bus would supersede this one.
