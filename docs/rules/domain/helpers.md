# Domain / Helpers

## domain-helpers-0001: Cross-aggregate domain code lives in a Helper folder

**WHEN** adding a class or interface under `core/src/Domain/` that no single aggregate owns

**THEN** put it in `core/src/Domain/Helper/` — never flat in `Domain/`, and never inside an aggregate folder that does
not own it. This is the same shape as `core/src/Application/Helper/`
(`application-helpers-0001`), so each layer has exactly one home for what its aggregates share, and a helper carries no
aggregate vocabulary.

Pick the layer by who needs it, not by who happens to call it first. `Core\Application` may depend on `Core\Domain`
and never the reverse (ADR-0017), so a `Domain/Helper/` interface is reachable from a domain service and an application
handler alike — `IdentityGenerator` serves `SignInHandler` and `CreateMovementHandler`. Something only the application
layer could want stays in `Application/Helper/`.

**Example:**

| Wrong                                                       | Right                                                       |
|-------------------------------------------------------------|-------------------------------------------------------------|
| `core/src/Domain/IdentityGenerator.php` (flat in the layer) | `core/src/Domain/Helper/IdentityGenerator.php`              |
| `core/src/Domain/Movement/IdentityGenerator.php`            | `core/src/Domain/Helper/IdentityGenerator.php`              |
| a `Domain/Helper/` interface taking or returning `Movement` | a method on the aggregate, or a class in `Domain/Movement/` |
| `Domain/Helper/` duplicated per aggregate                   | one `Helper/` folder per layer                              |

## domain-helpers-0002: A domain helper is a port, and apps/api supplies the adapter

**WHEN** the domain needs a capability it cannot honestly provide itself — minting an identity, reading the clock,
reaching anything outside the process

**THEN** declare it in `Domain/Helper/` as an **interface** named for the capability (`IdentityGenerator::generate()`),
implement it in `apps/api` (`App\Identity\UuidV7IdentityGenerator`), and alias the two in
`apps/api/config/services.yaml`. That is what keeps `core` framework-free (ADR-0005): `Symfony\Component\Uid`
is the adapter's business, not the domain's. Callers depend on the interface and go through it —
`$this->identities->generate()`, never a `Uuid::v7()` call inline — which is also what lets PHPSpec double the
capability (`SignInHandlerSpec`, `CreateMovementHandlerSpec`) instead of asserting against a random value.

Repositories are the deliberate exception and not a precedent to follow: they are concrete DBAL classes living in their
aggregate's folder, because `core` owns its persistence outright (ADR-0012 — deptrac allows
`CoreDomain → DBAL`). Do not add a `*RepositoryInterface` to `Helper/` to make them match this rule.

**Example:**

| Wrong                                             | Right                                                                |
|---------------------------------------------------|----------------------------------------------------------------------|
| `Uuid::v7()->toRfc4122()` inside `SignInHandler`  | `$this->identities->generate()`                                      |
| `IdentityGenerator` implemented inside `core`     | `App\Identity\UuidV7IdentityGenerator` implementing it in `apps/api` |
| a `Helper/` interface with no adapter aliased     | aliased in `apps/api/config/services.yaml`                           |
| `MovementRepositoryInterface` in `Domain/Helper/` | concrete `MovementRepository` in `Domain/Movement/`                  |
| a concrete `Clock` class in `Domain/Helper/`      | a `Clock` interface there, implemented in `apps/api`                 |
