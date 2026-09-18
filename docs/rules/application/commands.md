# Application / Commands

## application-commands-0001: One command and one handler per write use case

**WHEN** adding a write operation to `core`

**THEN** put it in `src/Application/<Aggregate>/Command/` as a `final readonly` command extending
`BaseCommand` (`application-helpers-0002`), paired one-to-one with a `final readonly` handler whose single
`__invoke(<Intent>Command $command): void` returns nothing — a write use case reports failure by throwing, and a
caller that needs the affected aggregate reads it back (`application-commands-0002`). The command carries the whole
use-case input as public properties — the aggregate id and the acting author's id included, so `CreateMovementCommand`
mints its own `MovementId` rather than leaving the handler to generate one the caller could never learn — and nothing
else: no behaviour beyond the inherited `hasProperty()`, no framework attributes. Promote a field that is always
supplied; declare one the caller may omit plain and assign it conditionally (`application-commands-0003`). The handler
only orchestrates — load, mutate, save — with the rules staying in the aggregate.

**Example:**

| Wrong                                                | Right                                                          |
|------------------------------------------------------|----------------------------------------------------------------|
| a service method carrying a write (`update($id, …)`)  | `UpdateMovementHandler::__invoke(UpdateMovementCommand $c)`    |
| `__invoke(…): Movement`, returning the saved aggregate | `__invoke(…): void`, leaving the caller to read it back       |
| `MovementId::generate()` inside `CreateMovementHandler` | `$command->id`, minted by `CreateMovementCommand`            |
| `Application/Movement/CreateMovementCommand.php`     | `Application/Movement/Command/CreateMovementCommand.php`       |
| `#[AsMessageHandler]` on `UpdateMovementHandler`     | tagged in `apps/api/config/services.yaml`                      |
| validation inside `SubmitMovementHandler`            | `Movement::submit()` throwing `InvalidMovement`                |
| id passed to the handler beside the command          | `UpdateMovementCommand::fromPayload($payload, $authorId, $id)` |

## application-commands-0002: Writes and reads each dispatch on their own bus

**WHEN** a controller under `apps/api/src/Controller` — or a Behat `Given` step — needs a `core` write or read

**THEN** dispatch the command through `App\Messenger\CommandBus`, which returns nothing, and read the affected
movement back by dispatching `ShowMovementQuery` through `App\Messenger\QueryBus` — under the id the caller holds,
`$command->id` for a create. A read dispatches the same way and gets the handler's result back
(`application-queries-0003`). Both buses are synchronous and unwrap Messenger's `HandlerFailedException`, so the
`DomainError` the domain threw is still what you catch and map to a status code (`domain-helpers-0003`), and the
read-back belongs inside the same `try`.
Nothing in `apps/api` injects a provider or a repository — the bus is the only way in (ADR-0020).

**Example:**

| Wrong                                                | Right                                                                           |
|------------------------------------------------------|---------------------------------------------------------------------------------|
| `$this->movements->submit($id, $author->id)`         | `$this->commandBus->dispatch(new SubmitMovementCommand($id, $author->id))`      |
| `$movement = $this->commandBus->dispatch($command)`  | `dispatch($command)`, then `$this->queryBus->dispatch(new ShowMovementQuery(…))` |
| injecting `MessageBusInterface` into a controller    | injecting `App\Messenger\CommandBus` or `App\Messenger\QueryBus`              |
| injecting `MovementProvider` into a controller       | `$this->queryBus->dispatch(new ListMovementsQuery($author->id))`                |
| a query dispatched on `command.bus`                  | `query.bus`, where the query handlers are tagged                                |

## application-commands-0003: An absent field is uninitialised, not null

**WHEN** a `Core\Application` command carries a field the caller may legitimately omit, and `null` is itself a
meaningful value for that field (clearing a movement's location, say)

**THEN** leave the property **unassigned** when the input omitted it, rather than storing `null` or pairing it with a
`<field>Provided` boolean: declare it as a plain (non-promoted) property on the `final readonly class`, assign it
conditionally in the constructor, and read it in the handler through `hasProperty('<field>')`, touching the aggregate
only when that returns `true`. `hasProperty()` and the PHPStan ignore it requires belong to the `BaseCommand` base
(`application-helpers-0002`).

**Example:**

| Wrong                                                                | Right                                                                     |
|----------------------------------------------------------------------|---------------------------------------------------------------------------|
| `public bool $locationProvided` alongside `public ?string $location` | `public ?string $location` left unassigned when the key is absent         |
| `$command->locationProvided ? $command->location : $m->location()`   | `$command->hasProperty('location') ? $command->location : $m->location()` |
| `$command->title ?? $movement->title()` (conflates absent and null)  | `$command->hasProperty('title') ? $command->title : $movement->title()`   |

## application-commands-0004: Commands and handlers are named for the intent

**WHEN** naming a `Core\Application` command or the handler that consumes it

**THEN** name both for the use case's intent, never for the HTTP verb or the persistence operation that happens to sit
under it. The command takes a `Command` suffix and lives in a file of the same name; the handler repeats that same
intent followed by `Handler`, without carrying `Command` twice over — `CreateMovementHandler`, never
`CreateMovementCommandHandler`. A controller dispatching the command takes the same intent again
(`application-framework-0001`).

**Example:**

| Wrong                                                | Right                                                 |
|------------------------------------------------------|-------------------------------------------------------|
| `CreateMovement` / `CreateMovement.php`              | `CreateMovementCommand` / `CreateMovementCommand.php` |
| `CreateMovementCommandHandler`                       | `CreateMovementHandler`                               |
| `UpsertMovementCommand`, `SaveOrEditMovementCommand` | `CreateMovementCommand`, `UpdateMovementCommand`      |
| `PostMovementSubmitController`                       | `SubmitMovementController`                            |
