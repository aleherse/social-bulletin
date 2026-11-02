# ADR-0021: Adopt CQRS and Domain Events

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

Writes also produce facts other parts of the system may care about.
The aggregate that causes a fact should announce it,
without knowing who listens and without announcing a fact that was never stored.

This ADR adopts CQRS — Command Query Responsibility Segregation —
with one bus per side, plus a third bus for the domain events a write produces.

## Decision

### Messenger owns three synchronous buses

| Bus           | Carries                  | Direction                       | Handlers    |
|---------------|--------------------------|---------------------------------|-------------|
| `command.bus` | commands (default bus)   | `apps/api` → `packages/core`    | exactly one |
| `query.bus`   | queries                  | `apps/api` → `packages/core`    | exactly one |
| `event.bus`   | domain events            | `packages/core` → its listeners | zero or more |

No bus has a transport: every message is handled in the request that dispatched it.
`event.bus` allows messages with no handler, so an event may be published before anything listens.

`App\Messenger\CommandBus` and `App\Messenger\QueryBus` SHALL be the only way `apps/api` reaches a handler.
Each is a `HandleTrait` wrapper that rethrows the exception nested inside Messenger's `HandlerFailedException`,
so controllers keep catching domain exceptions and mapping them to HTTP codes.
`CommandBus::dispatch()` returns nothing; `QueryBus::dispatch()` returns the handler's result.
Everything in `apps/api` reaches a use case through them — the controllers, and the read-back after a write —
and no class there may inject a provider or a repository.

### Commands and queries are framework-free

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

A command handler SHALL return `void`: a write reports failure by throwing,
and a caller that needs the aggregate reads it back through a query.
So the command carries the aggregate's identity — `CreateMovementCommand` mints its own `MovementId`.

A query handler SHALL only call providers and return what they produce;
a query that must refuse has the provider throw.

No handler carries `#[AsMessageHandler]`: `apps/api/config/services.yaml` registers each kind
by a glob tagged on its own bus, which keeps `packages/core` free of Symfony (ADR-0005).

### Queries go through providers, commands through repositories

Every query SHALL go through a **provider**: one concrete class per aggregate in
`packages/core/src/Application/<Aggregate>/Provider/`, constructed with `Doctrine\DBAL\Connection` (ADR-0009).
It returns `final readonly` models from `Model/`, named for the aggregate and never aggregates,
with public promoted properties, no behaviour, and only what their readers use.

Repositories SHALL keep the write side: `save()`, and the finders that hydrate an aggregate a handler will mutate.
Where both sides need the same lookup they get one method each
(`MovementRepository::authorMovement()`, `MovementProvider::authorMovement()`), and neither delegates to the other.
There SHALL be no domain service serving both reads and writes for an aggregate.

`packages/core/src/Domain/<Aggregate>/` holds the write model alone:
the aggregate, its value objects, events and exceptions, and its repository.

### Aggregates record events, repositories publish them

An aggregate SHALL record the event of each fact it causes, when it occurs, using `Domain\Helper\RecordsEvents`:
`Movement::draft()` records `MovementHasBeenCreated`, while `restore()` records nothing.
An event is a `final readonly` class implementing `Domain\Helper\DomainEvent`,
named in the past tense, kept in `Domain/<Aggregate>/Events/`, and carrying ids rather than aggregates.

`Repository::save()` SHALL publish the recorded events as its last statement,
through the `Domain\Helper\EventDispatcher` port:

```php
$this->connection->executeStatement(/* INSERT … ON CONFLICT … */);
$this->events->dispatch(...$movement->pullEvents());
```

A fact is therefore stored before it is published, a failed write publishes nothing,
and handlers and controllers never dispatch, so no save path can forget to.

`App\Messenger\EventBus` implements the port: it puts each event on `event.bus`
and rethrows the nested exception like the other buses.
Event handlers live in `Application/<Aggregate>/Event/`
and are tagged on `event.bus` by a glob added with the first one.

### Publishing is not atomic with the write

There is no transaction around `save()` and no outbox.
A listener that throws fails the request after the fact was stored,
and a crash between the write and the dispatch loses the event.
Publishing inside a transaction was rejected: a failing listener would roll back the fact
and listeners could read uncommitted data.

The upgrade path is a **transactional outbox**: `save()` writes the aggregate and one row per event
in a single transaction, then publishes after the commit.
Delivery becomes at-least-once, so listeners must be idempotent.
Only `save()` persists and publishes, so adopting it changes one method per repository.
It becomes necessary when a listener has a side effect outside the database
or an event moves to an asynchronous transport.

## Consequences

- Every use case is enumerable: one command or query class per use case under `Core\Application`,
  one event class per fact under `Core\Domain`, and `debug:messenger` lists the three buses.
- `packages/core` stays framework-free; only `apps/api` knows Messenger exists.
- Dispatching is indirect: a reader follows controller → bus → handler (→ provider, for a query).
- All buses stay synchronous, so nothing is queued and nothing retries.
  Moving any of them to an async transport would need its own ADR.
- Listeners run inside the request: a slow or failing one slows or fails the response, but never un-stores the write.
- An aggregate is never hydrated just to be rendered.
- Queries and commands duplicate their SQL and column lists by design:
  a schema change lands in two places per aggregate.
- `CommandBus::dispatch()` returns `void`, so a controller needs one extra query to render what it just wrote.
- A new event is one class plus one `recordThat()` call; the repository already publishes whatever was recorded.
