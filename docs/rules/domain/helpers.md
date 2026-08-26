# Domain / Helpers

## domain-helpers-0001: Cross-aggregate domain code lives in a Helper folder

**WHEN** adding a class or interface under `core/src/Domain/` that no single aggregate owns

**THEN** put it in `core/src/Domain/Helper/` — never flat in `Domain/`, and never inside an aggregate folder that
doesn't own it. Same shape as `core/src/Application/Helper/` (`application-helpers-0001`): one home per layer for
what its aggregates share, carrying no aggregate vocabulary of its own.

**Example:**

| Wrong                                                                  | Right                                                        |
|------------------------------------------------------------------------|---------------------------------------------------------------|
| `core/src/Domain/AggregateId.php` or `Domain/Movement/AggregateId.php` | `core/src/Domain/Helper/AggregateId.php`                     |
| a `Domain/Helper/` interface taking or returning `Movement`            | a method on the aggregate, or a class in `Domain/Movement/`  |
| `Domain/Helper/` duplicated per aggregate                              | one `Helper/` folder per layer                               |

## domain-helpers-0002: A domain helper is a port, and apps/api supplies the adapter

**WHEN** the domain needs a capability it cannot honestly provide itself — reaching outside the process, a real
runtime/environment dependency (e.g. reading the system clock), or anything `apps/api`-specific

**THEN** declare it in `Domain/Helper/` as an **interface** named for the capability, implement it in `apps/api`,
and alias the two in `apps/api/config/services.yaml` — that's what keeps `core` framework-free (ADR-0005).

Exception: a small, dependency-free Symfony component `deptrac.yaml` already permits inside `Domain` on its own
layer (e.g. `SymfonyUid`) can be called directly

Repositories are a deliberate exception too, not a precedent for this rule: concrete DBAL classes in their
aggregate's folder, because `core` owns its persistence outright (ADR-0012 — deptrac allows `CoreDomain → DBAL`).
Don't add a `*RepositoryInterface` to `Helper/` to match this pattern.

**Example:**

| Wrong                                                             | Right                                                                |
|---------------------------------------------------------------------|------------------------------------------------------------------------|
| an `IdentityGenerator` port + adapter just to call `Uuid::v7()`     | `AggregateId::generate()` calling `Uuid::v7()` directly                |
| a `Helper/` interface with no adapter aliased                       | aliased in `apps/api/config/services.yaml`                             |
| `MovementRepositoryInterface` in `Domain/Helper/`                    | concrete `MovementRepository` in `Domain/Movement/`                    |
| a concrete `Clock` class in `Domain/Helper/`                         | a `Clock` interface there, implemented in `apps/api`                   |
