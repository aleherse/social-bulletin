# FRAMEWORK.md

## Purpose

This framework defines the core workflow, specification, and agent-routing rules for repositories that adopt it.

Optional standards are composed at install time so teams can keep the shared delivery model while choosing the engineering standards they actually want enforced.

When tradeoffs appear, prefer maintainability, correctness, and explicit intent over hidden conventions or tool-driven shortcuts.

## Core Workflow Constitution

The following rules apply to every installation profile:

- `specs/features/` is canonical and versioned.
- `specs/changes/` is optional working state and is not versioned.
- Any workflow mode that changes observable system behavior MUST end with an update to `specs/features/`.
- Use the `commit-changes` skill to organise working tree changes into atomic, functional, and semantic commits using the [Conventional Commits](https://www.conventionalcommits.org/) format.
- Before starting any non-trivial change, the agent MUST act as a routing meta-agent and ask the user how to proceed.
- Optional standards are enabled through `.aircury/framework.config.json`.

## Installed Capabilities

- `open-spec` — Structured propose/apply/complete workflow for complex changes
- `spec-kit` — Formal specification workflow for feature definition and delivery
- `git` — Focused git workflow helpers for atomic commits
- `decision-records` — Requires agents to capture material architectural and workflow decisions in ADRs under specs/decisions/.
- `testing` — Testing standards plus curated Playwright and E2E testing skills
- `code-style` — Automatically detects and follows project-specific linting and parsing rules by analysing package.json and config files.
- `frontend` — Frontend standards with layout, experience, and UI generation skills
- `token-efficiency` — Project token-efficiency rules plus the Caveman skill for terse responses
- `resilience` — Error-handling and structured-logging standards with curated resilience skills
- `specs` — Skills for extracting authoritative specs and designing re-implementations from them
- `language` — UK business English guidance for project communication

## Architecture Decision Records

This installation uses ADRs to preserve architectural and workflow intent over time.

## ADR Rules

- Store ADRs under `specs/decisions/`.
- Create or update an ADR when a task introduces, changes, or supersedes a material architectural or workflow decision.
- Read relevant ADRs before implementing work in an area governed by prior decisions.
- Do not silently rewrite history when direction changes. Create a new ADR that references the superseded decision.

## ADR Dual-Write to Airsync

Every ADR created or superseded MUST also be proposed to Airsync as a team-scoped memory. This ensures that architectural decisions are discoverable by agents across all projects using Airsync, not just the current repository.

When proposing an ADR to Airsync:

- Use `memory_kind: "note"`
- Use `scope: "team"`
- Include tags: `["adr", "ADR-XXXX"]` (replace XXXX with the ADR number)
- Include `source_refs` pointing to the ADR file path
- Copy the ADR's Context, Decision, and Consections as the memory content

## ADR Template

```md
# ADR-XXXX: <decision title>

- Status: Proposed | Accepted | Superseded
- Date: YYYY-MM-DD
- Supersedes: ADR-XXXX (optional)

## Context
<why this decision is needed>

## Decision
<what was decided>

## Consequences
<tradeoffs, follow-ups, and constraints>
```

## Testing Strategy

Automated tests are required for behaviour that matters. The test suite should be fast at the bottom, realistic at the boundaries, and selective with full end-to-end coverage.

### TDD Workflow

Testing includes a red -> green -> refactor workflow by default.

Work in vertical slices:

1. Write one failing test for one observable behavior.
2. Implement the minimum code needed to pass.
3. Refactor while keeping tests green.
4. Repeat.

Do not batch all tests first. Do not batch all implementation first.

### Coverage Model

- Write unit tests for domain logic, pure functions, transformations, policies, and other isolated behaviour with meaningful branching.
- Write integration tests for application use cases, persistence adapters, HTTP handlers, messaging flows, and other boundary-crossing behaviour.
- Write end-to-end tests only for critical user journeys, production wiring, and regressions that cannot be trusted at lower levels alone.
- Prefer a balanced test pyramid over a top-heavy suite of slow UI tests.

Ratio guide for a healthy suite:

- Unit: roughly 70%
- Integration: roughly 20%
- E2E: roughly 10%

Treat these as steering ratios, not coverage gates.

### Frontend Defaults

When the project has a frontend, prefer this default toolchain unless the repository already standardises on something else:

- **Vitest** for unit and component-level execution.
- **Testing Library** for behaviour-driven UI tests through accessible queries.
- **Playwright** for browser-level end-to-end coverage.

Frontend testing rules:

- Test user-observable behaviour, not component internals.
- Prefer Testing Library queries by role, label, and visible text before falling back to test IDs.
- Keep Playwright focused on high-value journeys such as authentication, checkout, onboarding, critical CRUD flows, or cross-page regressions.
- Avoid large snapshot suites with low signal.

### Backend Defaults

Backend services must always include:

- **Unit tests** for domain behaviour and isolated business rules.
- **Integration tests** for adapters, data access, transport layers, and boundary contracts.

Language and framework-specific tools may vary by repository, but these expectations do not.

### Test Quality Rules

- Structure tests with a clear Given-When-Then or Arrange-Act-Assert flow.
- Name tests by observable outcome, ideally in a `should ... when ...` style.
- Prefer real collaborators inside the boundary under test and mock only true external systems or uncontrollable side effects.
- Keep each test isolated and independent. No test may depend on another test's execution order or data.
- Keep fixtures small and intention-revealing.
- Prefer deterministic tests with controlled inputs, clocks, randomness, and network boundaries.
- Avoid sleep-based timing assertions and other timeout-driven checks unless time is the behaviour under test.
- Make regressions reproducible with a focused failing test before fixing the bug.
- Keep the fast-feedback layer fast enough to run on every commit locally and in CI.
- Ensure tests can run reliably in CI without hidden local prerequisites.
- Do not ship features that only have manual verification when automated coverage is feasible.

## Code Style

### Linting and Parsing

Ensure code consistency by adhering to the project's configured tools.

- Analyze `package.json` to identify the linter and parser used (e.g., Biome, ESLint, Prettier, Oxlint).
- Check `devDependencies` and `dependencies` for tool-specific packages.
- Locate configuration files (`.eslintrc.*`, `prettier.config.js`, `biome.json`, `oxlint.json`).
- If a tool is detected, follow its specific rules and formatting conventions.
- If multiple tools are present (e.g., ESLint and Prettier), prioritise the one that handles the relevant concern (e.g., Prettier for formatting, ESLint for logic).
- If no tool is explicitly configured, default to industry standards for the project's language (e.g., StandardJS or AirBnB for JS/TS).

## 1. Module Purpose

Activate this module when the project has an existing frontend. Analyze the code before modifying the UI. Replicate and extend the UI with strict fidelity to the project's real design system. Do not assume rules about styles, components, or visual behaviors without this module active.

## 2. Analysis Pipeline

Execute this workflow to replicate or extend a UI module.

### Phase 1 — Structural Extraction (Layout)

Use the `frontend-layout-extractor` skill to analyze the source code at the target location.

- **Goal**: Produce a `layout.md` file that captures every field, label, and static element with "full field parity".
- **Constraint**: This phase must ignore all styling and complex orchestration logic.
- **Output**: `specs/features/<feature-name>/layout.md`.

### Phase 2 — Behavioral Extraction (Experience)

Use the `frontend-experience-extractor` skill to analyze the same source code.

- **Goal**: Produce an `experience.md` file that captures user flows, micro-interactions, state transitions, validation feedback, and conditional visibility or authorization logic.
- **Constraint**: This phase focuses on "how it feels" and the behavioral logic, including who sees what and when, while `layout.md` remains the source of structural field parity.
- **Output**: `specs/features/<feature-name>/experience.md`.

### Phase 3 — Visual Implementation (UI)

Use the `frontend-ui-generator` skill to build the interface based on both the `layout.md` and `experience.md` files, ensuring strict adherence to the project's design system.

- **Style Guide**: Ensure `specs/ui/style-guide.md` is updated with current tokens.
- **Implementation**: Replicate the exact structure and behavior.
- **Fidelity**: Achieve full parity with the specified layout and experience, including role-gated rendering and field-level visibility rules, while maintaining strict consistency with the project's visual style.

## 3. Project Style Guide

Generate or automatically update the `specs/ui/style-guide.md` file after completing the three analysis phases. This file is the absolute design source of truth for the project.

- Generate the file automatically from the analysis. Do not write it by hand.
- Update the file whenever you detect new tokens or unrecorded patterns.
- Reference the guide in every component spec you produce.
- Mark a section as `[pending analysis]` if there is not enough data. Do not omit it, leave it empty, or invent values.

Mandatory file structure:

```md
# Style guide — [project name]

## Colors
| Token | Value | Semantic Use |
|---|---|---|

## Typography
| Level | Family | Size | Weight | Line Height |
|---|---|---|---|---|

## Spacing
[base scale, available values, usage rules]

## Interaction States
[per state: hover, focus, active, disabled, loading, error]
[per state: what changes visually + duration + easing if applicable]

## Project Notes
[detected specific conventions that do not fit in the above categories]
```

## 4. Component Spec

Produce a spec in `specs/features/[component-name].md` for every UI task that generates or modifies a component.

The spec must strictly follow this format:
- API: Define props with name, type, default value, and requirement status.
- Variants: Define an exhaustive list detailing what changes visually in each variant.
- States: Define what happens visually and functionally in every possible state.
- Tokens used: Explicitly reference tokens from `specs/ui/style-guide.md`. Do not use hardcoded values.
- Acceptance criteria: Define at least one visual, one functional, and one accessibility criterion. Specs without acceptance criteria are invalid.
- Out of scope: Explicitly declare what is excluded to prevent scope creep.

## 5. Implementation Rules

- Use exclusively tokens from `specs/ui/style-guide.md` in generated code. Do not introduce hardcoded values for color, typography, or spacing.
- Extend existing component libraries (MUI, shadcn, Radix) by following their customization patterns. Do not rewrite their components from scratch.
- Propose the complete API in the spec before writing code if a component does not exist in the project.
- Create new animations using the library already present in the project. Write an ADR to introduce a new animation library.
- Place reusable generic components in the folder designated by the project for that purpose (e.g., `components/ui/`, `shared/`). Detect the path before creating files.

## 6. Framework Flow Triggers

| Task | Flow |
|---|---|
| Initial frontend project analysis (onboarding) | Execute the 3 phases + generate `specs/ui/style-guide.md` before any other task |
| Small visual modification on existing component | Plan-Build (token analysis is still mandatory) |
| New isolated well-specified component | OpenSpec |
| New design system or significant UI refactor | Spec Kit |

## 7. Absolute Restrictions

- Do not invent design tokens that do not exist in the project.
- Do not use hardcoded values where an equivalent token exists.
- Do not omit the Phase 1-3 analysis by arguing "the task is too small".
- Do not generate a component spec without acceptance criteria.
- Do not introduce UI dependencies (icon libraries, animations, components) without an ADR.
- Do not assume a composition pattern is correct without verifying it in the existing code.

## Token Efficiency

This module enables terse-by-default communication for project sessions.

- Load and apply the `caveman` skill in `full` mode at the start of each new session in this project.
- Optimize for fewer output tokens without losing technical accuracy.
- Prefer short, direct answers over explanatory padding when the task is straightforward.
- Keep implementation details complete, but compress surrounding prose aggressively.
- If the user asks for more detail, examples, or a normal tone, expand the response immediately.

This module is reinforced by the external `caveman` skill, which is installed alongside the project configuration.

## Error Handling

Errors are a first-class part of the system design. Handle expected failures deliberately and fail fast on bugs.

### Error Classification

- **Operational errors** are expected runtime failures such as invalid input, timeouts, unavailable dependencies, rate limits, or missing resources.
- **Programmer errors** are bugs such as broken invariants, impossible states, incorrect assumptions, or null access on required values.

### Required Handling Rules

- Validate external input at boundaries before domain or application logic consumes it.
- Handle operational errors explicitly near the boundary where they become meaningful.
- Return safe user-facing messages. Do not leak stack traces, SQL fragments, filesystem paths, secrets, or internal topology.
- Log enough internal context for diagnosis, but keep recovery logic separate from presentation logic.
- Treat programmer errors as defects. Surface them quickly, log them with context, and fix the root cause rather than masking them.

### Recovery Patterns

Use recovery only when the failure mode is expected and the operation semantics allow it.

- Retry transient failures with bounded attempts, backoff, and jitter where appropriate.
- Use fallback paths only when degraded behaviour is still correct and explicit.
- Use compensation for multi-step flows when partial completion would leave the system inconsistent.
- Do not retry non-idempotent operations blindly.

### Anti-Patterns

- Silent catch blocks.
- Returning generic success when a meaningful failure occurred.
- Converting programmer errors into normal control flow.
- Mixing validation, authorisation, and system failures into the same vague error response.
- Retrying without limits or without understanding failure semantics.

## Structured Logging

Logs are part of the product's operating surface. They must support debugging, correlation, and incident response without exposing sensitive data.

### Logging Format

- Prefer structured machine-readable events such as JSON objects.
- Use consistent field names across services and modules.
- Include request IDs, correlation IDs, job IDs, or equivalent identifiers whenever work crosses boundaries.
- Include the operation name, outcome, duration, and relevant domain context when available.

### Wide Event Preference

Prefer one context-rich event per meaningful operation or request over many scattered log lines.

Good examples of useful context:

- Correlation identifier
- Route, command, or use-case name
- Outcome and status
- Duration or latency
- Safe business context that explains impact

### Logging Safety Rules

- Never log passwords, API keys, tokens, credentials, secrets, or encryption material.
- Avoid raw request and response bodies unless they are explicitly sanitised and necessary.
- Redact or omit personal data unless it is required for diagnosis and approved by the product context.
- Keep stack traces in internal logs only when they are safe and useful.

### Logging Quality Rules

- Do not scatter unstructured `console.log` style debug statements through request paths.
- Use a consistent logger interface for the project.
- Distinguish durable operational events from temporary local debugging noise.
- Make logs searchable by outcome, boundary, and correlation identifier.

## Coding Standards

- Use British English spelling in documentation, specs, commit messages, skill text, and user-facing copy.
- Prefer British English identifiers and names when introducing new code, unless an external API, tool, or established project interface requires a different spelling.
- Use explicit names that reflect the problem space.
- Keep functions small, cohesive, and intention-revealing.
- Prefer immutable data and side-effect isolation.
- Fail with clear errors and documented tradeoffs.
- Avoid boolean flag arguments when a richer type or explicit method is clearer.
- Prefer composition over inheritance.
- Keep modules deep: small public surface, meaningful internal behavior.
- Remove dead code and speculative abstractions.

## Change Workflow

For any non-trivial change:

1. Identify the affected capability and observable behavior.
2. Define or confirm the public behavior.
3. Choose the workflow mode with the user through meta-agent routing.
4. Implement inside the selected standards profile.
5. Refactor for clarity and boundary enforcement.
6. Run relevant verification, then broader checks.
7. Commit changes using the `commit-changes` skill for atomic deployment.

## Agent Operating Rules

Before starting any task:

- Read `FRAMEWORK.md` in full if you have not already done so in this session.
- Read the relevant specs in `specs/features/` that relate to the area you are changing.
- Read the relevant ADRs in `specs/decisions/` when the area is governed by prior decisions.
- Analyze `package.json` and local config files to identify the project's linting and formatting strategy.
- Act as a routing meta-agent: analyse the request, recommend a workflow mode, and ask the user how they want to proceed before implementing anything. Follow the routing protocol defined in `FRAMEWORK.md § Meta-agent routing`.

While executing work:

- Use British English spelling in documentation, specs, commit messages, skill text, and user-facing copy unless an external interface requires a different spelling.
- All observable behavior changes MUST update `specs/features/` before the work is done.
- Use the `commit-changes` skill to organise working tree changes into atomic, functional, and semantic commits using Conventional Commit format.
- After the user selects a workflow mode, follow `FRAMEWORK.md § Mode execution rules` exactly.
- Selecting `plan-build` authorises planning first, not automatic implementation.
- Selecting `spec-kit` requires following the full Spec Kit sequence in order unless the user explicitly changes modes.
- Selecting an OpenSpec workflow requires following its named sequence in order unless the user explicitly changes modes.
- Material architectural or workflow decisions MUST be captured or superseded in `specs/decisions/`.
- When an ADR is created or superseded, agents MUST also propose a corresponding entry to Airsync INBOX using `memory_kind: "note"` and `scope: "team"` with tags including `"adr"` and the ADR number. This ensures team-wide discoverability beyond the current project.
- Testing strategy is enabled. Choose the smallest test layer that can prove the behaviour with confidence.
- Write the failing test first when automated coverage is feasible.
- Prefer a test pyramid with many unit tests, fewer integration tests, and only a small number of critical E2E tests.
- Frontend code should default to Vitest for unit or component tests, Testing Library for user-facing UI behaviour, and Playwright for critical end-to-end journeys unless the repo already enforces another stack.
- Backend code MUST include unit tests and integration tests, even when the exact framework varies by language.
- Structure tests clearly with Given-When-Then or Arrange-Act-Assert.
- Prefer behaviour-focused assertions through public interfaces and accessible UI queries over internal implementation checks.
- Keep tests isolated, deterministic, and fast enough for regular local execution and CI.
- Add or update regression coverage for every confirmed bug when automated testing is feasible.
- Always check `package.json` and local config files to determine the linting/parsing strategy before making code changes.
- Ensure all generated code passes the project's linting checks.
- Use the `frontend-layout-extractor` skill to generate `specs/features/<name>/layout.md` capturing the structural requirements.
- Use the `frontend-experience-extractor` skill to generate `specs/features/<name>/experience.md` capturing behavioral and UX requirements.
- Use the `frontend-ui-generator` skill to build the UI based on both the `layout.md` and `experience.md` found in the feature folder.

- Maintain `specs/ui/style-guide.md` as the canonical source for all visual tokens.
- For legacy replication: prioritize structural fidelity in `layout.md` and visual consistency in the final implementation.
- Load and apply the `caveman` skill in `full` mode at the start of every new session in this project.
- ACTIVE EVERY RESPONSE: respond tersely by default while preserving full technical accuracy.
- Remove filler, pleasantries, and unnecessary hedging unless the user explicitly asks for more detail.
- Prefer short, direct phrasing with fragments when the meaning stays precise.
- Keep code, commands, commit messages, and other project artifacts in their normal readable form unless the user explicitly asks for compressed output there too.
- Treat `stop caveman` or `normal mode` as an instruction to disable the terse style for the rest of the session.
- Classify failures as operational errors or programmer errors before choosing a handling strategy.
- Operational errors at system boundaries MUST be handled explicitly with safe user-facing responses and clear internal context.
- Programmer errors and invariant violations MUST fail fast. Do not hide bugs behind generic success paths or silent recovery.
- Prefer retries only for transient failures and only with bounded attempts, backoff, and idempotent semantics.
- When an operation cannot complete normally, return or propagate an error shape that preserves intent, actionability, and boundary ownership.

- Emit structured logs as machine-readable events rather than ad hoc strings.
- Include correlation identifiers and operation context in logs that describe requests, jobs, or workflow steps.
- Prefer one context-rich completion event per meaningful operation over scattered low-signal log lines.
- Never log secrets, tokens, credentials, full sensitive payloads, or personal data that is not required for diagnosis.
- Keep log field names consistent across the codebase so events can be searched and correlated reliably.

## Workflow Framework

This framework uses a framework-agnostic change workflow.

### Supported modes

- `plan-build`: complete a planning step first, then implement only after the plan is finished and the user asks to proceed.
- `propose-apply-complete`: create working artifacts, implement from them, then sync canonical specs.
- `explore-propose-apply-complete`: explore first when the problem is unclear, then formalise and implement.
- `spec-kit`: spec-driven development using the Spec Kit workflow. Best for new features, cross-cutting concerns, or work requiring formal spec governance.

These are operating modes, not different specification systems. They all converge on the same canonical source of truth: `specs/features/`.

### Meta-agent routing

Before starting any non-trivial change, the agent MUST act as a routing meta-agent. Its role is to analyse the request and recommend the most appropriate mode, but the user makes the final decision.

**Routing protocol:**

1. Analyze the request for complexity, ambiguity, and scope.
2. Recommend one of the supported modes and briefly explain why.
3. Present the user with explicit workflow choices.
4. Ask the user how they want to proceed before doing any implementation work.

### Mode execution rules

Once the user selects a mode, the agent MUST follow that mode strictly. Do not merge modes, skip required steps, or silently continue into a later phase.

- `plan-build`:
  1. Plan first.
  2. Stop after the plan and present it clearly.
  3. Do not start building in the same step unless the user explicitly asks to continue with implementation after seeing the plan.
- `propose-apply-complete`:
  1. Run `open-spec-propose` first.
  2. Only after proposal artifacts exist, run `open-spec-apply`.
  3. After implementation is done, run `open-spec-complete`.
  4. Do not jump directly to apply or complete if the selected workflow has not reached that step.
- `explore-propose-apply-complete`:
  1. Run `open-spec-explore` first.
  2. Do not implement during explore mode.
  3. After exploration, continue with `open-spec-propose`, then `open-spec-apply`, then `open-spec-complete` in order.
- `spec-kit`:
  1. Follow the Spec Kit sequence strictly: `spec-kit-specify` → `spec-kit-clarify` → `spec-kit-plan` → `spec-kit-analyse` → `spec-kit-tasks` → `spec-kit-implement`.
  2. `spec-kit-checklist` may be used as a review or quality gate, but it does not replace required sequence steps.
  3. Do not skip ahead to implementation, planning, or task generation when an earlier required Spec Kit step has not been completed.

## Definition of Done

A change is not done unless all of the following are true:

- Relevant verification has been run for the affected scope.
- Naming matches the chosen problem language of the project.
- No unnecessary abstractions or framework leakage were introduced.
- Relevant spec in `specs/features/` reflects the current behavior.
- Relevant behavior is covered by tests.
- Material architectural decisions are captured or superseded in `specs/decisions/` and proposed to Airsync.
- Error paths distinguish operational failures from programmer errors and handle them accordingly.
- Logging is structured, correlated, and free of secrets or unnecessary sensitive data.
- The code style is consistent with the project's configured tools.
- Visual modifications align with the project design system tokens in `specs/ui/style-guide.md`.

## Living Specifications

`specs/features/` is the authoritative, technology-agnostic description of what the system does. It is not a planning artifact. It is a permanent record of system behavior.

### Non-Negotiable Rule

Every observable behavior change to the codebase MUST update `specs/features/` before the change is considered done.

### Spec format

```md
### Requirement: <system behavior as a declarative statement>
<One-sentence description using SHALL/MUST.>

#### Scenario: <observable outcome>
- **WHEN** <condition or trigger>
- **THEN** <expected system response>
```

Use RFC 2119 language. Do not include implementation details, technology names, or framework references. Specs describe behavior, not code.

## Review Checklist

Before finishing work, verify:

- Is the chosen behavior reflected in `specs/features/`?
- Is the code aligned with the enabled capabilities?
- Are tradeoffs explicit enough that a future agent can continue safely?
- Would this change still make sense if infrastructure or tooling changed?

If the answer to any of these is "no", the change is not ready.

## Default Agent Behavior

When contributing:

- Start by identifying the capability or boundary you are changing.
- Prefer existing package and boundary structure over creating ad hoc modules.
- Keep docs, specs, tests, and implementation aligned.
- If forced to choose, protect clarity of intent first and adapt tools around it.
