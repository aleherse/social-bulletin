# Application / Framework

**WHEN** a Symfony controller under `apps/api/src/Controller` would handle more than one route
**THEN** split it into one `final` (or `final readonly`) class per route, named
`<HttpVerb><Aggregate>[<Action>]Controller`, with a single `__invoke()` method carrying the
`#[Route]` attribute — not several public action methods on one class.

*Example:*
    | Wrong                                                                          | Right                                                                                                    |
    |---------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|
    | `MovementController::create/list/show/update/submit()` in one class            | `PostMovementController`, `GetMovementsController`, `GetMovementController`, `PatchMovementController`, `PostMovementSubmitController` |
    | `SessionController::create/me/logout()` in one class                            | `PostSessionController`, `GetMeController`, `PostLogoutController`                                       |

---

**WHEN** a non-GET Symfony controller under `apps/api/src/Controller` needs to read fields out of
the request payload
**THEN** wrap that extraction in a dedicated `<HttpVerb><Aggregate>[<Action>]Command` value object —
a `final readonly class` with a private constructor and a static `fromPayload(array $payload): self`
factory that validates each field's shape with `Webmozart\Assert\Assert` (e.g. `Assert::nullOrString`)
— not inline field-pulling in the controller body, and not a shared ad-hoc coercion helper. Business
rules (blank checks, format, length) stay in the domain service; the Command only validates shape.

*Example:*
    | Wrong                                                                    | Right                                                                          |
    |---------------------------------------------------------------------------|----------------------------------------------------------------------------------|
    | `PostSessionController` reads `$payload['email']` inline and calls `RequestPayload::stringField()` | `PostSessionController` calls `PostSessionCommand::fromPayload($payload)` and reads `$command->email` |

---

**WHEN** a controller under `apps/api/src/Controller` must hand payload fields to a `packages/core`
service
**THEN** give its `<HttpVerb><Aggregate>[<Action>]Command` one `to<Intent>()` method per domain
command it feeds, and pass the resulting domain command to the service — never spread payload fields
as separate service arguments, and never compute merge defaults in the controller body. Absent-field
semantics belong in that mapper: POST passes `?? ''` so the domain reports the field error, PATCH
falls back to the current aggregate value (`to<Intent>(<Aggregate> $current)`), using the
`<field>Provided` flag wherever `null` is itself a legal value. The mapping lives in `apps/api`
because App may depend on Core, never the reverse.

*Example:*
    | Wrong                                                                          | Right                                                     |
    |------------------------------------------------------------------------------------|---------------------------------------------------------------|
    | `$this->movementService->create($author->id, $command->title ?? '', $command->description ?? '', …)` | `$this->movementService->create($command->toDraft($author->id))` |
    | PATCH merge (`$command->title ?? $movement->title()`, …) inline in `PatchMovementController::__invoke()` | `$command->toEdit($movement)` on `UpsertMovementCommand`   |
    | `MovementService::update($id, $authorId, string $title, string $description, …)` | `MovementService::update($id, $authorId, EditMovement $command)` |
