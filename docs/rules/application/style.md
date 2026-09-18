# Application / Style

## application-style-0001: Final readonly classes over per-property readonly

**WHEN** declaring a controller (or other service) whose constructor-promoted properties are all immutable

**THEN** mark the class `final readonly class` and drop `readonly` from each individual promoted property — don't repeat
`readonly` per property on a `final class`.

This does not apply to a class PHPSpec doubles — the repositories and the providers. Prophecy can reflect neither
a `final` class nor a `readonly` one, so those stay `class <Name>` with `readonly` on each promoted property
(`application-queries-0001`).

**Example:**

| Wrong                                                                                                      | Right                                                                                                      |
|------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------|
| `final class SignInController { public function __construct(private readonly CommandBus $commandBus) {} }` | `final readonly class SignInController { public function __construct(private CommandBus $commandBus) {} }` |
| `final readonly class MovementProvider`, doubled in a spec                                                | `class MovementProvider` with `private readonly Connection $connection`                                    |
