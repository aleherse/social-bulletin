# Implementation Plan: [FEATURE]

**Branch**: `[###-feature-name]` | **Date**: [DATE] | **Spec**: [link]

**Input**: Feature specification from `/specs/[###-feature-name]/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command. See `.specify/templates/plan-template.md` for the execution workflow.

## Summary

[Extract from feature spec: primary requirement + technical approach from research]

## Technical Context

<!--
  ACTION REQUIRED: Replace the content in this section with the technical details
  for the project. The structure here is presented in advisory capacity to guide
  the iteration process.
-->

**Language/Version**: [e.g., Python 3.11, Swift 5.9, Rust 1.75 or NEEDS CLARIFICATION]

**Primary Dependencies**: [e.g., FastAPI, UIKit, LLVM or NEEDS CLARIFICATION]

**Storage**: [if applicable, e.g., PostgreSQL, CoreData, files or N/A]

**Testing**: [Required — name every suite this feature needs, per ADR-0015:
PHPSpec, Behat, Vitest, Playwright. "None" is not a valid answer; a layer left
out needs a written waiver]

**Target Platform**: [e.g., Linux server, iOS 15+, WASM or NEEDS CLARIFICATION]

**Project Type**: [e.g., library/cli/web-service/mobile-app/compiler/desktop-app or NEEDS CLARIFICATION]

**Performance Goals**: [domain-specific, e.g., 1000 req/s, 10k lines/sec, 60 fps or NEEDS CLARIFICATION]

**Constraints**: [domain-specific, e.g., <200ms p95, <100MB memory, offline-capable or NEEDS CLARIFICATION]

**Scale/Scope**: [domain-specific, e.g., 10k users, 1M LOC, 50 screens or NEEDS CLARIFICATION]

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Confirm each gate or record a justified violation in Complexity Tracking.

- [ ] **I. Tests first-class**: every layer this feature touches is listed below
      with the tool that will cover it; no layer is left implicit
- [ ] **II. Layer/tool mapping**: `packages/core` → PHPSpec, `apps/api` → Behat,
      `apps/web` units → Vitest, user journeys → Playwright
- [ ] **II. Pyramid**: every user story has a Playwright journey for its **happy
      path**; error, edge, and validation cases are planned at the lowest layer
      that can prove them and are not repeated higher up
- [ ] **III. Decisions and rules written down**: relevant ADRs consulted;
      divergence proposes a new ADR; applicable `docs/rules/` files read
- [ ] **IV. Automated gates**: work is runnable through `make` targets; test data
      comes from the DSLR `fixtures` snapshot and no test run recreates it

Architecture boundaries (hexagonal layering, deptrac, FSD imports) are not a
constitutional gate; they are governed by ADR-0005/ADR-0007 and enforced by
`make lint`. Gate III covers consulting them.

**Layers touched by this feature**:

| Layer | Touched? | Test tool | Where |
|-------|----------|-----------|-------|
| `packages/core` | [yes/no] | PHPSpec | `packages/core/spec/…` |
| `apps/api` | [yes/no] | Behat | `apps/api/features/…` |
| `apps/web` | [yes/no] | Vitest | next to the source |
| User journeys | [yes/no] | Playwright | `apps/web/e2e/…` |

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)
<!--
  ACTION REQUIRED: Replace the placeholder tree below with the concrete layout
  for this feature. Delete unused options and expand the chosen structure with
  real paths (e.g., apps/admin, packages/something). The delivered plan must
  not include Option labels.
-->

```text
# [REMOVE IF UNUSED] Option 1: Single project (DEFAULT)
src/
├── models/
├── services/
├── cli/
└── lib/

tests/
├── contract/
├── integration/
└── unit/

# [REMOVE IF UNUSED] Option 2: Web application (when "frontend" + "backend" detected)
backend/
├── src/
│   ├── models/
│   ├── services/
│   └── api/
└── tests/

frontend/
├── src/
│   ├── components/
│   ├── pages/
│   └── services/
└── tests/

# [REMOVE IF UNUSED] Option 3: Mobile + API (when "iOS/Android" detected)
api/
└── [same as backend above]

ios/ or android/
└── [platform-specific structure: feature modules, UI flows, platform tests]
```

**Structure Decision**: [Document the selected structure and reference the real
directories captured above]

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., 4th project] | [current need] | [why 3 projects insufficient] |
| [e.g., Repository pattern] | [specific problem] | [why direct DB access insufficient] |
