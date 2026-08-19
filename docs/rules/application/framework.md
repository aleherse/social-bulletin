# Application / Framework

## application-framework-0001: One controller class per route, named for the work not the verb

**WHEN** adding a Symfony controller under `apps/api/src/Controller`, or naming one

**THEN** give each route its own `final` (or `final readonly`) class with a single `__invoke()` carrying the
`#[Route]` attribute — never several public action methods on one class — and take the name from what the controller
does, not from how it is reached:

- a controller that **dispatches a `Core\Application` command** is named for that command's intent, dropping the
  `Command` suffix: `SaveMovementCommand` → `SaveMovementController`, `SubmitMovementCommand` →
  `SubmitMovementController`. The HTTP verb is a routing detail and stays out of the class name — the `#[Route]`
  attribute already carries it, and a controller can answer to more than one verb.
- **every other controller** — reads, and writes with no `Core\Application` command behind them
  (`application-framework-0002`) — is `<HttpVerb><Resource>Controller`: `GetMovementsController`,
  `GetMovementController`, `GetCategoriesController`, `GetMeController`, `PostSessionController`,
  `PostLogoutController`.

One class still serves two routes when both dispatch the same command (`application-commands-0005`: a `null` id creates,
a present id edits) — stack both `#[Route]` attributes on one
`__invoke(Request $request, User $author, ?string $id = null)` rather than duplicating the payload-to-command mapping
across two otherwise identical classes.

**Example:**

| Wrong                                                                                       | Right                                                                                                   |
|---------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|
| `MovementController::create/list/show/update/submit()` in one class                         | `SaveMovementController`, `SubmitMovementController`, `GetMovementsController`, `GetMovementController` |
| `PostMovementSubmitController` for the class dispatching `SubmitMovementCommand`            | `SubmitMovementController`                                                                              |
| `PostMovementController` and `PatchMovementController` duplicating the same payload mapping | one `SaveMovementController` with two stacked `#[Route]` attributes                                     |
| `SessionController::create/me/logout()` in one class                                        | `PostSessionController`, `GetMeController`, `PostLogoutController`                                      |

## application-framework-0002: Payload shape validated on the application command

**WHEN** a non-GET Symfony controller under `apps/api/src/Controller` needs to read fields out of the request payload
and hand them to a `core` write use case

**THEN** parse and validate the payload on the `Core\Application` command the controller dispatches, through a static
`fromPayload(array $payload, ...): self` entry point on the command itself (e.g.
`SaveMovementCommand::fromPayload`) validating each field's shape with `Webmozart\Assert\Assert` (e.g.
`Assert::nullOrString`) — never inline field-pulling in the controller body.

Put the parsing in the command's **private constructor** and keep `fromPayload` as the named wrapper over it. This is
not cosmetic: PHPStan's `property.readOnlyAssignNotInConstructor` rejects assigning `readonly` properties anywhere else,
so lifting the `Assert` calls up into `fromPayload` reopens one error per field (`application-commands-0003`).

Where a use case has no `Core\Application` command — a controller calling a domain service directly, as
`PostSessionController` does with `UserService` — that same factory goes on an `apps/api`-side
`<HttpVerb><Aggregate>[<Action>]Command` instead (`PostSessionCommand`). What is banned is an `apps/api` wrapper that
re-maps the payload when a `Core\Application` command already exists to carry it.

Business rules (blank checks, format, length) stay in the domain aggregate; `fromPayload` only validates shape and
assigns nothing at all for a key the payload never carried (`application-commands-0003`) — it never resolves a use-case
default itself. When one command serves more than one use case (`application-commands-0005`), absent-field defaulting is
the handler's job, since only the handler knows which branch is running: empty for a freshly created aggregate, the
loaded aggregate's current value for an edit.

**Example:**

| Wrong                                                                                                | Right                                                                                                 |
|------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------|
| `PostSessionController` reads `$payload['email']` inline and calls `RequestPayload::stringField()`   | `PostSessionController` calls `PostSessionCommand::fromPayload($payload)` and reads `$command->email` |
| `Assert` calls in `fromPayload`, assigning the command's properties there                            | `Assert` calls in the private constructor; `fromPayload` just returns `new self(…)`                   |
| an `apps/api` request command re-mapping a payload a `Core\Application` command already takes        | `SaveMovementCommand::fromPayload(…)` called straight from the controller                             |
| `$this->commandBus->dispatch(new SaveMovementCommand($id, $author->id, $payload['title'] ?? '', …))` | `$this->commandBus->dispatch(SaveMovementCommand::fromPayload($payload, $author->id, $id))`           |
| absent-field merge (`$payload['title'] ?? $movement->title()`, …) inline in the controller           | resolved in `SaveMovementHandler`, from the aggregate it loads                                        |
