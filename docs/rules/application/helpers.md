# Application / Helpers

## application-helpers-0001: Cross-aggregate code lives in a Helper folder

**WHEN** adding a class under `core/src/Application/` that no single aggregate owns

**THEN** put it in `core/src/Application/Helper/` — never flat in `Application/`, and never inside an aggregate folder
that does not own it. A helper carries no aggregate vocabulary: the moment it does it belong in that aggregate's folder
instead.

**Example:**

| Wrong                                                               | Right                                                            |
|---------------------------------------------------------------------|------------------------------------------------------------------|
| `core/src/Application/Command.php` (flat in the layer)              | `core/src/Application/Helper/Command.php`                        |
| `core/src/Application/Movement/Command.php` as every command's base | `core/src/Application/Helper/Command.php`                        |
| a `Helper/` class taking or returning `Movement`                    | a method on the aggregate, or a class in `Application/Movement/` |

## application-helpers-0002: Command is the base every application command extends

**WHEN** writing a `Core\Application` command, or asking whether one of its fields was supplied

**THEN** extend `Core\Application\Helper\Command` and ask through `hasProperty(string $name): bool`, never `isset()`.
Backed by `ReflectionProperty::isInitialized()`, it reports `true` for a field supplied as `null` and `false` only for
one never supplied at all; `isset()` reports `false` for both and so collapses "clear this field" into "leave it alone".

Uninitialised properties trip PHPStan's `property.uninitializedReadonly`. Silence it with an inline
`// @phpstan-ignore` on each property rather than giving up `readonly` or widening anything in
`phpstan.dist.neon`; an ignore that stops matching is itself a build error, so it cannot outlive its property.

**Example:**

| Wrong                                                       | Right                                                        |
|-------------------------------------------------------------|--------------------------------------------------------------|
| `isset($command->location)`                                 | `$command->hasProperty('location')`                          |
| `final readonly class UpdateMovementCommand` standing alone | `final readonly class UpdateMovementCommand extends Command` |
| dropping `readonly` to satisfy PHPStan                      | keep it; ignore the one identifier instead                   |
