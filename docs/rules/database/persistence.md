# Database / Persistence

**WHEN** an aggregate in `packages/core` has persisted `created_at` / `updated_at` columns
**THEN** let the database assign them: the repository's `save()` writes `now()` for both on insert,
sets `updated_at = now()` in the `ON CONFLICT DO UPDATE` list while leaving `created_at` out of it,
ends with `RETURNING <all columns>`, and returns the hydrated row as a fresh aggregate. The aggregate
takes no `\DateTimeImmutable $now` parameter and exposes no writer for the timestamps — the
properties are private, set only by `restore()`, and read through `createdAt()` / `updatedAt()`, so
an aggregate built by `draft()` has no timestamps until it has been saved.

*Example:*
    | Wrong                                                       | Right                                            |
    |-----------------------------------------------------------------|------------------------------------------------------|
    | `Movement::draft($id, $command, new \DateTimeImmutable())`   | `Movement::draft($id, $command)`                  |
    | `MovementService` calling `new \DateTimeImmutable()`         | Postgres `now()` inside `MovementRepository::save()` |
    | `save(Movement $movement): void` plus a public `markSaved()` | `save(Movement $movement): Movement`, hydrated from `RETURNING` |
    | `'updated_at' => $movement->updatedAt()->format(ATOM)` bound as a parameter | `updated_at = now()` in the SQL      |
