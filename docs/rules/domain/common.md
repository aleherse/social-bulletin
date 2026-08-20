# Domain / Common

## domain-common-0001: Aggregate identifiers are value objects sharing one base

**WHEN** an aggregate in `packages/core/src/Domain/<Aggregate>/` needs an identifier

**THEN** make it a thin final subclass of `Domain\Helper\AggregateId` (`domain-helpers-0001`) in the aggregate's own
folder — e.g. `final class MovementId extends AggregateId {}`. `AggregateId` supplies `from(string): static`
(validates via `Assert::uuid()`), `generate(): static`, `equals(self): bool`, and `__toString()`.

Aggregate factories (`draft()`, `register()`, `restore()`) take the id VO directly, never a raw string. Compare ids
with `->equals()`, never `===`/`!==` — two VOs holding the same UUID are different object instances.

**Example:**

| Wrong                                                      | Right                                                     |
|----------------------------------------------------------------|-----------------------------------------------------------|
| `Movement::draft(string $id, string $authorId, ...)`           | `Movement::draft(MovementId $id, UserId $authorId, ...)`  |
| `$movement->authorId !== $authorId`                              | `! $movement->authorId->equals($authorId)`                |
| a hand-rolled id class duplicating validation/equality           | `final class ... extends Domain\Helper\AggregateId {}`    |
