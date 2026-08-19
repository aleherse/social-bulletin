<!--
SYNC IMPACT REPORT
==================
Version change: 2.1.0 → 2.2.0
Bump rationale: MINOR — a new principle (V) was added. No existing principle was
removed, renamed, or renumbered, and nothing previously permitted by principles
I–IV became forbidden.

Principles added:
  V. Code Explains Itself; Comments Are Exceptional — comments that restate
     readable code are noise and MUST NOT be written; a comment is warranted
     only when it carries what the code cannot (a why, an external constraint,
     a deliberate deviation, a non-local consequence). Machine-read annotations
     (PHPStan/PHPDoc types, `@throws`, justified suppressions) are exempt.

Current principles:
  I.   Tests Are First-Class Citizens (NON-NEGOTIABLE)
  II.  Every Layer Is Tested In Its Own Tool
  III. Decisions Are Recorded Before They Are Coded
  IV.  Automated Gates Over Human Vigilance
  V.   Code Explains Itself; Comments Are Exceptional

Sections added: none
Sections removed: none

Templates requiring updates:
  ✅ .claude/skills/speckit-implement/SKILL.md — implementation execution rules
     now state the comment discipline that applies while code is written
  ✅ .specify/templates/plan-template.md     — no change; Principle V governs code,
     not planning artifacts, so it adds no plan-stage gate
  ✅ .specify/templates/tasks-template.md    — no change; comment discipline is a
     property of every implementation task, not a task of its own
  ✅ .specify/templates/spec-template.md     — no change; specs contain no code
  ✅ .claude/skills/speckit-analyze/SKILL.md — no change; analyze inspects
     spec/plan/tasks artifacts, and Principle V is checked in code review
  ✅ AGENTS.md (CLAUDE.md symlink)           — no change; already defers to this
     file for principles and to docs/rules/ for coding rules

Standing note (from 2.0.0): dropping the original Principle III did not delete
the rule from the project. The hexagonal boundary remains specified by ADR-0005
and docs/engineering/backend/hexagonal.md, and stays mechanically enforced by
deptrac via `make lint`. It simply no longer carries constitutional force.

Deferred items:
  - A replacement architectural principle is expected in a future amendment.
    Until it lands, architecture questions defer to the ADRs.
  - Principle V is enforced by review, not by a linter. If a mechanical check
    proves feasible, it belongs under Principle IV as an automated gate.

--- History ---
1.0.0 (2026-08-09): Initial ratification; five principles adopted.
2.0.0 (2026-08-09): Principle III removed; IV and V renumbered.
2.1.0 (2026-08-09): Principle II expanded with the test pyramid rule.
2.2.0 (2026-08-19): Principle V added — comment discipline.
-->

# Social Bulletin Constitution

## Core Principles

### I. Tests Are First-Class Citizens (NON-NEGOTIABLE)

Tests are deliverables, not follow-up work. They are planned, written, and
verified at every stage of the Spec Kit process — never bolted on afterwards
and never silently skipped.

- Every feature specification MUST express its acceptance scenarios in terms a
  test can assert.
- Every plan MUST state which test layers the feature touches.
- Every `tasks.md` MUST contain explicit test tasks, written before the
  implementation tasks they cover, for each layer the feature touches.
- Test tasks MUST be written first and MUST be observed failing before the
  implementing code is written.
- A task, story, or feature MUST NOT be marked complete while a layer it
  touches is untested. Omitting a layer is permitted only as an explicit,
  written waiver in `tasks.md` naming the layer and the reason.
- "The suite is green" is evidence only when the suite actually exercises the
  change. Adding a code path without adding coverage for it is a defect, even
  when every existing test passes.

**Rationale**: A passing suite that does not exercise the new code reads as
safety while providing none. This project shipped a full feature — three user
stories, backend and frontend — with `make tests` green and zero browser
coverage of it, because no task ever asked for that coverage. The gap was in
the process, not in anyone's diligence, so the process is where it is fixed.

### II. Every Layer Is Tested In Its Own Tool

ADR-0015 assigns one tool per layer, and a change MUST be tested in the tool
that owns the layer it changes:

