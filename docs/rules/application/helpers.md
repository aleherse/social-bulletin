# Application / Helpers

## application-helpers-0001: Cross-aggregate code lives in a Helper folder

**WHEN** adding a class under `core/src/Application/` that no single aggregate owns

**THEN** put it in `core/src/Application/Helper/` — never flat in `Application/`, and never inside an aggregate folder
that does not own it. A helper carries no aggregate vocabulary: the moment it does it belong in that aggregate's folder
instead.

**Example:**

| Wrong                                                               | Right                                                            |
|---------------------------------------------------------------------|------------------------------------------------------------------|
| `core/src/Application/BaseCommand.php` (flat in the layer)   | `core/src/Application/Helper/BaseCommand.php`             |
| `core/src/Application/Movement/Command/BaseCommand.php`      | `core/src/Application/Helper/BaseCommand.php`             |
| a `Helper/` class taking or returning `Movement`                    | a method on the aggregate, or a class in `Application/Movement/` |

## application-helpers-0002: Every application command extends BaseCommand

**WHEN** writing a `Core\Application` command, or asking whether one of its fields was supplied

**THEN** extend `Core\Application\Helper\BaseCommand` and ask through `hasProperty(string $name): bool`,
never `isset()`. The name keeps it distinct from the per-aggregate `Command/` folders it is the base for
(`application-commands-0001`).
Backed by `ReflectionProperty::isInitialized()`, it reports `true` for a field supplied as `null` and `false` only for
one never supplied at all; `isset()` reports `false` for both and so collapses "clear this field" into "leave it alone".

Uninitialised properties trip PHPStan's `property.uninitializedReadonly`. Silence it with an inline
`// @phpstan-ignore` on each property rather than giving up `readonly` or widening anything in
`phpstan.dist.neon`; an ignore that stops matching is itself a build error, so it cannot outlive its property.

**Example:**

| Wrong                                                       | Right                                                        |
|-------------------------------------------------------------|--------------------------------------------------------------|
| `isset($command->location)`                                 | `$command->hasProperty('location')`                          |
| `final readonly class UpdateMovementCommand` standing alone | `… extends BaseCommand`                               |
| dropping `readonly` to satisfy PHPStan                      | keep it; ignore the one identifier instead                   |

## application-helpers-0003: Every application query extends BaseQuery

**WHEN** writing a `Core\Application` query, or asking what dispatching it gives back

**THEN** extend `Core\Application\Helper\BaseQuery` and declare the result through its template parameter
(`application-queries-0003`). The base is generic and otherwise empty: it carries no `hasProperty()`, because an
absent field is a write concern (`application-commands-0003`) — the first query that needs one lifts the method out
of `BaseCommand` rather than copying it.

**Example:**

| Wrong                                                    | Right                                                              |
|----------------------------------------------------------|--------------------------------------------------------------------|
| `final readonly class ShowMovementQuery` standing alone  | `… extends BaseQuery`, `@extends BaseQuery<Movement>` |
| `ShowMovementQuery extends BaseCommand`           | `… extends BaseQuery`                                        |
| `hasProperty()` copied onto `BaseQuery`           | left on `BaseCommand` until a query needs it                 |
