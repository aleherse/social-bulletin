# Skeleton Spec

Interactive workflow for turning the project's ADR decisions into a meaningful walking-skeleton specification. The output is a reviewed spec for the first tiny end-to-end slice, usually at `specs/changes/walking-skeleton/spec.md` unless the project is configured for Spec Kit, OpenSpec, or a similar specification tool.

A Walking Skeleton is a tiny implementation of the system that performs a small end-to-end function. It need not use the final architecture, but it should link together the main architectural components. The architecture and the functionality can then evolve in parallel. — Alistair Cockburn

## Principles

- **Gate on ADRs.** A walking skeleton should reflect actual architectural decisions. If ADRs are missing, stop and direct the user to `/walking-skeleton plan`.
- **Do not overwrite build-ready work silently.** If a walking-skeleton spec already exists, stop and ask whether the user wants to build it with `/walking-skeleton build` or intentionally restart the spec.
- **Review before writing.** Help the user sharpen the desired end-to-end behavior before creating files.
- **Tie behavior to architecture.** Use the ADRs to identify the components the skeleton should connect: frontend, API, database, authentication, packaging, deployment/dev environment, testing, etc.
- **Keep the slice tiny.** Prefer the smallest end-to-end behavior that proves the key seams. Avoid turning the walking skeleton into a full feature backlog.
- **One step at a time.** Complete each step and get clear user confirmation before advancing.

## Step 1 — Check project readiness

Run these checks in the current project root before asking for the walking-skeleton behavior.

### 1. Confirm ADRs exist

1. Check whether `specs/decisions/` exists.
2. Look for at least one ADR document, matching common names such as:
   - `specs/decisions/ADR-*.md`
   - `specs/decisions/*adr*.md`
   - `specs/decisions/*.md` where the file contains an ADR-style title or sections like `Context`, `Decision`, or `Consequences`
3. If no ADR document is found, stop. Tell the user:
   - `specs/decisions/` does not contain project ADRs yet.
   - They should run `/walking-skeleton plan` first.
   - This command will continue only after ADRs exist.

Do not create a walking-skeleton spec without ADRs unless the user explicitly changes the task and accepts that it is outside this workflow.

### 2. Check for an existing walking-skeleton spec

Look for an existing walking-skeleton change/spec before continuing. Check at least:

- `specs/changes/walking-skeleton/spec.md`
- `specs/changes/walking-skeleton/**/spec.md`
- `specs/changes/walking-skeleton/**/*.md`
- OpenSpec-style paths such as `specs/changes/walking-skeleton/specs/**/spec.md`
- Spec Kit or similar configured paths when project files indicate them, such as `.specify/`, `.openspec/`, `openspec.json`, `specify` templates, or existing `specs/changes/` conventions

If a matching file exists, summarize the path(s) and ask the user to choose:

1. **Build existing spec** — direct them to `/walking-skeleton build` and stop.
2. **Start again** — continue to Step 2, but first agree what to do with the old spec: overwrite it, archive/rename it, or write a new change ID.

Do not delete or overwrite existing spec files without explicit user confirmation.

### 3. Read the ADRs for context

If ADRs exist and no blocking spec exists, read the ADRs under `specs/decisions/`. Build a short architecture summary for yourself:

- project type and main runtime components
- frontend/backend/API boundaries
- persistence and authentication decisions
- local development and routing decisions
- testing expectations
- any ADR constraints the walking skeleton should respect

Use this summary in Step 2. You do not need to show every ADR detail unless it helps the user decide.

## Step 2 — Elicit and review the walking-skeleton behavior

Ask the user for the walking-skeleton specification. Include this concise explanation if they have not already provided one:

> A walking skeleton is the smallest useful end-to-end behavior that connects the main architectural pieces. It can be simple, but it should prove the seams between browser/UI, API/application code, persistence, authentication/session handling, routing, and tests where those exist in the ADRs.

Offer a concrete example when useful, such as minimum registration-or-login:

```markdown
Adopt a minimum registration-or-login walking skeleton.

When the current browser user is not authenticated, the homepage renders a registration form that asks only for an email address.
When submitted, the frontend sends the email address to the API.
The API checks whether a user with that email already exists.
If the user exists, the API issues a JWT using the configured cookie-based mechanism.
If the user does not exist, the API creates the user first, then issues a JWT using the same mechanism.
After successful submission, the frontend shows an authenticated hello view greeting the user by email.
On page load, the frontend checks the API for an existing authenticated user and shows either the hello view or the registration form.
If the user is logged in, a logout link is shown; clicking it clears the JWT cookie and returns to the registration form.
```

