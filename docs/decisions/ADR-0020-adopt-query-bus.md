# ADR-0020: Adopt A Query Bus

- Status: Accepted
- Date: 2026-08-29

## Context

[ADR-0019](ADR-0019-adopt-cqrs-read-providers.md) split reads onto providers
but kept them as direct method calls,
inheriting that from [ADR-0017](ADR-0017-adopt-symfony-messenger-command-bus.md),
which put reads out of scope entirely.

The two sides are therefore reached in two different ways.
A write is a named class a reader can enumerate —
`CreateMovementCommand`, `SubmitMovementCommand` — dispatched through one entry point.
A read is a method signature on a provider,
discoverable only by opening the provider,
and every controller couples straight to the class that runs the SQL.

`debug:messenger` lists the write use cases the API supports.
The read use cases appear nowhere.

## Decision

### A second bus, symmetrical with the first

`framework.messenger.buses` SHALL declare `query.bus` beside `command.bus`.
`command.bus` stays the default bus.

`query.bus` SHALL have no transport:
queries are handled synchronously in the request that dispatched them.

### `apps/api` reads only through `QueryBus`

`App\Messenger\QueryBus` SHALL wrap `query.bus` with `HandleTrait`,
exactly as `App\Messenger\CommandBus` wraps `command.bus`,
with two differences:
it takes an `BaseQuery` rather than any object,
and it **returns** the handler's result rather than discarding it —
a query that reported nothing would have no purpose.
It SHALL rethrow the exception nested inside Messenger's `HandlerFailedException`,
so a controller still catches `MovementNotFound` and maps it to 404.

Everything in `apps/api` that reads SHALL dispatch a query:
the read controllers, the read-back after a write (ADR-0017),
`SignInController`, and the two security adapters
(`ApiUserProvider`, `AuthorValueResolver`).
No class in `apps/api` may inject a provider,
and no Behat context may call one:
`MovementContext` dispatches `CurrentUserQuery` the way it already dispatches `SignInCommand`.

### Queries live in `Application/<Aggregate>/Query/`

`packages/core/src/Application/<Aggregate>/Query/` SHALL hold the read use cases,
one query plus one handler each,
named for the intent the way commands are (`application-commands-0004`):
`ListMovementsQuery` and `ListMovementsHandler`, `ShowMovementQuery` and `ShowMovementHandler`,
`ListCategoriesQuery`, `CurrentUserQuery`.

A query handler SHALL only call providers and return what they produce.
It holds no SQL — that stays in the provider (ADR-0019) —
and no rules: a read that must refuse has the provider throw,
as `MovementProvider::authorMovement()` already does.

Handlers SHALL NOT carry `#[AsMessageHandler]`.
They are registered from `apps/api/config/services.yaml`
by an `Application/*/Query/*Handler.php` glob tagged on `query.bus`,
which keeps `packages/core` free of Symfony (ADR-0005).

### `BaseQuery` carries the result type

`Core\Application\Helper\BaseQuery` SHALL be the base every query extends,
beside `BaseCommand` (`application-helpers-0002`).

It SHALL be generic over what the query returns —
`@template-covariant TResult`, with each query declaring
`@extends BaseQuery<list<Model\Movement>>` and the like —
so `QueryBus::dispatch()` is annotated `@return TResult`
and a controller gets a typed result under PHPStan level 8
without an `Assert::isInstanceOf()` or an inline `@var` at every call site.

The base carries no `hasProperty()`.
Absent fields are a write concern (`application-commands-0003`);
the first query that needs one lifts the method out of `BaseCommand` rather than copying it.

### Providers stop being `final readonly`

The provider classes SHALL be declared `class <Aggregate>Provider`,
keeping `readonly` on the promoted `Connection` alone,
for the same reason the repositories are shaped that way:
Prophecy can double neither a `final` class nor a `readonly` one,
and the query-handler specs double the providers.

## Consequences

- The read use cases are enumerable, like the writes:
  one query class each, and `debug:messenger` lists both buses.
- Every read costs one more hop — controller → bus → handler → provider —
  and one more class pair per use case.
  A read that used to be a one-line provider call is now four files.
- Query handlers are thin by construction,
  so their PHPSpec examples assert delegation rather than behaviour.
  The behaviour they delegate to stays covered by Behat, which exercises the SQL (ADR-0015).
- ADR-0019's "Reads stay direct calls" section is superseded,
  as is what ADR-0017 said about reads.
  The rest of both decisions stands: providers still own the SQL,
  repositories still own the write side.
- A query that needs to fan out across aggregates now has a place to do it —
  the handler — without a provider learning about another aggregate.
