# ADR-0019: Adopt CQRS Read Providers

- Status: Accepted
- Date: 2026-08-29

## Context

[ADR-0017](ADR-0017-adopt-symfony-messenger-command-bus.md) moved every write
into `Core\Application` as a command and a handler,
and put reads explicitly out of scope:
`MovementService` and `UserService` kept their finders,
each one a thin pass-through to a repository method,
and controllers called them directly.

That leaves one class per aggregate serving both sides.
`MovementRepository` hydrates a `Movement` aggregate for `SubmitMovementHandler`
to mutate, and hydrates the same aggregate for `ListMovementsController`
to render and throw away.
A reader cannot tell which finder guards a write and which one answers a query,
and a query that needs a field from another table
has nowhere to put the join but the write model's own hydration.

The rendering side already ignores the aggregate it is handed.
`MovementPresenter` calls nine accessors on `Movement` and keeps the values;
none of the behaviour that makes `Movement` an aggregate —
`edit()`, `submit()`, `assertDraft()` — is reachable from a `GET`.

## Decision

### Reads go through providers, writes through repositories

Every read that serves a query SHALL go through a **provider**:
a single concrete class per aggregate in
`packages/core/src/Application/<Aggregate>/Provider/`,
named `<Aggregate>Provider`,
constructed with `Doctrine\DBAL\Connection` directly (ADR-0009),
returning models and never aggregates.

Repositories SHALL keep the write side:
`save()`, and the finders that hydrate an aggregate a handler is about to mutate.
A repository finder MUST NOT serve a query.
Where both sides need the same lookup they get one method each —
`MovementRepository::authorMovement()` returns the aggregate a handler mutates,
`MovementProvider::authorMovement()` returns the model a controller renders —
and neither delegates to the other.

Providers and repositories SHALL NOT depend on one another.
They share the table, not the code:
each declares its own columns, and duplicated SQL between the two sides is expected.

### Providers live in `Core\Application`, beside the commands

`packages/core/src/Application/<Aggregate>/` SHALL hold both sides of the use cases,
split into one folder per kind:

```
packages/core/src/Application/Movement/
├── Command/    CreateMovementCommand.php, CreateMovementHandler.php, …
├── Model/      Movement.php, Category.php
└── Provider/   MovementProvider.php, CategoryProvider.php
```

This refines ADR-0017, which put the commands and handlers directly in the aggregate folder:
they move into `Command/`, so a reader sees the write use cases, the read models,
and the readers that produce them as three separate lists rather than one alphabetical pile.

`packages/core/src/Domain/<Aggregate>/` SHALL hold the write model alone —
the aggregate, its value objects, its exceptions, and its repository.

No new Deptrac layer is introduced:
`Application` may already reach `Domain` and `Doctrine\DBAL`,
and `Domain` still may not reach `Application`,
so a provider may name a domain value object
while nothing in the write model can name a provider or a read model.

### DTOs are named for the aggregate, in `Model/`

A provider SHALL return `final readonly` DTOs
named for the aggregate they describe and nothing more —
`Application\Movement\Model\Movement`, `…\Model\Category`, `Application\User\Model\User`.
The folder says what they are, so the class name carries no `View` or `Dto` suffix.
A model and the aggregate it describes therefore share a short name
and are told apart by their namespace;
the one file that would need both (`SignInController`) names only the domain `User`.

A model SHALL carry public promoted properties and no behaviour:
no methods, no derived values, no persistence, and no validation.
It MAY hold domain value objects (`MovementId`, `Area`, `MovementStatus`)
where the reader benefits from the type;
it MUST NOT hold an aggregate.

A model SHALL carry only what its readers use.
`Model\Movement` therefore has no `authorId`:
ownership scopes the query in SQL rather than being re-checked in PHP after the read.

### Domain read services are removed

`MovementService` and `UserService` SHALL be deleted.
Their query methods become provider methods;
`MovementService::authorMovement()`'s ownership check —
until now the guard in front of every write —
moves onto `MovementRepository::authorMovement()`,
which throws the same `MovementNotFound`.

### `apps/api` reads only through providers

Controllers SHALL read through providers and render the models they return.
This includes the read-back after a dispatch (ADR-0017):
`CreateMovementController`, `UpdateMovementController` and `SubmitMovementController`
dispatch the command, then read the movement back through `MovementProvider`,
inside the same `try`.

`AuthorValueResolver` SHALL resolve a `Model\User $author` controller argument
rather than the `User` aggregate,
and `ApiUserProvider` SHALL load its identity through `UserProvider`,
so authentication reads no longer hydrate the write model.

Providers SHALL be registered in `apps/api/config/services.yaml`
by the same kind of resource glob that registers the handlers —
`Application/*/Provider/*Provider.php` beside `Application/*/Command/*Handler.php` —
keeping `packages/core` free of Symfony (ADR-0005).

### Reads stay direct calls

**Superseded by [ADR-0020](ADR-0020-adopt-query-bus.md).**
A read is now dispatched as a query on a second bus,
and the provider is reached by that query's handler rather than by the caller.
What follows still holds for the provider itself:
it reads the same tables, in the same request, through the same connection.

Reads SHALL remain direct method calls on a provider.
No query bus, no query objects, and no separate read database:
the providers read the same tables the repositories write,
in the same request, through the same connection.

## Consequences

- The read side and the write side are separately readable and separately changeable.
  A query that needs a join or a projection changes a provider
  without touching the aggregate.
- `Movement` is no longer hydrated to be rendered,
  so `restore()` exists only for the write path.
- Reads and writes duplicate their SQL and their column lists by design.
  A schema change lands in two places per aggregate, and both must be updated.
- Provider coverage is Behat, not PHPSpec:
  a provider is a DBAL class with no logic to double,
  exactly as the repositories already are (ADR-0015).
  `MovementServiceSpec` and `UserServiceSpec` go;
  the handler specs double `MovementRepository::authorMovement()` instead of
  wrapping a real service.
- `CategoryRepository` becomes `CategoryProvider`:
  categories are a read-only lookup, and nothing writes them from the application.
  Its unused `exists()` goes with it — `database-dbal-0002` already rejects
  pre-validating a foreign key.
- ADR-0017's "Reads are out of scope" section is superseded by this decision.
  Its command-bus decisions stand unchanged.
- A future read model that no longer matches the write tables —
  a denormalised projection, a separate connection — would need its own ADR,
  but it would change what a provider reads, not who calls it.
