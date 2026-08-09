<!--
SYNC IMPACT REPORT
==================
Version change: 1.0.0 → 2.0.0
Bump rationale: MAJOR — a principle was removed. The versioning policy below
reserves MAJOR for removing or redefining a principle, and removal is
backward incompatible for anything that cited the old numbering.

Principles removed:
  III. Hexagonal Core, Frameworks At The Edges — withdrawn at the author's
       request, pending a better formulation in a later amendment.

Principles renumbered:
  IV. Decisions Are Recorded Before They Are Coded → III
  V.  Automated Gates Over Human Vigilance        → IV

Current principles:
  I.   Tests Are First-Class Citizens (NON-NEGOTIABLE)
  II.  Every Layer Is Tested In Its Own Tool
  III. Decisions Are Recorded Before They Are Coded
  IV.  Automated Gates Over Human Vigilance

Sections added: none (unchanged since 1.0.0)
Sections removed: none

Templates requiring updates:
  ✅ .specify/templates/plan-template.md   — dropped the hexagonal gate,
     renumbered the remaining Constitution Check gates
  ✅ .specify/templates/tasks-template.md  — no change; cites Principle I only
  ✅ .specify/templates/spec-template.md   — no change; cites Principle I only
  ✅ .claude/skills/speckit-analyze/SKILL.md — no change; cites Principle I only
  ✅ AGENTS.md (CLAUDE.md symlink)         — no change; cites no principle by number

Note: dropping the principle does not delete the rule from the project. The
hexagonal boundary remains specified by ADR-0005 and
docs/engineering/backend/hexagonal.md, and stays mechanically enforced by
deptrac via `make lint`. It simply no longer carries constitutional force.

Deferred items:
  - A replacement architectural principle is expected in a future amendment.
    Until it lands, architecture questions defer to the ADRs.

--- History ---
1.0.0 (2026-08-09): Initial ratification; five principles adopted.
2.0.0 (2026-08-09): Principle III removed; IV and V renumbered.
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
- Every user-facing journey described in a spec's user stories MUST have a
  Playwright journey, because that is the only layer that exercises the
  compiled frontend against the real API.
- Component and journey assertions MUST use accessible queries (role, label,
  visible text), never CSS selectors or test-only attributes.

**Rationale**: Each tool proves something the others cannot. PHPSpec cannot
prove nginx serves the bundle; Vitest cannot prove the API contract holds.
Coverage in one layer routinely masquerades as coverage of the feature.

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

**Version**: 2.0.0 | **Ratified**: 2026-08-09 | **Last Amended**: 2026-08-09
