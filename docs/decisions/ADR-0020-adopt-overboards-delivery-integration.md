# ADR-0020: Adopt the Overboards delivery integration

- Status: Accepted
- Date: 2026-10-03

## Context

Work on this project is queued on a Boards board and delivered by the Overboards pipeline:
unattended agents specify, implement and verify cards,
then hand the result to a person for review.
Registering the project needs a board, a token for it, and a branch for completed work.

## Decision

Connect the project to Overboards through one board, a supervisor-held token
and the `INTEGRATION_BRANCH` integration branch,
and treat the readiness requirements as standing constraints.

### Board

- The project has one board, `SOCIAL_BOARD_URL`,
  served by the API at `BOARDS_API_ORIGIN`.
- The **AI delivery board** section of `README.md` SHALL be the only place
  that names the board, the API base and the integration branch.
  Overboards skills read that section,
  so its heading SHALL stay as it is and its first link SHALL be the board.
- The board's **Backlog** column SHALL be the canonical backlog.
  Later work SHALL be filed there with the `overboards-add-card` skill,
  not in TODO comments, local notes or issues.

```markdown
## AI delivery board

Work on this project is queued and delivered on one Boards board,
worked by the Overboards pipeline.
There is one target, so there is nothing to choose between.

|                    | Production           |
|--------------------|----------------------|
| Board              | `SOCIAL_BOARD_URL`   |
| API base           | `BOARDS_API_ORIGIN`  |
| Integration branch | `INTEGRATION_BRANCH` |

The token is board-scoped and issued from the board's **Manage tokens**
with the **Unattended contributor** shortcut.

The **Backlog** column is the project's canonical backlog;
file new findings with the `overboards-add-card` skill,
which checks the board for a covering card first.

Completed cards land on `INTEGRATION_BRANCH`.
A stage that needs the branch and finds it absent creates it once from `main`;
the release pull request from `INTEGRATION_BRANCH` into `main` is where a person reviews.
`main` is never pushed to directly.
```

### Token

The token SHALL be board-scoped,
issued from the board's **Manage tokens** with the **Unattended contributor** shortcut,
and stored only in the Overboards supervisor's secrets.

### Readiness requirements

Every change SHALL keep these requirements; breaking one breaks the integration.

| Requirement             | What it demands                                                            |
|-------------------------|----------------------------------------------------------------------------|
| `ROOT-README`           | The README covers setup, daily use, focussed checks and the full gate      |
| `MAKEFILE-COMMANDS`     | `make` lists targets; standard init, start, gate and focussed checks exist |
| `WORKER-ISOLATION`      | Init, start, checks and gate need no project toolchain on the host         |
| `INIT-IDEMPOTENT`       | `make init` succeeds cold and again without cleanup                        |
| `PREREQ-ORDERING`       | Dependent steps wait on health or completion, never on timing              |
| `FULL-GATE-TIERING`     | Cheap checks finish before services, builds or browsers start              |
| `FULL-GATE-SINGLE-PATH` | No target, flag or variable bypasses the gate's cheap checks               |
| `FULL-GATE-STAGES`      | Stages are listed from the gate's declaration and diagnosable one by one   |
| `GATE-EXECUTION`        | `make ci` runs to completion and leaves no source changes in CI mode       |
| `CI-PARITY`             | CI runs the same gate on pull requests and on `main`                       |
| `CLEANUP-DOCUMENTED`    | A documented command removes every resource the project created            |
| `TEST-COVERAGE`         | Behaviour is covered by automated tests, or the gap is documented          |

## Consequences

- The token never enters the repository, so a checkout cannot leak it.
- `main` and `INTEGRATION_BRANCH` diverge between releases until the release pull request merges.
- Changes to the Makefile, Compose files, hooks and CI must keep the readiness requirements,
  and a regression blocks unattended delivery.
- Renaming the board or the integration branch
  means changing this ADR, the README section and the supervisor's configuration together.
- Doctor-owned settings can drift unnoticed until the Doctor runs.
