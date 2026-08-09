# Domain / Common

**WHEN** adding or changing an operation that mutates an aggregate in `packages/core/src/<Aggregate>/`
**THEN** express the operation as a command object and handle it in the aggregate's single
`apply(<Aggregate>Command $command): void` method — a `final readonly class` per intent, flat in the
aggregate folder, implementing the `<Aggregate>Command` marker interface;
`apply()` checks the invariants shared by every command, then `match (true)` dispatches to a
*private* handler per command, with a `default` arm throwing `\LogicException`. Creation keeps its
named constructor, taking the creation command (which does not implement the interface, since it has
no instance to apply to). Never expose a public mutator per operation.

*Example:*
    | Wrong                                                        | Right                                                     |
    |------------------------------------------------------------------|---------------------------------------------------------------|
    | `$movement->edit($title, $description, $category, $area, $location)` | `$movement->apply(new EditMovement($title, $description, $category, $area, $location))` |
    | `$movement->submit()`                                         | `$movement->apply(new SubmitMovement())`                   |
    | draft-status guard repeated in `edit()` and `submit()`        | guard once at the top of `apply()`                         |
    | `Movement::draft($id, $authorId, $title, …)`                   | `Movement::draft($id, new DraftMovement($authorId, …))`    |

    Files: `Movement.php` (aggregate), `MovementCommand.php` (interface),
    `EditMovement.php`, `SubmitMovement.php` (intents), `DraftMovement.php` (creation).
