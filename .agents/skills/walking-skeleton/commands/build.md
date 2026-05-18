# Skeleton Build

Interactive workflow for turning ADR decisions and a walking-skeleton specification into a working, end-to-end project slice. The command plans executable tasks, implements them category by category, and verifies the result can be recreated from scratch.

Run after `/walking-skeleton plan` (ADRs in `specs/decisions/`) and `/walking-skeleton spec` (walking-skeleton spec written and reviewed).

## Principles

- **Gate on prerequisites.** Do not scaffold code until ADRs and a walking-skeleton spec exist. Direct the user to an earlier command when something is missing.
- **Walking skeleton first.** Do not follow TDD, Spec Kit, OpenSpec, or similar spec-driven build workflows until the user explicitly instructs otherwise. The goal is a tiny working slice that proves architectural seams, not a full product backlog.
- **Tasks before bulk implementation.** When no task list exists, compile a reviewed `tasks.md` before writing application code. Replan when the user asks, or when tasks clearly no longer match the ADRs or walking-skeleton spec.
- **One category at a time.** Finish, validate, and get user confirmation for each task category before moving to the next. Clear context at each category boundary and work only from that category's tasks and its referenced ADRs.
- **ADR-grounded work.** Every task links to the ADR(s) it implements, except **documentation** tasks — those are required by this workflow even when no ADR covers them. When a task spans concerns, split it across categories instead of blending them.
- **Executable tasks only.** Each task must be specific enough to start immediately — include paths, commands, package names, and filenames where the ADR implies them.
- **Pause on surprises.** Stop and ask when implementation reveals a conflict, missing decision, or a better alternative.

## Preconditions — run before Step 1, 2, or 3

Run these checks in the project root every time this command is invoked.

### 1. Confirm ADRs exist

1. Check whether `specs/decisions/` exists.
2. Look for at least one ADR document, matching common names such as:
   - `specs/decisions/ADR-*.md`
   - `specs/decisions/*adr*.md`
   - `specs/decisions/*.md` with ADR-style sections (`Context`, `Decision`, `Consequences`)
3. If no ADR is found, stop and tell the user:
   - `specs/decisions/` does not contain project ADRs yet.
   - They should run `/walking-skeleton plan` first.
   - Return here after ADRs exist.

### 2. Confirm walking-skeleton spec exists

Look for the walking-skeleton specification at least at:

- `specs/changes/walking-skeleton/spec.md`
- `specs/changes/walking-skeleton/**/spec.md`
- OpenSpec-style paths such as `specs/changes/walking-skeleton/specs/**/spec.md`
- Spec Kit or similar paths when project files indicate them (`.specify/`, `.openspec/`, `openspec.json`, existing `specs/changes/` conventions)

If no walking-skeleton spec is found, stop and tell the user:

- The walking-skeleton specification is missing.
- They should run `/walking-skeleton spec` first.
- Return here after the spec is written and reviewed.

When a spec exists, read it along with the ADRs before planning or implementing.

### 3. Locate or create the task list

Check for a task file:

- Default: `specs/changes/walking-skeleton/tasks.md`
- OpenSpec / Spec Kit / project-specific paths when clearly configured (e.g. `specs/001-walking-skeleton/tasks.md`, tool-generated `tasks.md` beside the change)

**Routing rule:**

- If `tasks.md` exists and at least one task is incomplete (`- [ ]`) → go to **Step 2**.
- If `tasks.md` exists and every task is complete (`- [x]`) → go to **Step 3**.
- If `tasks.md` is missing or empty → go to **Step 1**.

Do not re-create `tasks.md` in Step 1 when a non-empty task list already exists unless the user explicitly asks to replan, or the task list is stale (references removed ADRs, missing categories implied by the spec, or describes work already superseded in the repo).

---

## Step 1 — Compile walking-skeleton tasks

Use this step when there is no task list yet, or the user asked to replan.

### Model check

Ask the user whether they are on the right AI model for planning work and whether they want to switch to a more capable model before generating many tasks.

### Read inputs

1. Read every ADR under `specs/decisions/` in order (`ADR-0000` for constants, then `ADR-0001`, …).
2. Read `ADR-0000` and resolve documentation constants (`DEV_API_URL`, `DATABASE_SCHEMA`, `PROJECT_SLUG`, etc.) when writing task descriptions. Use literal values where copy-paste commands, config, or setup instructions need exact text.
3. Read the walking-skeleton spec (usually `specs/changes/walking-skeleton/spec.md`).
4. Note explicit constraints: monorepo layout, Docker/Make entrypoints, frameworks, databases, testing tools, quality gates, and anything the spec's acceptance scenarios require.

### Task categories

Group every task into exactly one category. Categories do not overlap — if work touches two concerns, split it into separate tasks.