### Review loop

Loop until the user says the specification is ready to write.

For each draft the user gives, evaluate whether it is enough to generate a meaningful walking skeleton given the ADRs. Prefer concise, actionable feedback:

1. **Restate the proposed slice** in one or two sentences.
2. **Check architectural coverage** against the ADR summary. Note which major components it proves and which it skips.
3. **Identify missing behavior** that would block implementation, such as:
   - entry route or homepage behavior
   - user-visible success/failure states
   - API endpoints and request/response shape
   - persistence rule or seed data expectation
   - authentication/session behavior
   - page-load or refresh behavior
   - logout or reset behavior when authentication is involved
   - validation and error handling for the tiny slice
   - minimal automated tests expected by the ADRs
4. **Propose improvements** as small additions, not a sprawling feature list.
5. Ask whether to revise again or proceed to writing.

Use the ADRs as grounding. For example:

- If ADRs include a browser frontend and API, the skeleton should usually cross that boundary.
- If ADRs include a database, the skeleton should usually persist or read at least one record.
- If ADRs include authentication, the skeleton may be a login/register/hello flow, but another tiny authenticated behavior can also work.
- If ADRs defer authentication, do not force auth into the skeleton; choose a smaller domain slice that still proves UI/API/persistence.
- If ADRs include testing decisions, ask for or propose at least the most important acceptance checks.

## Step 3 — Write the specification

After the user confirms the behavior is ready, write the spec using the project's configured specification tooling when present. Otherwise, write a plain Markdown spec at:

`specs/changes/walking-skeleton/spec.md`

### Detect project spec tooling

Before writing, inspect the repository for existing conventions:

- OpenSpec: `.openspec/`, `openspec.json`, `specs/changes/*/proposal.md`, `specs/changes/*/tasks.md`, `specs/changes/*/specs/**/spec.md`, or references to `openspec` in package/config files.
- Spec Kit: `.specify/`, `specify` templates/scripts, `.specify/specs/`, or project docs that define a spec workflow.
- Similar tools: existing `specs/changes/` directories, templates, scripts, or docs that prescribe spec filenames and sections.

If a tool is clearly configured, follow the existing project convention and tell the user which convention you used. If unsure, ask a brief clarification instead of inventing a tool-specific format.

### Plain Markdown fallback format

When no tool-specific convention exists, create `specs/changes/walking-skeleton/spec.md` with this structure:

```markdown
# Walking Skeleton Specification

## Summary
[One-paragraph summary of the tiny end-to-end slice.]

## Architectural context
- [ADR-derived component or constraint]
- [ADR-derived component or constraint]

## Scope
### In scope
- [Small behavior included]

### Out of scope
- [Explicitly deferred behavior]

## Actors
- [Actor/system]

## Acceptance scenarios
### Scenario 1: [Name]
GIVEN [initial state]
WHEN [action]
THEN [observable outcome]
AND [additional observable outcome]

### Scenario 2: [Name]
GIVEN ...
WHEN ...
THEN ...

## Interface notes
- [Route, endpoint, request/response, cookie/session, or data notes needed for implementation]

## Data notes
- [Minimal data model/persistence expectations]

## Test expectations
- [Small set of tests that prove the skeleton works]

## Open questions
- [Only unresolved questions the user intentionally left open]
```

The acceptance scenarios should use `GIVEN`, `WHEN`, `THEN` wording. Keep them implementation-guiding but not over-specified. The next command, `/walking-skeleton build`, should be able to implement from this file plus the ADRs.

### After writing

1. Show the file path(s) written.
2. Summarize the spec in a short checklist.
3. Ask the user for feedback.
4. If the user requests changes, edit the spec and ask again.
5. When the user confirms the spec is good, finish and suggest `/walking-skeleton build` as the next step.

## Completion checklist

Before finishing, verify:

- [ ] `specs/decisions/` contains at least one ADR document.
- [ ] Existing walking-skeleton specs were not overwritten without explicit confirmation.
- [ ] The reviewed walking-skeleton behavior connects the main ADR-selected components where practical.
- [ ] The spec is written using configured Spec Kit/OpenSpec/similar conventions, or falls back to `specs/changes/walking-skeleton/spec.md`.
- [ ] Plain fallback specs use `GIVEN`, `WHEN`, `THEN` acceptance scenarios.
- [ ] The user reviewed the written spec and confirmed it is ready.

## Communication style

Be direct and collaborative. Keep the review loop moving: identify the smallest missing pieces, propose a concrete revision, and ask whether to proceed. Avoid broad product discovery unless the user's draft is too vague to guide implementation.
