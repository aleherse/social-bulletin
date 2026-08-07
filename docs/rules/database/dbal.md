# Database / DBAL

**WHEN** adding or changing a repository for an aggregate in `packages/core`
**THEN** implement it as a single concrete class in `packages/core/src/<Aggregate>/`, constructed with
`Doctrine\DBAL\Connection` directly — do not split it into a core `interface` plus a `Dbal*` adapter class
in `apps/api/src/Repository/`.

*Example:*
    | Before (ports & adapters split)                                                                    | After (core-owned concrete class)                              |
    |------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------|
    | `packages/core/src/User/UserRepository.php` (interface) + `apps/api/src/Repository/DbalUserRepository.php` | `packages/core/src/User/UserRepository.php` (concrete class)      |
    | `packages/core/src/Movement/Categories.php` (interface) + `apps/api/src/Repository/DbalCategories.php`     | `packages/core/src/Movement/CategoryRepository.php` (concrete class) |

---

**WHEN** naming a repository class in `packages/core/src/<Aggregate>/`
**THEN** name it `<Aggregate>Repository`, matching the aggregate it persists — not a data-shape name.

*Example:*
    | Wrong                                    | Right                                              |
    |--------------------------------------------|------------------------------------------------------|
    | `packages/core/src/Movement/Categories.php` | `packages/core/src/Movement/CategoryRepository.php` |