| Layer changed                        | Tool                     | Location                |
|--------------------------------------|--------------------------|-------------------------|
| `packages/core` domain logic         | PHPSpec                  | `packages/core/spec/`   |
| `apps/api` HTTP behaviour            | Behat + JMESPath         | `apps/api/features/`    |
| `apps/web` components, hooks, pures  | Vitest + Testing Library | next to the source      |
| End-to-end user journeys             | Playwright               | `apps/web/e2e/`         |

- A feature that changes more than one layer MUST be tested in each layer it
  changes; passing coverage in a neighbouring layer is not a substitute.
- Component and journey assertions MUST use accessible queries (role, label,
  visible text), never CSS selectors or test-only attributes.

**The pyramid decides *which* case goes *where*.** Layer coverage says a layer
is exercised; it does not license testing every case at every level.

- **Error, edge, and validation cases** MUST be tested at the **lowest layer
  that can prove them**, and MUST NOT be repeated higher up. A rule enforced in
  the domain is proven by PHPSpec; that same rejection does not need a Behat
  scenario, a Vitest case, and a Playwright journey as well. Duplicating it
  buys no confidence and costs a slow test that breaks on unrelated changes.
- **Happy paths MUST be covered up the stack**, including end to end. Every
  user story in a spec MUST have a Playwright journey walking its successful
  path, because that is the only layer proving the compiled frontend, nginx,
  and the real API agree.
- Push a case higher **only when the higher layer is the lowest one that can
  prove it**: an HTTP status mapping, a serialization shape, a redirect, an
  authorization boundary spanning session and route — these are not domain
  facts, so their tests belong where the behaviour lives.

**Rationale**: Each tool proves something the others cannot. PHPSpec cannot
prove nginx serves the bundle; Vitest cannot prove the API contract holds.
Coverage in one layer routinely masquerades as coverage of the feature — but
the opposite failure is just as real: an end-to-end suite that re-litigates
every validation rule is slow, brittle, and buries the journeys that matter.
Test the rule once, low; test the journey end to end.

### III. Decisions Are Recorded Before They Are Coded

- Structural changes MUST be checked against `docs/decisions/` first; diverging
  from an accepted ADR requires a new ADR, not a quiet exception.
- Before writing code in an area, the matching `docs/rules/<category>/` file
  MUST be read and followed.
- A decision worth repeating — a review correction, a convention set by a diff —
  MUST be distilled into `docs/rules/` rather than left for the next
  contributor to rediscover.
- Specification prose under `specs/` and `docs/` MUST use semantic line breaks
  so single-word edits produce single-line diffs.

**Rationale**: Undocumented conventions are relearned by every contributor, and
agents rediscover them by guessing. Writing them down once is cheaper than
enforcing them forever in review.

### IV. Automated Gates Over Human Vigilance

- `make` targets are the only supported entrypoints for build, test, and lint;
  every check MUST be runnable through one.
- Lefthook gates MUST stay honest to their cost budget (ADR-0013): `pre-commit`
  fast checks only, `commit-msg` Conventional Commits, `pre-push` medium-cost
  checks. Full API and E2E suites MUST NOT run in hooks.
- Database state for tests MUST come from the DSLR `fixtures` snapshot. Behat
  and Playwright restore it per scenario; test runs MUST NOT recreate it.
- Commit messages MUST follow Conventional Commits.

**Rationale**: Gates a human has to remember are gates that fail on the busy
day. Anything worth checking is worth automating.

### V. Code Explains Itself; Comments Are Exceptional

Readable code needs no narration. A comment that restates what the code already
says is noise: it doubles the reading cost, drifts out of date without failing a
single test, and buries the few comments that genuinely carry information.
Naming and structure are the primary tools for clarity; a comment is what is
left when they cannot carry the meaning.

- Clarity MUST be pursued in the code first — rename, extract, restructure. A
  comment MUST NOT stand in for a name that could have been clearer or a
  function that could have been smaller.
- Comments MUST NOT restate the code, and MUST NOT repeat what a signature,
  a type, or a test already states. "Constructor", "loop over the movements",
  and "returns the user" are all deletions.
