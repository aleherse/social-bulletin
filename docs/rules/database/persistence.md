# Database / Persistence

## database-persistence-0001: The database assigns the timestamps

**WHEN** an aggregate in `core` has persisted `created_at` / `updated_at` columns

**THEN** let the database assign them: the repository's `save()` writes `now()` for both on insert, sets
`updated_at = now()` in the `ON CONFLICT DO UPDATE` list while leaving `created_at` out of it, and returns `void`.
The aggregate takes no `\DateTimeImmutable $now` parameter and
exposes no writer for the timestamps — the properties are private, set only by `restore()`, and read through
`createdAt()` / `updatedAt()`, so an aggregate built by `draft()` has no timestamps until it has been read back.

**Example:**

| Wrong                                                                       | Right                                                                        |
|-----------------------------------------------------------------------------|------------------------------------------------------------------------------|
| `Movement::draft($id, $authorId, $title, …, new \DateTimeImmutable())`      | `Movement::draft($id, $authorId, $title, …)`                                 |
| `CreateMovementHandler` calling `new \DateTimeImmutable()`                  | Postgres `now()` inside `MovementRepository::save()`                         |
| `save(Movement $movement): Movement`, returning `$this->byId(…)`            | `save(Movement $movement): void`                                             |
| a public `markSaved()` or timestamp setter on the aggregate                 | `restore()` alone, hydrating from the row a finder read back                 |
| `'updated_at' => $movement->updatedAt()->format(ATOM)` bound as a parameter | `updated_at = now()` in the SQL                                              |

## database-persistence-0002: One shared query builder per repository

**WHEN** a repository in `core/src/Domain/<Aggregate>/` reads rows to hydrate its aggregate

**THEN** declare the selected columns once, in a private `getQueryBuilder(): \Doctrine\DBAL\Query\QueryBuilder`
holding only `select(...)` and `from(...)` and, if required, some `innerJoin(...)`; every finder starts from it, adds nothing but its own
`where` / `orderBy` / `setParameter`, and ends in `fetchAssociative()` or `fetchAllAssociative()` feeding a private
`hydrate()`.

**Example:**

| Wrong                                                                        | Right                                                                                      |
|------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------|
| `private const COLUMNS = 'id, author_id, title, …';`                         | `getQueryBuilder()->select('id', 'author_id', …)`                                          |
| `sprintf('SELECT %s FROM bulletin.movements WHERE id = :id', self::COLUMNS)` | `$this->getQueryBuilder()->where('id = :id')->setParameter('id', $id)->fetchAssociative()` |

`MovementRepository`: `byId()` and `byAuthor()` share it; `byAuthor()` adds
`orderBy('created_at', 'DESC')->addOrderBy('id', 'DESC')`.
`UserRepository`: `findByEmail()` adds `where('LOWER(email) = LOWER(:email)')`.
