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