- A comment is warranted only when it carries what the code cannot: why a
  non-obvious choice was made, a constraint imposed from outside the file, a
  deliberate deviation, or a consequence that is not visible locally. For
  example `apps/web/src/pages/home/model/email.ts` opens with "Lightweight
  pre-submit check; the API remains the validation authority" — that ownership
  boundary is nowhere in the code.
- Machine-read annotations are not prose and are exempt: the PHPDoc types static
  analysis needs (`@param array<string, mixed>`, `@var array{…}`), `@throws`, and
  tool suppressions. Every suppression MUST state its reason, the way
  `@phpstan-ignore property.uninitializedReadonly` in
  `packages/core/src/Application/Movement/UpdateMovementCommand.php` names the
  payload case that leaves the property unassigned.
- Commented-out code MUST NOT be committed. Version control already keeps it.
- Comments MUST be kept true. A comment contradicted by the code it sits above
  MUST be corrected or deleted in the same change, never left to rot.

**Rationale**: A wrong comment is worse than no comment, because it is believed.
The cheapest way to avoid wrong comments is to write few of them and make each
one earn its line — and the reader who has learned that every comment here says
something the code could not will actually read them.

## Testing Standards

These are the operational rules that make Principle I checkable.

**Fixtures**: `apps/api/features/fixtures.feature` holds only data that is
genuinely reusable across unrelated scenarios. Scenario-specific state belongs
in that scenario's own `Given` steps. `Given` steps MUST create data through
application code, never raw SQL.

**Assertions**: Behat `Then` steps use JMESPath against the JSON response.
Tests MUST NOT assert on backend message wording where that wording is a
translation key or subject to i18n; assert the error is surfaced and which
field it belongs to instead.

**Determinism**: Playwright runs single-worker against a restored snapshot and
MUST NOT depend on ordering between scenarios. `make web-e2e` builds the
frontend first so journeys always run against the compiled bundle nginx serves.

**Suite entrypoints**: `make php-unit`, `make api-tests`, `make web-unit`,
`make web-e2e`; `make tests` runs all four. Any document listing "how to run the
tests" MUST list all four or explain the omission.

## Development Workflow & Quality Gates

Tests are considered at each Spec Kit stage. Each stage carries an obligation:

| Stage             | Test obligation                                                        |
|-------------------|------------------------------------------------------------------------|
| `/speckit-specify`| Acceptance scenarios stated so a test can assert them                  |
| `/speckit-plan`   | Name the layers touched and the tool that will cover each              |
| `/speckit-tasks`  | Emit explicit test tasks per layer, ordered before their implementation |
| `/speckit-implement` | Write tests first, observe them fail, then implement                |
| `/speckit-analyze`| Flag any layer touched by the plan with no corresponding test task     |

Before a feature branch merges to `main`:

- `make lint` and `make tests` MUST both pass.
- The PR checklist (ADR-0013) MUST reflect which suites ran.
- Every user story in the spec MUST be traceable to at least one test that
  fails if the story regresses.

## Governance

This constitution supersedes conflicting practice elsewhere in the repository.
Where it disagrees with an ADR, the more recently dated document wins and the
older one MUST be updated or superseded rather than left contradictory.

**Amendment procedure**: Amendments are made through `/speckit-constitution`,
which MUST update this file, propagate the change to
`.specify/templates/*.md`, and record a Sync Impact Report at the top of this
file. Amendments touching a principle MUST state their rationale in the
principle itself.

**Versioning policy**: Semantic versioning applies to this document.
MAJOR for removing or redefining a principle in a backward-incompatible way,
MINOR for adding a principle or materially expanding guidance,
PATCH for clarifications and wording that do not change obligations.

**Compliance review**: `/speckit-analyze` checks feature artifacts against these
principles. Reviewers MUST verify compliance on every PR. Complexity that
violates a principle MUST be recorded in the plan's Complexity Tracking table
with the simpler alternative that was rejected and why — an unjustified
violation blocks merge.

**Version**: 2.2.0 | **Ratified**: 2026-08-09 | **Last Amended**: 2026-08-19
