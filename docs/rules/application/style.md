# Application / Style

## application-style-0001: Final readonly classes over per-property readonly

**WHEN** declaring a controller (or other service) whose constructor-promoted properties are all immutable

**THEN** mark the class `final readonly class` and drop `readonly` from each individual promoted property — don't repeat
`readonly` per property on a `final class`.

**Example:**

| Wrong                                                                                                      | Right                                                                                                      |
|------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------|
| `final class SignInController { public function __construct(private readonly CommandBus $commandBus) {} }` | `final readonly class SignInController { public function __construct(private CommandBus $commandBus) {} }` |
