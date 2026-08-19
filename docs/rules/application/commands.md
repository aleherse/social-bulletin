# Application / Commands

## application-commands-0001: One command and one handler per write use case

**WHEN** adding a write operation to `core`

**THEN** put it in `src/Application/<Aggregate>/` as a `final readonly` command extending the `Command` base
(`application-helpers-0002`), paired one-to-one with a `final readonly` handler exposing a single
`__invoke(<Intent>Command $command): <Aggregate>` that returns the saved aggregate. The command carries the whole
use-case input as public properties, the aggregate id and the acting author's id included where the use case has them —
`CreateMovementCommand` carries the author's id but no aggregate id, because the handler mints it; `SignInCommand`
carries neither, because signing in is what registers the user it identifies. A field is promoted where it is always
supplied, and plain and conditionally assigned where the caller may omit it (`application-commands-0003`); beyond the
inherited `hasProperty()` the command holds no behaviour and no framework attributes. The handler only orchestrates —
load, mutate, save — with the rules staying in the aggregate (`domain-common-0001`).

**Example:**

| Wrong                                                | Right                                                          |
|------------------------------------------------------|----------------------------------------------------------------|
| `MovementService::update($id, $authorId, $title, …)` | `UpdateMovementHandler::__invoke(UpdateMovementCommand $c)`    |
| a handler returning `void`                           | `__invoke(…): Movement`, returning the saved aggregate         |
| `#[AsMessageHandler]` on `UpdateMovementHandler`     | tagged in `apps/api/config/services.yaml`                      |
| validation inside `SubmitMovementHandler`            | `Movement::submit()` throwing `InvalidMovement`                |
| id passed to the handler beside the command          | `UpdateMovementCommand::fromPayload($payload, $authorId, $id)` |

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
intent followed by `Handler` and does **not** carry `Command` twice over — `CreateMovementHandler`, never
`CreateMovementCommandHandler`. A controller dispatching the command takes the same intent again
(`application-framework-0001`).

**Example:**

| Wrong                                                | Right                                                 |
|------------------------------------------------------|-------------------------------------------------------|
| `CreateMovement` / `CreateMovement.php`              | `CreateMovementCommand` / `CreateMovementCommand.php` |
| `CreateMovementCommandHandler`                       | `CreateMovementHandler`                               |
| `UpsertMovementCommand`, `SaveOrEditMovementCommand` | `CreateMovementCommand`, `UpdateMovementCommand`      |
| `PostMovementSubmitController`                       | `SubmitMovementController`                            |

## application-commands-0005: Create and edit are separate commands

**WHEN** an aggregate needs both a create and an edit write, even where the two take the same fields

**THEN** give each its own command and handler — `CreateMovementCommand` / `CreateMovementHandler` and
`UpdateMovementCommand` / `UpdateMovementHandler` — rather than one command with a nullable id and a handler branching
on it. The two use cases only look alike from the payload's side. They differ in everything that follows it: an id
minted against one bound from the path, a named constructor against a load-mutate-save, `MovementNotFound` and
`MovementNotDraft` reachable from only one of them, and — the reason the fields cannot be shared either — a different
answer to what an omitted field means. Creating has nothing to preserve, so an absent field is the empty value the
domain rejects (`application-framework-0002`); editing must leave the movement's current value standing, so an absent
field stays unassigned and the handler reads it through `hasProperty()` (`application-commands-0003`). One command
serving both has to leave *every* field unassigned to keep the stricter of the two contracts, pushing a defaulting
decision into the handler that neither branch actually shares.

The duplicated `fromPayload` parsing is the price, and it is the smaller one: each copy states its own use case's rule
about absence instead of deferring it. Two commands also let each handler take only what it needs —
`CreateMovementHandler` has no `MovementService`, `UpdateMovementHandler` no `IdentityGenerator`.

`SubmitMovementCommand` stays separate for the same reason it always did: it guards `draft → proposed`.

**Example:**

| Wrong                                                                    | Right                                                     |
|--------------------------------------------------------------------------|-----------------------------------------------------------|
| one `SaveMovementCommand` with `?string $id`, the handler branching      | `CreateMovementCommand` and `UpdateMovementCommand`       |
| `CreateMovementCommand` leaving omitted fields unassigned "for symmetry" | `?? ''` — a new movement has no current value to preserve |
| folding `SubmitMovementCommand` in as a `bool $submit` flag              | a separate command — it guards a status transition        |
