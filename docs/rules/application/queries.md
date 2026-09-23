# Application / Queries

## application-queries-0001: Reads go through a provider, writes through a repository

**WHEN** adding a read to `core`, or deciding which class should carry one

**THEN** the SQL belongs on a `<Aggregate>Provider` in `core/src/Application/<Aggregate>/Provider/`, constructed
with `Doctrine\DBAL\Connection` directly and returning models (`application-queries-0002`). Only a query handler
calls it (`application-queries-0003`). A repository keeps `save()` and only the finder that hydrates the aggregate a
handler is about to mutate. Where both sides need the same lookup they get one method each and neither delegates to
the other: they share the table, not the code (ADR-0019, `database-persistence-0002`). `deptrac.yaml` enforces the
split: providers sit in their own `Provider` layer, which may not reach `Repository`.

A provider is declared `class <Aggregate>Provider`, not `final readonly` — Prophecy doubles it in the query-handler
specs, and can double neither a `final` class nor a `readonly` one (`application-style-0001`).

**Example:**

| Wrong                                                                     | Right                                                            |
|---------------------------------------------------------------------------|------------------------------------------------------------------|
| `MovementRepository::byAuthor()` feeding a query handler                  | `MovementProvider::byAuthor()`, returning models                 |
| `MovementProvider` constructed with `MovementRepository`                  | `MovementProvider` constructed with `Connection`                 |
| `MovementService` wrapping the repository for reads and writes alike      | provider for the query, repository for the write                 |
| SQL in `ListMovementsHandler`                                             | SQL in `MovementProvider`, called by the handler                 |

`SubmitMovementHandler` loads through `MovementRepository::authorMovement()` and
`ShowMovementHandler` through `MovementProvider::authorMovement()`: the same ownership scoping,
one returning the aggregate to mutate, the other the model to render.

## application-queries-0002: A provider returns models from the aggregate's Model folder

**WHEN** deciding what a provider hands back

**THEN** return `final readonly` classes named after the aggregate itself — no `View` or `Dto` suffix, since
`core/src/Application/<Aggregate>/Model/` already says what they are — carrying public promoted properties and
nothing else: no methods, no derived values, no validation. A model may hold domain value objects, never an
aggregate, and carries only what its readers use: `Model\Movement` has no `authorId`, because ownership scopes the
query in SQL rather than being re-checked in PHP after the read. A model and its aggregate share a short name and
are told apart by their namespace, so a file imports only the one it needs.

**Example:**

| Wrong                                                           | Right                                                       |
|-----------------------------------------------------------------|-------------------------------------------------------------|
| `MovementProvider::byAuthor(): list<Domain\Movement\Movement>`   | `list<Application\Movement\Model\Movement>`                 |
| `Model\Movement::toArray()`, `Model\Movement::isDraft()`        | plain properties; `MovementPresenter` maps them to JSON     |
| `public UserId $authorId` on the model, compared after the read | `->andWhere('author_id = :author_id')` in the provider      |
| `Application/Movement/Model/MovementView.php`                   | `Application/Movement/Model/Movement.php`                   |

## application-queries-0003: A query declares what handling it returns

**WHEN** adding a read use case to `core`

**THEN** put it in `src/Application/<Aggregate>/Query/` as a `final readonly` query extending
`Core\Application\Helper\BaseQuery`, paired one-to-one with a `final readonly` handler whose single
`__invoke(<Intent>Query $query)` returns models. Both are named for the intent, the way commands are
(`application-commands-0004`). The query declares its result through the base's template parameter —
`@extends BaseQuery<list<Movement>>` — which is what makes `QueryBus::dispatch()` return that type instead of
`mixed` under PHPStan level 8, so no caller needs an `Assert::isInstanceOf()` or an inline `@var`.

The handler only calls providers and hands back what they produce: no SQL (that is the provider's,
`application-queries-0001`) and no rules — a read that must refuse has the provider throw, as
`MovementProvider::authorMovement()` throws `MovementNotFound`.

**Example:**

| Wrong                                                            | Right                                                        |
|------------------------------------------------------------------|--------------------------------------------------------------|
| `GetMovementsQuery`, `MovementsByAuthorQuery`                    | `ListMovementsQuery` / `ListMovementsHandler`                |
| `ListMovementsQueryHandler`                                      | `ListMovementsHandler`                                       |
| `final readonly class ListMovementsQuery` standing alone         | `… extends BaseQuery` with `@extends BaseQuery<list<Movement>>` |
| `$movements = $this->queryBus->dispatch($query); assert(...)`    | the annotated result, used directly                          |
| an ownership check inside `ShowMovementHandler`                  | `MovementProvider::authorMovement()` scoping it in SQL        |
