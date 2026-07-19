# Data Model: Propose a Movement

## Domain model (`packages/core`)

### Movement (aggregate root)

| Field         | Type                | Rules                                                       |
|---------------|---------------------|-------------------------------------------------------------|
| `id`          | string (UUID v7)    | Generated via the existing `IdentityGenerator` port         |
| `authorId`    | string (UUID v7)    | The signed-in user who created the draft (FR-001, FR-009)   |
| `title`       | string              | Required, non-empty, max 200 chars                          |
| `description` | string              | May be empty while `draft`; required to submit (FR-006)     |
| `category`    | string              | Must exist in the managed category list (FR-002)            |
| `area`        | `Area` enum         | One of the seven values (FR-003)                            |
| `location`    | ?string             | Required unless area is `international`, then null (FR-004) |
| `status`      | `MovementStatus`    | `draft` on creation (FR-005)                                |
| `createdAt`   | `DateTimeImmutable` | Set on creation (FR-009)                                    |
| `updatedAt`   | `DateTimeImmutable` | Touched on every edit and on submit (FR-009)                |

Behaviour (methods on the aggregate, not in services):

- Constructor/named constructor enforces title, category, area, and
  location rules; status starts as `draft`.
- Edit methods only work while `draft` (FR-007 scope for this feature;
  editing `proposed` movements is not exposed).
- `submit()` — allowed only when status is `draft` **and** description
  is non-empty; moves status to `proposed` (FR-006, US2).
  Any other transition throws.

### Value enumerations

- `MovementStatus`: `draft` | `proposed` | `published`
  (`published` is never produced by this feature).
- `Area`: `international` | `national` | `state` | `province` |
  `region` | `municipality` | `neighborhood`.

### Ports

- `MovementRepository`: `save(Movement)`, `byId(string): ?Movement`,
  `byAuthor(string): Movement[]` — one repository for the aggregate.
- `Categories`: `all(): Category[]`, `exists(string): bool` —
  read-only port over the managed list.

### Relationships

- Movement → User: `authorId` references the existing `bulletin.users`
  row; users are a separate aggregate, referenced by id only.
- Movement → Category: by category id; categories are reference data,
  not part of the aggregate.

## Database schema (PostgreSQL, schema `bulletin`)

Documented in DBML; `db/schema.dbml` is created/updated during
implementation, and the migration is raw SQL over DBAL (ADR-0009).

```dbml
Table bulletin.categories {
  id         text        [pk, note: 'e.g. animal_rights, cooperative']
  sort_order int         [not null, default: 0]

  Note: 'Managed movement category list; seeded by migration'
}

Table bulletin.movements {
  id          uuid        [pk]
  author_id   uuid        [not null, ref: > bulletin.users.id]
  title       varchar(200) [not null]
  description text        [not null, default: '',
                           note: 'raw markdown; empty allowed in draft']
  category    text        [not null, ref: > bulletin.categories.id]
  area        text        [not null,
                           note: 'CHECK: 7 area values (FR-003)']
  location    text        [note: 'CHECK: null iff area = international']
  status      text        [not null, default: 'draft',
                           note: 'CHECK: draft | proposed | published']
  created_at  timestamptz [not null]
  updated_at  timestamptz [not null]

  indexes {
    author_id [name: 'movements_author_idx']
  }

  Note: 'Movement proposals; submit requires non-empty description'
}
```

Migration notes:

- One new migration creates `bulletin.categories`, seeds
  `animal_rights`, `anti-racism`, `black_power`, `cooperative`,
  then creates `bulletin.movements` with the three `CHECK` constraints
  above and the FK to `bulletin.users`.
- `description` uses `NOT NULL DEFAULT ''` — "empty" means empty
  string, avoiding a null/empty ambiguity in code and SQL.
- The submit rule (non-empty description when leaving `draft`) is
  domain logic, not a constraint: a draft may legitimately be empty,
  so only the transition guards it.

## State transitions

```text
(create) ──> draft ──submit()──> proposed        published
                 ^                                (exists, unreachable
                 └── edits allowed only here       in this feature)
```

- `draft` → `proposed`: author only, description required (FR-006).
- Every other transition is rejected (FR-006).
- `proposed` → `published` belongs to the future moderation spec.
