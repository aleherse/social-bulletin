# Application / Style

**WHEN** declaring a controller (or other service) whose constructor-promoted properties are all
immutable
**THEN** mark the class `final readonly class` and drop `readonly` from each individual promoted
property — don't repeat `readonly` per property on a `final class`.

*Example:*
    | Wrong                                                    | Right                                              |
    |-------------------------------------------------------------|------------------------------------------------------|
    | `final class SessionController { public function __construct(private readonly UserService $userService) {} }` | `final readonly class SessionController { public function __construct(private UserService $userService) {} }` |
