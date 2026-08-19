# Domain / Common

## domain-common-0001: Aggregate mutations are explicit methods

**WHEN** adding or changing an operation that mutates an aggregate in `core/src/Domain/<Aggregate>/`

**THEN** expose it as an intention-revealing public method taking the fields it needs, and let named constructors take
their fields directly. Invariants shared by every mutation live in one private assertion each mutator calls first.
Commands are an application-layer concern (`application-commands-0001`): no `apply()` dispatcher, no
`<Aggregate>Command` marker interface, and no command class in the `Domain` namespace.

**Example:**

| Wrong                                                         | Right                                                                |
|---------------------------------------------------------------|----------------------------------------------------------------------|
| `$movement->apply(new SaveMovementCommand($title, …))`        | `$movement->edit($title, $description, $category, $area, $location)` |
| `$movement->apply(new SubmitMovementCommand())`               | `$movement->submit()`                                                |
| `Movement::draft($id, new SaveMovementCommand($authorId, …))` | `Movement::draft($id, $authorId, $title, …)`                         |
| draft guard inside `apply()`, dispatching by `instanceof`     | `assertDraft()` called by `edit()` and `submit()`                    |

Files: `Movement.php` holds `draft()`, `edit()`, `submit()` and the private `assertDraft()`; the commands they used to
take live in `core/src/Application/Movement/` (ADR-0017).
