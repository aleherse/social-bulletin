# Application / Framework

## application-framework-0001: One controller class per route, named for the work not the verb

**WHEN** adding a Symfony controller under `apps/api/src/Controller`, or naming one

**THEN** give each route its own `final` (or `final readonly`) class with a single `__invoke()` carrying the
`#[Route]` attribute — never several public action methods on one class — and name it for the work it does, never for
the HTTP verb that happens to reach it. The verb is a routing detail the `#[Route]` attribute already carries, and one
controller can answer to more than one verb, so it cannot name the class. This holds for every controller, reads
included — there is no `<HttpVerb><Resource>Controller` fallback.

- a controller that **dispatches a `Core\Application` command** takes that command's intent, dropping the `Command`
  suffix: `CreateMovementCommand` → `CreateMovementController`, `SubmitMovementCommand` → `SubmitMovementController`,
  `SignInCommand` → `SignInController`.
- a **read** takes the intent its route serves — `ListMovementsController` and `ListCategoriesController` for
  collections, `ShowMovementController` for a single one, `CurrentUserController` for `/api/me`. Prefer `List`/`Show`
  over `Get`, which only restates the method.
- a **write with no command behind it** is named the same way, for what it does to the session or the resource:
  `LogoutController`.

One class serves exactly one route. `POST /api/movements` and `PATCH /api/movements/{id}` look like one use case
wearing two verbs, but each has its own command (`application-commands-0005`) and its own response — `201` with no id
to read against `200` with one bound from the path — so they stay two controllers. A single `__invoke` serving both
would branch on an `?string $id = null` parameter to re-make a distinction the router already made.

**Example:**

| Wrong                                                                                         | Right                                                                                                       |
|-----------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------|
| `MovementController::create/list/show/update/submit()` in one class                           | `CreateMovementController`, `UpdateMovementController`, `ListMovementsController`, `ShowMovementController` |
| `PostMovementSubmitController` for the class dispatching `SubmitMovementCommand`              | `SubmitMovementController`                                                                                  |
| one `SaveMovementController` with a `POST` and a `PATCH` `#[Route]` stacked on one `__invoke` | `CreateMovementController` and `UpdateMovementController`, one route each                                   |
| `SessionController::create/me/logout()` in one class                                          | `SignInController`, `CurrentUserController`, `LogoutController`                                             |
| `GetMovementsController`, `GetMeController` — the HTTP method restated as a class name        | `ListMovementsController`, `CurrentUserController`                                                          |

## application-framework-0002: Payload shape validated on the application command

**WHEN** a non-GET Symfony controller under `apps/api/src/Controller` needs to read fields out of the request payload
and hand them to a `core` write use case

**THEN** parse and validate the payload on the `Core\Application` command the controller dispatches, through a static
`fromPayload(array $payload, ...): self` entry point on the command itself (e.g. `UpdateMovementCommand::fromPayload`)
— never inline field-pulling in the controller body. Keep `fromPayload` a named wrapper over a **private constructor**
that does both the assigning and the `Webmozart\Assert\Assert` shape checks (e.g. `Assert::nullOrString`): PHPStan's
`property.readOnlyAssignNotInConstructor` rejects assigning `readonly` properties anywhere else. Every write already
has a command behind it, so an `apps/api` wrapper re-mapping a payload the command could take itself is banned; only a
payload-taking write with no command behind it at all may fall back to an `apps/api`-side
`<HttpVerb><Aggregate>[<Action>]Command`.

Business rules (blank checks, format, length) stay in the domain aggregate, and `fromPayload` never resolves a
**use-case default** — a value standing in for one the caller did not give. Where absence could mean more than one
thing, it must assign nothing at all and leave the property uninitialised (`application-commands-0003`):
`UpdateMovementCommand` leaves every omitted field unassigned, because the value standing in for it is the one the
movement already holds and only `UpdateMovementHandler` has loaded it. Normalising absence to the empty value is
allowed only where the domain cannot tell the two apart — `SignInCommand` assigns `''` for a missing `email`, and
`CreateMovementCommand` does the same for all five of its fields, because absent, `null` and `''` all reach the domain
as the one thing it rejects. The moment the two could diverge, leave the property unassigned instead; splitting one
write into a create and an edit command (`application-commands-0005`) is often what makes that possible.

**Example:**

| Wrong                                                                                                   | Right                                                                                         |
|---------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------|
| `SignInController` reads `$payload['email']` inline and calls `RequestPayload::stringField()`           | `SignInController` dispatches `SignInCommand::fromPayload($payload)`                          |
| `Assert` calls in `fromPayload`, assigning the command's properties there                               | `Assert` calls in the private constructor; `fromPayload` just returns `new self(…)`           |
| an `apps/api` request command re-mapping a payload a `Core\Application` command already takes           | `CreateMovementCommand::fromPayload(…)` called straight from the controller                   |
| `$this->commandBus->dispatch(new UpdateMovementCommand($id, $author->id, $payload['title'] ?? '', …))`  | `$this->commandBus->dispatch(UpdateMovementCommand::fromPayload($payload, $author->id, $id))` |
| absent-field merge (`$payload['title'] ?? $movement->title()`, …) inline in the controller              | resolved in `UpdateMovementHandler`, from the aggregate it loads                              |
| `$this->title = $payload['title'] ?? ''` in `UpdateMovementCommand` — it would silently blank the field | left unassigned; `UpdateMovementHandler` falls back to the movement's current title           |
| `SignInCommand::$email` left unassigned, so every reader must ask `hasProperty('email')` first          | `$this->email = $email ?? ''` — absent, `null` and `''` are all `email.blank`                 |