| Category      | Description                                          | Example                                      |
|---------------|------------------------------------------------------|----------------------------------------------|
| structure     | Create project scaffolding and directories           | `.gitignore`, `apps/...`                     |
| setup         | Install software required to complete the tasks      | Docker, PHP, Composer, PostgreSQL            |
| modules       | Install apps, frameworks, and modules                | Symfony, React                               |
| dependencies  | Install libraries from package managers              | `composer.json`, `package.json`              |
| linting       | Install and configure linters and static analysis    | PHPStan, Prettier                            |
| testing       | Install and configure testing tools and harness only | PHPUnit config, test bootstrap, `make tests` |
| skeleton      | Implement walking-skeleton feature and its proof     | endpoints, migrations, spec acceptance tests |
| scripts       | Add developer quality-of-life scripts                | Makefile, `initdb.sql`                       |
| documentation | Add project documentation                            | README.md, AGENTS.md                         |
| cleanup       | Remove redundant and duplicated code and tools       | delete unused stubs, duplicate config        |

**Testing vs skeleton:** The **testing** category is for toolchain setup only (install PHPUnit, wire test env, add `make tests`). Spec acceptance tests that exercise implemented behavior belong in **skeleton**, because they require the feature to exist first.

**Cleanup timing:** Do not add **cleanup** tasks during initial Step 1 compilation. Add cleanup tasks when finishing a category or during the final **cleanup** pass in Step 2, once redundant artifacts actually exist.

**Documentation (always include):** Add a **documentation** section with at least one task even when no ADR mentions README, AGENTS, or onboarding docs. Derive scope from ADRs, the walking-skeleton spec, and project entrypoints (Makefile targets, Docker Compose, package scripts). Typical artifacts: root `README.md` (prerequisites, bootstrap, run, test, lint commands) and `AGENTS.md` when the repo uses agent-oriented conventions. Documentation tasks do not require an `[ADR-XXXX]` tag.

**Ordering inside each category:** follow ADR order by default. Reorder when a later ADR clearly depends on an earlier task that would otherwise run too late.

**Task IDs:** assign sequential IDs `T01`, `T02`, … across the whole file (not per category).

**Section headers:** Use Title Case category names as `##` headers (`## Structure`, `## Setup`, …). Include only categories that have at least one task — omit empty sections.

### Write `tasks.md`

Create `specs/changes/walking-skeleton/tasks.md` unless the project uses OpenSpec, Spec Kit, or another configured convention — then follow that convention and tell the user which path you used.

Use this structure:

```markdown
# Walking Skeleton tasks:

## Structure

- [ ] [T01] [ADR-0001] Create monorepo directories `apps/api` and `packages/core` with root `.gitignore`

## Setup

- [ ] [T02] [ADR-0002] Add Docker Compose services for PHP, nginx, and PostgreSQL

## Documentation

- [ ] [T03] Add root `README.md` with prerequisites, bootstrap, and `make` / Docker entrypoints from ADR-0000 constants
```

**Task line format:**

```markdown
- [ ] [TaskID] [ADR-XXXX] Description
```

For **documentation** tasks only, omit the ADR tag:

```markdown
- [ ] [TaskID] Description
```

Rules:

- `[ADR-XXXX]` references the output ADR id from `specs/decisions/`. Use multiple tags when a task implements several ADRs: `[ADR-0001] [ADR-0005]`. Omit ADR tags for **documentation** tasks.
- Descriptions are imperative, concrete, and immediately executable. Resolve `ADR-0000` constants to literal values where tasks need exact hostnames, schemas, paths, or commands.
- Walking-skeleton behaviors and their automated proof (e.g. PHPUnit create-then-list) belong in **skeleton**, not **testing**.
- Do not add TDD-first or OpenSpec/Spec Kit workflow tasks unless the user later opts in.
- Do not add **cleanup** tasks in Step 1.

### Consistency review

Before asking for approval, check:

- Every selected ADR has at least one task (except `ADR-0000`, which informs constants referenced by other tasks).
- **documentation** has at least one task covering onboarding docs (typically `README.md`), even when no ADR mandates documentation.
- Every in-scope acceptance scenario in the walking-skeleton spec maps to one or more **skeleton** tasks.
- Spec acceptance tests are in **skeleton**, not **testing**; **testing** covers toolchain setup only.
- No task is vague ("set up backend", "add tests") — replace with commands, paths, and expected artifacts.
- No task spans categories without being split.
- **setup** vs **modules** vs **dependencies**: setup = host/tooling prerequisites; modules = frameworks/apps; dependencies = package-manager libraries.
- No empty category sections appear in `tasks.md`.
- Step 1 output contains no **cleanup** tasks.

### Present for approval

Show a table of task counts per category, the total task count, and the output path. Ask the user to approve or suggest edits. Revise until they confirm.

Do not start **Step 2** until the user explicitly approves the task list.

---

## Step 2 — Implement tasks (category loop, task loop)

Use this step when `tasks.md` has incomplete tasks.

### Model check

