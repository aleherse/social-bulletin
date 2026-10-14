# ADR-0021: Adopt CQRS

- Status: Accepted
- Date: 2026-08-10

## Context

The API has write use cases — create, update, submit — and read use cases — list, show —
that each need one entry point from `apps/api` into `packages/core`,
without dragging Symfony into the core package
and without one service collapsing a write and its matching reads into one class.

A repository built to hydrate an aggregate for mutation is also the wrong shape for answering a query:
it hydrates behaviour (`edit()`, `submit()`, `assertDraft()`) that a `GET` never reaches,
has nowhere to put a join onto another table,
and — if shared by both sides — leaves a reader unable to tell
which call guards a write and which one only answers a query.

This ADR adopts CQRS — Command Query Responsibility Segregation —
splitting writes and reads into separate classes, each reached through its own bus.

## Decision

### Messenger owns two buses, symmetrical with each other

`symfony/messenger` SHALL carry both sides from `apps/api` into `packages/core`,
configured as two buses: `command.bus`, set as the default bus, and `query.bus`.
Neither bus SHALL have a transport configured:
commands and queries are handled synchronously in the request that dispatched them.

`App\Messenger\CommandBus` and `App\Messenger\QueryBus` SHALL be the only way `apps/api` reaches a handler.
Each is a `HandleTrait` wrapper that rethrows the exception nested inside Messenger's `HandlerFailedException`,
so controllers keep catching domain exceptions and keep mapping them to HTTP codes.
`CommandBus::dispatch()` returns nothing, keeping `HandleTrait`'s guarantee
that exactly one handler ran, synchronously;
`QueryBus::dispatch()` returns the handler's result, since a query that reported nothing would have no purpose.

### Commands live in `Command/`, queries in `Query/`, both framework-free

`packages/core/src/Application/<Aggregate>/` SHALL hold one command plus one handler per write use case
in `Command/`, and one query plus one handler per read use case in `Query/`,
with the business rules staying in the aggregate:

```
packages/core/src/Application/Movement/
├── Command/    CreateMovementCommand.php, CreateMovementHandler.php, …
├── Model/      Movement.php, Category.php
├── Provider/   MovementProvider.php, CategoryProvider.php
└── Query/      ListMovementsQuery.php, ListMovementsHandler.php, …
```

A command handler SHALL return `void`:
a write use case reports failure by throwing,
and a caller that needs the affected aggregate reads it back through a query,
so the command SHALL carry the aggregate's identity —
`CreateMovementCommand` mints its own `MovementId`
rather than leaving the handler to generate one the caller could never learn.

A query handler SHALL only call providers and return what they produce —
no SQL, no rules: a query that must refuse has the provider throw

Neither a command handler nor a query handler SHALL carry `#[AsMessageHandler]`;
both are registered from `apps/api/config/services.yaml` by a glob tagged on their own bus,
which keeps `packages/core` free of Symfony (ADR-0005).

### Queries go through providers, commands through repositories

Every query SHALL go through a **provider**:
a single concrete class per aggregate in `packages/core/src/Application/<Aggregate>/Provider/`,
named `<Aggregate>Provider`, constructed with `Doctrine\DBAL\Connection` directly (ADR-0009),
returning models and never aggregates.

Repositories SHALL keep the write side:
`save()`, and the finders that hydrate an aggregate a handler is about to mutate.
Where both sides need the same lookup they get one method each —
`MovementRepository::authorMovement()` for the write, `MovementProvider::authorMovement()` for the query —
and neither delegates to the other, nor depends on it.
There SHALL be no domain service that serves both reads and writes for an aggregate.

`packages/core/src/Domain/<Aggregate>/` SHALL hold the write model alone —
the aggregate, its value objects, its exceptions, and its repository.

### Providers return models, never aggregates

A provider SHALL return `final readonly` DTOs named for the aggregate, in `Model/`
(`Application\Movement\Model\Movement`, not `MovementView` or `MovementDto`),
carrying public promoted properties and no behaviour.
A model SHALL carry only what its readers use.

### `apps/api` reaches handlers only through a bus

Everything in `apps/api` SHALL reach a use case by dispatching —
the read controllers, the write controllers, the read-back after a write,
No class in `apps/api` may inject a provider or a repository.

## Consequences

- The write and read use cases the API supports are both enumerable:
  one command class and one query class per use case under `Core\Application`,
  and `debug:messenger` lists both buses.
- `packages/core` stays framework-free; only `apps/api` knows Messenger exists.
- Dispatching is indirect: a reader following a controller goes controller → bus → handler
  (→ provider, for a query) rather than straight to a service method.
- Both buses stay synchronous, so nothing is queued and nothing retries.
  Moving either to an async transport later would need its own ADR.
- An aggregate is never hydrated just to be rendered.
- Queries and commands duplicate their SQL and their column lists by design.
  A schema change lands in two places per aggregate, and both must be updated.
- `CommandBus::dispatch()` returns `void`, so a controller needs one extra query
  to render the aggregate it just wrote, inside the same `try` as the dispatch.
- Query handlers are thin by construction.
