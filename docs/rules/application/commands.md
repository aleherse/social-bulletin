# Application / Commands

## application-commands-0001: One command and one handler per write use case

**WHEN** adding a write operation to `core`

**THEN** put it in `src/Application/<Aggregate>/` as a `final readonly` command extending the `Command` base
(`application-helpers-0002`), paired one-to-one with a `final readonly` handler exposing a single
`__invoke(<Intent>Command $command): <Aggregate>` that returns the saved aggregate. The command carries the whole
use-case input as public properties — including the aggregate id and the acting author's id — promoted where the field
is always supplied, plain and conditionally assigned where the caller may omit it (`application-commands-0003`); beyond
the inherited `hasProperty()` it holds no behaviour and no framework attributes. The handler only orchestrates —
load-or-generate-id, mutate, save — with the rules staying in the aggregate (`domain-common-0001`).

**Example:**

| Wrong                                                | Right                                                        |
|------------------------------------------------------|--------------------------------------------------------------|
| `MovementService::update($id, $authorId, $title, …)` | `SaveMovementHandler::__invoke(SaveMovementCommand $c)`      |
| a handler returning `void`                           | `__invoke(…): Movement`, returning the saved aggregate       |
| `#[AsMessageHandler]` on `SaveMovementHandler`       | tagged in `apps/api/config/services.yaml`                    |
| validation inside `SubmitMovementHandler`            | `Movement::submit()` throwing `InvalidMovement`              |
| id passed to the handler beside the command          | `SaveMovementCommand::fromPayload($payload, $authorId, $id)` |

## application-commands-0002: Writes dispatch, reads call directly

**WHEN** a controller under `apps/api/src/Controller` — or a Behat `Given` step — needs a `core` write

**THEN** dispatch the command through `App\Messenger\CommandBus` and use its return value as the saved aggregate; the
bus is synchronous and unwraps Messenger's `HandlerFailedException`, so the domain exception is still what you catch and
map to a status code. Reads stay a direct call on the domain service — no query bus, no query objects (ADR-0017).

**Example:**

| Wrong                                                | Right                                                                      |
|------------------------------------------------------|----------------------------------------------------------------------------|
| `$this->movementService->submit($id, $author->id)`   | `$this->commandBus->dispatch(new SubmitMovementCommand($id, $author->id))` |
| injecting `MessageBusInterface` into a controller    | injecting `App\Messenger\CommandBus`                                       |
| `$this->commandBus->dispatch(new GetMovements(...))` | `$this->movementService->byAuthor($author->id)`                            |

## application-commands-0003: An absent field is uninitialised, not null

**WHEN** a `Core\Application` command carries a field the caller may legitimately omit, and `null` is itself a
meaningful value for that field (clearing a movement's location, say)

**THEN** leave the property **unassigned** when the input omitted it, rather than storing `null` or pairing it with a
`<field>Provided` boolean. Declare such fields as plain (non-promoted) properties on the `final readonly class` and
assign them conditionally in the constructor; the handler then reads them through `hasProperty('<field>')` and only
touches the aggregate when it returns `true`.

`hasProperty()` and the PHPStan exception it requires belong to the `Command` base — see
`application-helpers-0002` for how it tells the two states apart and why `isset()` cannot.

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
intent followed by `Handler` and does **not** carry `Command` twice over — `SaveMovementHandler`, never
`SaveMovementCommandHandler`. A controller dispatching the command takes the same intent again
(`application-framework-0001`).

**Example:**

| Wrong                                                    | Right                                             |
|----------------------------------------------------------|---------------------------------------------------|
| `SaveMovement` / `SaveMovement.php`                      | `SaveMovementCommand` / `SaveMovementCommand.php` |
| `SaveMovementCommandHandler`                             | `SaveMovementHandler`                             |
| `UpsertMovementCommand`, `CreateOrUpdateMovementCommand` | `SaveMovementCommand`                             |
| `PostMovementSubmitController`                           | `SubmitMovementController`                        |

## application-commands-0005: Create and edit share one command when the fields match

**WHEN** an aggregate needs both a create and an edit write, and the two take the same fields

**THEN** collapse them into one command whose aggregate id is nullable, and let the handler branch on it: a `null` id
creates (generate an id, call the aggregate's named constructor), a present id edits (load, mutate, save) — see
`SaveMovementHandler`. The controller never picks the branch; it passes the id it has, or `null`. What an absent field
falls back to differs between the two branches and so stays the handler's call (`application-framework-0002`).

Keep them as separate commands as soon as the use cases diverge in more than the id — a different field set, different
authorisation, a guarded transition — rather than forcing every write through one branching command.
`SubmitMovementCommand` stays its own command for exactly that reason: it guards `draft → proposed`.

**Example:**

| Wrong                                                                  | Right                                              |
|------------------------------------------------------------------------|----------------------------------------------------|
| `DraftMovementCommand` + `EditMovementCommand` taking identical fields | one `SaveMovementCommand` with `?string $id`       |
| the controller choosing create vs edit before dispatching              | `SaveMovementHandler` branching on `$command->id`  |
| folding `SubmitMovementCommand` in as a `bool $submit` flag            | a separate command — it guards a status transition |