Ask the user whether they are on the right AI model for implementation and whether they want a simpler/faster model for repetitive work.

If every task is already `- [x]`, skip to **Step 3**.

### Implementation loop

Use a **double loop**: an outer loop over categories, and an inner loop over incomplete tasks within each category.

Process categories in this order unless `tasks.md` already implies a different safe order:

1. structure
2. setup
3. modules
4. dependencies
5. linting
6. testing — toolchain only (PHPUnit install/config, test bootstrap, `make tests` wiring); no spec acceptance tests here
7. skeleton — feature implementation and spec acceptance tests
8. scripts
9. documentation — README, AGENTS, and other onboarding docs; no ADR tag required
10. cleanup — add cleanup tasks here if not already present, then execute them

Skip categories with no incomplete tasks (`- [ ]`).

#### Outer loop — per category

For **each category** with pending work:

1. **Clear context** before starting the category. Do not carry forward prior category notes, partial plans, or unrelated ADR/spec excerpts.
2. **Load only what this category needs:**
   - The incomplete tasks under that category's section in `tasks.md` (top to bottom).
   - The ADR(s) referenced by those tasks (from `specs/decisions/`). For **documentation**, read ADR-0000, entrypoint ADRs (Makefile, Docker, scripts), and spec acceptance passages — documentation tasks may have no ADR tags.
3. Resolve `ADR-0000` constants only when a task in this category needs literal values.
4. Read spec passages only when a task in this category requires acceptance or behavioral detail not covered by its ADR(s).
5. Run the inner loop below for every incomplete task in the category.
6. When the category is complete, follow **After each category** before advancing to the next category.

#### Inner loop — per task in the category

For **each incomplete task** in the current category:

1. **Announce** the task id, category, and description.
2. **Implement** the task in the repository.
3. **Mark complete** in `tasks.md`: change `- [ ]` to `- [x]`.
4. **Pause for user input** when:
   - the ADR and repo state conflict,
   - the spec is ambiguous for this task,
   - you see a materially better approach,
   - or the task fails and needs a decision.

Do not batch-mark tasks complete without doing the work.

### After each category

When all tasks in the current category are `- [x]`:

1. Verify each task's artifact exists and matches its ADR/spec intent.
2. Remove unused files, dead code, or duplicate config introduced while working; add **cleanup** tasks to `tasks.md` when needed.
3. Refactor obvious duplication surfaced during the category implementation.
4. Ask the user to validate the completed category.
5. Announce the next pending category, or proceed to the final review below.

### After all tasks complete

Summarize what was built, list any open questions, and ask the user for approval or improvements. When they confirm, continue to **Step 3**.

---

## Step 3 — Verify clean rebuild

Use this step when every task in `tasks.md` is marked complete.

Goal: prove the stack can be recreated from scratch and passes quality gates. Do not rely on context from Steps 1 or 2 — inspect the repo and run the verification sequence below.

Skip steps that do not apply to the stack. Prefer project scripts when they exist (Makefile targets, `composer install`, `docker compose`, etc.).

Verification sequence:

1. **Clear installed artifacts** — stop and remove Docker containers, volumes, and installed vendor/node/build artifacts as appropriate for the stack.
2. **Rebuild the stack** — rebuild containers and reinstall dependencies using the project's documented entrypoints.
3. **Run analysis and tests** — run configured linters, static analysis tools, and the full test suite.

Report pass/fail for each command with concise output. If something fails, fix it in the repo and re-run verification from step 1.

Ask the user whether they are happy with the outcome. When they approve, the walking skeleton is complete.

---

## What not to do unless the user opts in

- Do not require red/green TDD cycles or test-first implementation by default.
- Do not invoke Spec Kit, OpenSpec, or similar change-management ceremonies for implementation tracking — use `tasks.md` (or the project's existing task file) instead.
- Do not expand scope beyond the walking-skeleton spec and selected ADRs.
- Do not skip prerequisite checks even if the repository already contains partial code.

---

## Completion checklist

Before finishing, verify:

- [ ] `specs/decisions/` contained ADRs before building.
- [ ] A walking-skeleton spec existed and was read.
- [ ] `specs/changes/walking-skeleton/tasks.md` (or project convention path) lists every task with id, ADR tag (when applicable), and checkbox state.
- [ ] **documentation** tasks were planned and completed even when no ADR covers onboarding docs.
- [ ] Tasks were implemented category by category with user validation at category boundaries.
- [ ] Walking-skeleton acceptance behavior from the spec works.
- [ ] Linting and tests pass after a clean rebuild (Step 3), or the user explicitly accepted known gaps.
- [ ] The user approved the final result.

## Communication style

Be direct and incremental. During Step 1, optimize for a clear task ledger the user can scan. During Step 2, narrate which task you are on and what changed on disk. During Step 3, show the exact commands run and whether each succeeded. Prefer tables and short checklists over long prose.
