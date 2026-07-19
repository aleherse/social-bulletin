# Phase 0 Research: Propose a Movement

No `NEEDS CLARIFICATION` markers remained in the Technical Context;
research below records the decisions that shape the design and the
alternatives that were rejected.

## R1. Category storage: reference table

- **Decision**: Store categories in `bulletin.categories`
  (`id` text primary key, e.g. `animal_rights`, plus a sort order),
  seeded by the same migration that creates it.
  `bulletin.movements.category` is a foreign key to it.
- **Rationale**: The spec calls the list "managed" and expects it to
  grow without changing the feature (FR-002, Assumptions).
  A row insert extends the list; no schema change, no enum migration.
  The API can serve the list to the frontend form from one query.
- **Alternatives considered**:
  - PostgreSQL `ENUM` type — extending needs `ALTER TYPE`, and the
    frontend still needs a way to fetch values; rejected.
  - `CHECK` constraint on a text column — same drawback; rejected.
  - Hard-coded list in code — duplicated between PHP and TypeScript
    and not "managed by the platform"; rejected.

## R2. Area storage: text column with CHECK constraint

- **Decision**: `area` is a text column constrained by `CHECK` to the
  seven values from FR-003, mirrored by a `SocialBulletin\Core\Area`
  PHP enum and a TypeScript union type.
- **Rationale**: The area list is closed and defined by the spec, not
  managed data; a constraint plus code enums keeps it in sync with
  domain logic without a join.
- **Alternatives considered**: reference table (overkill for a fixed
  enumeration — it never grows without a spec change);
  PostgreSQL `ENUM` (harder to evolve than a `CHECK`); both rejected.

## R3. Location: nullable text, consistency enforced in the domain

- **Decision**: `location` is a nullable text column.
  The `Movement` aggregate enforces FR-004: `international` movements
  must have no location, every other area requires a non-empty one.
  A `CHECK ((area = 'international') = (location IS NULL))` backs it
  up at the database level.
- **Rationale**: The spec scopes location to "a named place";
  geocoding and structured hierarchies are explicitly out of scope,
  so free text is the whole requirement.
- **Alternatives considered**: separate locations table or structured
  columns (country/region/city) — builds a hierarchy the spec defers;
  rejected.

## R4. Markdown safety: store raw, render sanitised on the client

- **Decision**: Persist the description as the raw markdown the author
  typed.
  Render it in `apps/web` with `react-markdown`, which parses markdown
  to React elements and never injects raw HTML by default
  (no `rehype-raw`, no `dangerouslySetInnerHTML`).
- **Rationale**: FR-008 requires the *rendered output* never execute
  scripts or embed unsafe HTML.
  Rendering happens only in the frontend; a renderer that cannot emit
  raw HTML satisfies the requirement without a server-side sanitiser
  to maintain.
  Storing the original text preserves the author's draft losslessly.
- **Alternatives considered**:
  - Server-side sanitisation (e.g. HTMLPurifier) — adds a backend
    dependency to solve a rendering concern the backend does not have
    (the API never renders HTML); rejected.
  - Rejecting HTML at input time — would surprise authors pasting
    innocent angle brackets into drafts; rejected.

## R5. Status and transitions: PHP enum + aggregate method

- **Decision**: `MovementStatus` PHP enum (`draft`, `proposed`,
  `published`) with the single in-scope transition implemented as
  `Movement::submit()`, which fails unless status is `draft` and the
  description is non-empty (FR-006).
  The database stores the enum's string value with a `CHECK`.
  `published` exists as a value but nothing in this feature sets it.
- **Rationale**: Keeps the lifecycle rule inside the aggregate
  (hexagonal.md: behaviour in entities, not services) and anticipates
  the deferred moderation spec without implementing it.
- **Alternatives considered**: state machine library — one guarded
  transition does not justify a dependency; rejected.

## R6. API shape: resource endpoints + explicit submit action

- **Decision**: Four endpoints under the existing `/api` firewall:
  `GET /api/categories`, `POST /api/movements`,
  `GET /api/movements` (own movements) with
  `GET /api/movements/{id}`, `PATCH /api/movements/{id}` (draft edits),
  and `POST /api/movements/{id}/submit` for the transition.
  All require the authenticated cookie session (FR-001, FR-007).
- **Rationale**: Submission is a domain action with its own rules, not
  a field update; a dedicated action endpoint keeps `PATCH` for field
  edits and makes the transition auditable and testable on its own
  (matches "Skipping Use Cases" avoidance in hexagonal.md).
- **Alternatives considered**: `PATCH { "status": "proposed" }` —
  hides a guarded transition inside a generic update and invites
  invalid transitions; rejected.

## R7. Frontend structure: entities + features + page slices (FSD)

- **Decision**: `entities/movement` (types, query hooks, read-only
  markdown view), `features/propose-movement` (draft form and submit
  interaction), `pages/movements` (list of own movements and the
  editor route).
  Categories are fetched via TanStack Query and cached.
- **Rationale**: ADR-0007 mandates FSD with layers added only as
  needed; the movement model is reused by both the form feature and
  the pages, which justifies the `entities` layer, and interactive
  proposal logic sits in `features`.
- **Alternatives considered**: everything inside `pages/movements`
  (would duplicate the model between create and list slices);
  a `widgets` layer (nothing composite enough yet); both rejected.
