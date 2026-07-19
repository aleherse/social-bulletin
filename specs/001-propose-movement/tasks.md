# Tasks: Propose a Movement

**Input**: Design documents from `/specs/001-propose-movement/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md,
contracts/movements-api.md, quickstart.md

**Tests**: Included — ADR-0015 mandates PHPSpec (core), Behat (api),
and Vitest (web) coverage; write them first within each story.

**Organization**: Tasks are grouped by user story so each story is an
independently implementable and testable increment.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Include exact file paths in descriptions

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Database objects and schema documentation every story
needs.

- [X] T001 Generate a Doctrine migration (via `make console` +
      `doctrine:migrations:generate`) in `apps/api/migrations/` that
      creates `bulletin.categories` (seeded with `animal_rights`,
      `anti-racism`, `black_power`, `cooperative`) and
      `bulletin.movements` with the CHECK constraints and FK from
      data-model.md, raw SQL only (ADR-0009)
- [X] T002 [P] Create `db/schema.dbml` documenting `bulletin.users`,
      `bulletin.categories`, and `bulletin.movements` per
      data-model.md
- [X] T003 Rebuild the database and DSLR snapshot with `make db`;
      confirm the migration applies cleanly on a fresh database

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core enums and ports every story compiles against.

**⚠️ CRITICAL**: No user story work can begin until this phase is
complete.

- [X] T004 [P] Create `MovementStatus` enum (`draft`, `proposed`,
      `published`) in
      `packages/core/src/Movement/MovementStatus.php`
- [X] T005 [P] Create `Area` enum (seven FR-003 values) in
      `packages/core/src/Movement/Area.php`
- [X] T006 [P] Create `Category` value object in
      `packages/core/src/Movement/Category.php` and `Categories` port
      (`all()`, `exists()`) in
      `packages/core/src/Movement/Categories.php`
- [X] T007 [P] Create `MovementRepository` port (`save`, `byId`,
      `byAuthor`) in
      `packages/core/src/Movement/MovementRepository.php`

**Checkpoint**: Foundation ready — user story phases can begin.

---

## Phase 3: User Story 1 - Create a movement draft (Priority: P1) 🎯 MVP

**Goal**: A signed-in user saves a movement draft (title, category,
area, location, optional markdown description) and sees it in their
own list.

**Independent Test**: Create a draft with valid values via UI or API
and confirm it is saved with status `draft`, visible only to its
author; invalid input and guests are rejected.

### Tests for User Story 1 (write first, must fail)

- [X] T008 [P] [US1] PHPSpec for creation rules (title required and
      ≤ 200 chars, description ≤ 20,000 chars, valid category/area,
      location required unless `international`, empty description
      allowed, status starts `draft`) in
      `packages/core/spec/Movement/MovementSpec.php`
- [X] T009 [P] [US1] PHPSpec for `MovementService::create`
      (id generation, category lookup, persistence) in
      `packages/core/spec/Movement/MovementServiceSpec.php`
- [X] T010 [P] [US1] Behat scenarios for US1 acceptance cases 1–5
      (valid draft 201, empty-description draft 201, duplicate title
      coexists, missing fields 400 naming each field, guest 401) plus
      `GET /api/categories` and own-list visibility in
      `apps/api/features/movements.feature`

### Implementation for User Story 1

- [X] T011 [US1] Implement the `Movement` aggregate (named constructor
      enforcing creation rules from data-model.md) in
      `packages/core/src/Movement/Movement.php`
- [X] T012 [P] [US1] Add domain exceptions (`InvalidMovement`,
      `MovementNotFound`) in `packages/core/src/Movement/`
- [X] T013 [US1] Implement `MovementService::create` using
      `IdentityGenerator`, `Categories`, and `MovementRepository` in
      `packages/core/src/Movement/MovementService.php`
- [X] T014 [P] [US1] Implement `DbalCategories` adapter in
      `apps/api/src/Repository/DbalCategories.php`
- [X] T015 [P] [US1] Implement `DbalMovementRepository` (`save`,
      `byId`, `byAuthor`) in
      `apps/api/src/Repository/DbalMovementRepository.php`
- [X] T016 [US1] Implement `MovementController` with
      `POST /api/movements`, `GET /api/movements`, and
      `GET /api/movements/{id}` mapping 201/400/401/404 per
      contracts/movements-api.md in
      `apps/api/src/Controller/MovementController.php`
- [X] T017 [P] [US1] Implement `GET /api/categories` in
      `apps/api/src/Controller/CategoryController.php`
- [X] T018 [US1] Wire ports to adapters (service aliases if
      autowiring needs them) in `apps/api/config/services.yaml`
- [ ] T019 [P] [US1] Add `react-markdown` to `apps/web` and create the
      `entities/movement` slice (types, TanStack Query hooks for
      categories/create/list/get, markdown description view) in
      `apps/web/src/entities/movement/`
- [ ] T020 [US1] Build the draft form (category select fed by the API,
      area select, location hidden for `international`, optional
      description) in `apps/web/src/features/propose-movement/`
- [ ] T021 [US1] Add the movements page slice (my-movements list +
      new-draft route) in `apps/web/src/pages/movements/` and wire
      routing in `apps/web/src/app/`
- [ ] T022 [P] [US1] Add i18n strings for form, list, statuses, and
      validation messages in
      `apps/web/src/shared/i18n/locales/en/common.json`
- [ ] T023 [US1] Vitest tests for form validation behaviour and list
      rendering in `apps/web/src/features/propose-movement/` and
      `apps/web/src/pages/movements/`

**Checkpoint**: User Story 1 is fully functional — drafts can be
created and listed; MVP deliverable.

---

## Phase 4: User Story 2 - Submit a draft as a proposal (Priority: P2)

**Goal**: The author moves a draft to `proposed`; empty descriptions
and repeat submissions are rejected.

**Independent Test**: Submit an existing draft and confirm status
becomes `proposed`; an empty-description draft is refused with an
explanation; a second submit returns a conflict.

### Tests for User Story 2 (write first, must fail)

- [ ] T024 [P] [US2] PHPSpec for `Movement::submit` (draft with
      description → `proposed`; empty description rejected; non-draft
      rejected) in `packages/core/spec/Movement/MovementSpec.php`
- [ ] T025 [P] [US2] Behat `Given` steps that create movements through
      application code (ADR-0015) in the Behat context under
      `apps/api/features/`, plus US2 scenarios (submit 200,
      empty-description 400, resubmit 409, another user's draft 404)
      in `apps/api/features/movements.feature`

### Implementation for User Story 2

- [ ] T026 [US2] Implement `Movement::submit()` guarding status and
      description (FR-006) in
      `packages/core/src/Movement/Movement.php`
- [ ] T027 [US2] Implement `MovementService::submit` (author check +
      persistence) in `packages/core/src/Movement/MovementService.php`
- [ ] T028 [US2] Add `POST /api/movements/{id}/submit` mapping
      200/400/404/409 per contract in
      `apps/api/src/Controller/MovementController.php`
- [ ] T029 [US2] Add the submit action and status display to the
      frontend (mutation hook in `apps/web/src/entities/movement/`,
      submit button + error message in
      `apps/web/src/features/propose-movement/`) with Vitest coverage

**Checkpoint**: Stories 1 and 2 work independently — drafts can be
proposed.

---

## Phase 5: User Story 3 - Edit a draft before submission (Priority: P3)

**Goal**: The author edits any field of a movement while it is still a
draft.

**Independent Test**: Change a draft's fields and confirm the changes
persist with status still `draft`; editing a proposed movement is
refused.

### Tests for User Story 3 (write first, must fail)

- [ ] T030 [P] [US3] PHPSpec for edit behaviour (field updates allowed
      only in `draft`, same validation as creation, `updatedAt`
      touched) in `packages/core/spec/Movement/MovementSpec.php`
- [ ] T031 [P] [US3] Behat scenarios for US3 (PATCH edits 200,
      validation 400, non-draft 409, another user's movement 404) in
      `apps/api/features/movements.feature`

### Implementation for User Story 3

- [ ] T032 [US3] Add edit methods to the `Movement` aggregate in
      `packages/core/src/Movement/Movement.php`
- [ ] T033 [US3] Implement `MovementService::update` in
      `packages/core/src/Movement/MovementService.php`
- [ ] T034 [US3] Add `PATCH /api/movements/{id}` mapping
      200/400/404/409 per contract in
      `apps/api/src/Controller/MovementController.php`
- [ ] T035 [US3] Add the edit route reusing the draft form (update
      hook in `apps/web/src/entities/movement/`, edit page in
      `apps/web/src/pages/movements/`) with Vitest coverage

**Checkpoint**: All three user stories are independently functional.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [ ] T036 [P] Add reusable baseline movements to
      `apps/api/features/fixtures.feature` (only genuinely shared
      data, ADR-0015) and refresh the snapshot with `make db`
- [ ] T037 [P] Verify the nelmio OpenAPI JSON documents the five new
      endpoints; add attributes in
      `apps/api/src/Controller/` where missing
- [ ] T038 Run `make lint` and the full `make tests` suite; fix any
      fallout across `packages/core`, `apps/api`, `apps/web`
- [ ] T039 Walk through `specs/001-propose-movement/quickstart.md`
      end-to-end (browser + curl flows) and correct any drift

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: no dependencies; T003 needs T001.
- **Foundational (Phase 2)**: independent of Phase 1 (pure PHP), but
  both block the stories.
- **User Stories (Phases 3–5)**: all need Phases 1–2.
  US2 (T026, T027, T028) and US3 (T032, T033, T034) extend files
  created by US1 (T011, T013, T016), so run the backend of US1 first;
  US2 and US3 are independent of each other.
- **Polish (Phase 6)**: after the stories you choose to ship.

### Within Each User Story

- Tests first, confirmed failing, then implementation.
- Aggregate → service → adapters/controller → frontend.
- PHPSpec files are shared across stories (`MovementSpec.php`), so
  later stories append to them rather than rewrite.

### Parallel Opportunities

- T004–T007 (all foundational files) in parallel.
- Within US1: T008–T010 in parallel; then T014, T015, T017, T019, T022
  in parallel once their prerequisites exist.
- US2 and US3 can proceed in parallel after US1's backend, if the
  shared files (`Movement.php`, `MovementService.php`,
  `MovementController.php`, `movements.feature`) are coordinated.

## Parallel Example: User Story 1

```bash
# Write all US1 tests together first:
Task: "PHPSpec creation rules in packages/core/spec/Movement/MovementSpec.php"
Task: "PHPSpec service spec in packages/core/spec/Movement/MovementServiceSpec.php"
Task: "Behat US1 scenarios in apps/api/features/movements.feature"

# After T011+T013, build adapters and frontend groundwork in parallel:
Task: "DbalCategories in apps/api/src/Repository/DbalCategories.php"
Task: "DbalMovementRepository in apps/api/src/Repository/DbalMovementRepository.php"
Task: "entities/movement slice in apps/web/src/entities/movement/"
```

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phases 1–2 (migration, schema doc, enums, ports).
2. Phase 3 (US1) — drafts creatable and listed.
3. **STOP and VALIDATE**: run `make php-unit`, `make api-tests`,
   `make web-unit`; demo draft creation.

### Incremental Delivery

1. US1 → validate → demo (MVP).
2. US2 → drafts become proposals → validate → demo.
3. US3 → drafts editable → validate → demo.
4. Phase 6 polish before merging to `main`.

## Notes

- Auto-commit hooks commit after each speckit phase; still commit
  after each task or logical group during implementation.
- Behat runs restore the DSLR `fixtures` snapshot; never recreate the
  snapshot from a test run (ADR-0015).
- Keep `packages/core` free of Symfony/DBAL imports (deptrac enforces
  the boundary) and respect FSD import rules in `apps/web`.
