# Application / Framework

## application-framework-0001: One controller class per route, named for the work not the verb

**WHEN** adding a Symfony controller under `apps/api/src/Controller`, or naming one

**THEN** give each route its own `final` (or `final readonly`) class with a single `__invoke()` carrying the
`#[Route]` attribute — never several public action methods on one class — and name it for the work it does, never for
the HTTP verb that happens to reach it. The verb is a routing detail the `#[Route]` attribute already carries, and one
controller can answer to more than one verb, so it cannot name the class. This holds for every controller, reads
included — there is no `<HttpVerb><Resource>Controller` fallback.

- a controller that **dispatches a `Core\Application` command** takes that command's intent, dropping the `Command`
  suffix: `SaveMovementCommand` → `SaveMovementController`, `SubmitMovementCommand` → `SubmitMovementController`,
  `SignInCommand` → `SignInController`.
- a **read** takes the intent its route serves — `ListMovementsController` and `ListCategoriesController` for
  collections, `ShowMovementController` for a single one, `CurrentUserController` for `/api/me`. Prefer `List`/`Show`
  over `Get`, which only restates the method.
- a **write with no command behind it** is named the same way, for what it does to the session or the resource:
  `LogoutController`.

One class still serves two routes when both dispatch the same command (`application-commands-0005`: a `null` id creates,
a present id edits) — stack both `#[Route]` attributes on one
`__invoke(Request $request, User $author, ?string $id = null)` rather than duplicating the payload-to-command mapping
across two otherwise identical classes.

**Example:**

| Wrong                                                                                       | Right                                                                                                     |
|---------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|
| `MovementController::create/list/show/update/submit()` in one class                         | `SaveMovementController`, `SubmitMovementController`, `ListMovementsController`, `ShowMovementController` |
| `PostMovementSubmitController` for the class dispatching `SubmitMovementCommand`            | `SubmitMovementController`                                                                                |
| `PostMovementController` and `PatchMovementController` duplicating the same payload mapping | one `SaveMovementController` with two stacked `#[Route]` attributes                                       |
| `SessionController::create/me/logout()` in one class                                        | `SignInController`, `CurrentUserController`, `LogoutController`                                           |
| `GetMovementsController`, `GetMeController` — the HTTP method restated as a class name      | `ListMovementsController`, `CurrentUserController`                                                        |

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

Every write already has a `Core\Application` command behind it, so that is where the factory belongs — an `apps/api`
wrapper re-mapping a payload the command could take itself is banned. `SignInController` used to hold one
(`App\Controller\User\PostSessionCommand`) while sign-in was a direct `UserService` call; it went the moment the write
became `SignInCommand`. Only a payload-taking write with no command behind it at all may fall back to an
`apps/api`-side `<HttpVerb><Aggregate>[<Action>]Command`.

Business rules (blank checks, format, length) stay in the domain aggregate, and `fromPayload` never resolves a
**use-case default** — a value standing in for one the caller did not give. Where absence could mean more than one
thing, it must therefore assign nothing at all and leave the property uninitialised (`application-commands-0003`): when
one command serves more than one use case (`application-commands-0005`), absent-field defaulting is the handler's job,
since only the handler knows which branch is running — empty for a freshly created aggregate, the loaded aggregate's
current value for an edit.

A field the use case always requires is the narrower case, and there `fromPayload` may normalise absence to the empty
value: `SignInCommand` assigns `''` for a missing `email`, because absent, `null` and `''` all mean the same thing and
the domain rejects all three as `email.blank`. That is normalisation, not a default — nothing is being stood in for, and
`hasProperty()` would have no second state to report. Reach for it only when the empty value is genuinely
indistinguishable from absence; the moment the two could diverge, leave the property unassigned instead.

**Example:**

| Wrong                                                                                                      | Right                                                                                       |
|------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------|
| `SignInController` reads `$payload['email']` inline and calls `RequestPayload::stringField()`              | `SignInController` dispatches `SignInCommand::fromPayload($payload)`                        |
| `Assert` calls in `fromPayload`, assigning the command's properties there                                  | `Assert` calls in the private constructor; `fromPayload` just returns `new self(…)`         |
| an `apps/api` request command re-mapping a payload a `Core\Application` command already takes              | `SaveMovementCommand::fromPayload(…)` called straight from the controller                   |
| `$this->commandBus->dispatch(new SaveMovementCommand($id, $author->id, $payload['title'] ?? '', …))`       | `$this->commandBus->dispatch(SaveMovementCommand::fromPayload($payload, $author->id, $id))` |
| absent-field merge (`$payload['title'] ?? $movement->title()`, …) inline in the controller                 | resolved in `SaveMovementHandler`, from the aggregate it loads                              |
| `$this->title = $payload['title'] ?? ''` in `SaveMovementCommand` — an edit would silently blank the field | left unassigned; `SaveMovementHandler` picks the fallback for the branch it is running      |
| `SignInCommand::$email` left unassigned, so every reader must ask `hasProperty('email')` first             | `$this->email = $email ?? ''` — absent, `null` and `''` are all `email.blank`               |
