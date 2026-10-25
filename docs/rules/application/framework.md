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

## application-framework-0003: A controller reaches core through Application only

**WHEN** writing a controller under `apps/api/src/Controller`

**THEN** import from `Core\Application` — the command, the query, the model it renders — and nothing from `Domain`.
Naming a domain class puts a business rule in the controller and duplicates one the use case already runs.

**Example:**

| Wrong                                                               | Right                                                              |
|-----------------------------------------------------------------------|----------------------------------------------------------------------|
| `use SocialBulletin\Core\Domain\User\User;` in `SignInController`     | only the `SignInCommand` and `CurrentUserQuery` it dispatches        |
| `$email = User::normaliseEmail($command->email);`, then querying it   | `new CurrentUserQuery($command->email)`, normalised in the query constructor |
| injecting `MovementProvider` into a controller                        | dispatching on `QueryBus` (`application-commands-0002`)              |

## application-framework-0004: Domain failures are translated by one exception listener

**WHEN** a command or query can fail with a `DomainError` (`domain-helpers-0003`) that must become an HTTP response

**THEN** let it propagate out of the controller. `App\EventListener\DomainErrorListener` answers every `DomainError`
with the status code from its `STATUS_CODE` constant — no `try/catch` in a controller, no status map per route. A
failure outside the domain (e.g. `CurrentUserNotFound`) gets its own `kernel.exception` listener in
`apps/api/src/EventListener/`.

**Example:**

| Wrong                                                                             | Right                                                      |
|-----------------------------------------------------------------------------------|------------------------------------------------------------|
| `catch (MovementNotFound)` returning `HTTP_NOT_FOUND` in `ShowMovementController` | no catch; `MovementNotFound::STATUS_CODE` (404) answers it |
| the same `MovementNotDraft` → 409 catch repeated in `Update` and `Submit`         | one listener, one `STATUS_CODE` on the exception           |

## application-framework-0005: Error responses are JSON:API documents

**WHEN** apps/api builds an error response for a client

**THEN** build it with `App\Http\JsonApiErrorResponse::create()` — an `errors` array of objects with `status` and
`title`, served as `application/vnd.api+json`. Report a field problem as one object per field, with the problem in
`detail` and `source.pointer` set to the request field (`/title`). Never hand-roll `JsonResponse(['message' => …])` —
the web client (`readApiError()`) and the Behat steps read this shape.

**Example:**

| Wrong                                        | Right                                                                                            |
|----------------------------------------------|--------------------------------------------------------------------------------------------------|
| `{"message": "…", "errors": {"title": "…"}}` | `{"errors": [{"status": "400", "title": "…", "detail": "…", "source": {"pointer": "/title"}}]}` |
| Behat: `the JSON at "errors.title"`          | `the JSON at "errors[?source.pointer=='/title'].detail \| [0]"`                                  |

## application-framework-0006: Controllers return models; a view listener renders the JSON:API document

**WHEN** a controller under `apps/api/src/Controller` answers with a resource or a list of them

**THEN** return the `Core\Application` model (or `list<Model>`) and mark the controller with
`#[JsonApi(<Name>Transformer::class, '<type>')]` (`status:` when it is not 200). `App\EventListener\JsonApiViewListener`
wraps the result in a `data` document served as `application/vnd.api+json`, and
`App\Http\OpenApi\JsonApiRouteDescriber` documents the same route from the same attribute. The transformer in
`apps/api/src/Http/Transformer/` is the only place that maps a model to the wire shape — `id` stays in its array, and
Fractal lifts it out of `attributes`. A controller that sets a cookie still returns the model and leaves the token on the request
(`SessionCookieListener::TOKEN_ATTRIBUTE`); one that returns no body returns its own `Response`, which the listener
leaves alone. Never build `new JsonResponse([...])` for a resource.

**Example:**

| Wrong                                                                 | Right                                                                   |
|-----------------------------------------------------------------------|-------------------------------------------------------------------------|
| `return new JsonResponse(MovementPresenter::toArray($movement));`     | `#[JsonApi(MovementTransformer::class, 'movements')]` and `return $movement;` |
| `new JsonResponse(['movements' => [...]])`                            | `list<Movement>` return; the document is `{"data": [...]}`              |
| `new JsonResponse($body, Response::HTTP_CREATED)` for a create        | `#[JsonApi(MovementTransformer::class, 'movements', status: Response::HTTP_CREATED)]` |
| Behat: `the JSON at "title"`                                          | `the JSON at "data.attributes.title"`                                   |

## application-framework-0007: A write reads its payload from a JSON:API resource document

**WHEN** a non-GET controller under `apps/api/src/Controller` takes fields from the request body

**THEN** declare `#[JsonApiPayload('<type>', <Command>::class)] array $payload` instead of reading `Request::toArray()`.
`App\Http\JsonApiPayloadResolver` hands the controller the `data.attributes` of a body sent as
`application/vnd.api+json`, and answers `415` for another media type, `400` for malformed JSON or a body that is not a
resource object, and `409` when `data.type` is not `<type>` or `data.id` contradicts the `{id}` in the path. The
controller then passes `$payload` to `<Command>::fromPayload()` (`application-framework-0002`) as before; no controller
unwraps `data` itself. The command named on the attribute is also what documents the request: `JsonApiRouteDescriber`
lists its public properties, bar `id` and `authorId`, as the `attributes` in `apps/docs/openapi.json`.

**Example:**

| Wrong                                                          | Right                                                              |
|----------------------------------------------------------------|--------------------------------------------------------------------|
| `$payload = $request->toArray();` in `CreateMovementController` | `#[JsonApiPayload('movements', CreateMovementCommand::class)] array $payload` |
| `$payload['data']['attributes']['title']` in a controller        | `CreateMovementCommand::fromPayload($payload, $currentUser->id)`   |
| Behat: `{"title": "Save the Bees"}`                              | `{"data": {"type": "movements", "attributes": {"title": "…"}}}`   |

## application-framework-0008: An API change regenerates the OpenAPI document

**WHEN** a route, command, transformer or the session behaviour changes under `apps/api`

**THEN** run `make openapi` and commit `apps/docs/openapi.json`; `make openapi-check` fails the gate otherwise
(ADR-0023). Document the route from its attributes (`application-framework-0006`, `application-framework-0007`),
never with a hand-written schema. Only what attributes cannot express goes in
`apps/api/config/packages/nelmio_api_doc.yaml` — security, `Set-Cookie` headers, non-default statuses, public routes —
and `features/openapi.feature` asserts it.

**Example:**

| Wrong                                                  | Right                                                              |
|--------------------------------------------------------|--------------------------------------------------------------------|
| Edit `apps/docs/openapi.json` by hand                  | `make openapi`                                                     |
| A `components.schemas` entry copying `CreateMovementCommand` | `#[JsonApiPayload('movements', CreateMovementCommand::class)]` |
| `/api/logout` left with the global `cookieAuth` security | `paths./api/logout.post.security: []` and its `204` in `nelmio_api_doc.yaml` |
