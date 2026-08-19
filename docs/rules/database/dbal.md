# Database / DBAL

## database-dbal-0001: Repositories are concrete classes owned by core

**WHEN** adding or changing a repository for an aggregate in `core`

**THEN** implement it as a single concrete class in `core/src/Domain/<Aggregate>/`, constructed with
`Doctrine\DBAL\Connection` directly — do not split it into a core `interface` plus a `Dbal*` adapter class in
`apps/api/src/Repository/`.

**Example:**

| Before (ports & adapters split)                                                                          | After (core-owned concrete class)                                  |
|----------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------|
| `core/src/Domain/User/UserRepository.php` (interface) + `apps/api/src/Repository/DbalUserRepository.php` | `core/src/Domain/User/UserRepository.php` (concrete class)         |
| `core/src/Domain/Movement/Categories.php` (interface) + `apps/api/src/Repository/DbalCategories.php`     | `core/src/Domain/Movement/CategoryRepository.php` (concrete class) |

## database-dbal-0002: Repositories named after their aggregate

**WHEN** naming a repository class in `core/src/Domain/<Aggregate>/`

**THEN** name it `<Aggregate>Repository`, matching the aggregate it persists — not a data-shape name.

**Example:**

| Wrong                                     | Right                                             |
|-------------------------------------------|---------------------------------------------------|
| `core/src/Domain/Movement/Categories.php` | `core/src/Domain/Movement/CategoryRepository.php` |

## database-dbal-0003: Foreign keys enforce lookup existence

**WHEN** an aggregate's persisted column has a database-level foreign key to a managed lookup table (e.g.
`movements.category` → `bulletin.categories.id`)

**THEN** let the constraint enforce existence and catch
`Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException` inside the repository's `save()`, translating it into
the aggregate's own validation exception — do not run a separate
`exists()` SELECT beforehand to pre-validate the same thing.

**Example:**

`core/src/Domain/Movement/MovementRepository.php` `save()`:

```php
try {
    $this->connection->executeStatement(/* INSERT ... ON CONFLICT (id) DO UPDATE ... */, [/* ... */]);
} catch (ForeignKeyConstraintViolationException $exception) {
    if (! str_contains($exception->getMessage(), 'movements_category_fk')) {
        throw $exception;
    }

    throw new InvalidMovement([
        'category' => 'movement.category.unknown',
    ], 'movement.invalid', $exception);
}
```

Check the exception message for the specific constraint name so unrelated FK violations (e.g. `movements_author_fk`) are
not misreported as the wrong field. Only the write is wrapped: the stored row is read back afterwards through
`byId()`, since the insert carries no `RETURNING` clause (`database-persistence-0002`).
