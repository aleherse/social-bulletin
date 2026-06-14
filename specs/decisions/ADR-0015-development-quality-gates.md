# ADR-0015: Development quality gates

- Status: Accepted
- Date: 2026-06-12

## Context

The project needs fast local feedback, consistent commit quality, and optional heavier CI checks that contributors can request from a pull request.

## Decision

Use Lefthook as the Git hook runner. Add hook-safe Make targets that call `docker compose run --rm` for containerised commands, so hooks do not require services to already be running.

Lefthook SHALL be configured with these boundaries:

- `pre-commit` runs fast checks only: format, lint, type and coding-standard checks.
- `commit-msg` validates Conventional Commit messages that MAY start with a task management tool ID.
- `pre-push` runs medium-cost checks such as codebase scanners and unit tests. Do not run full API or E2E tests.

Lefthook SHALL be installed (if not already) as part of `make init`.

Pull request template SHALL be added:

```markdown
Closes {LINK TO GH ISSUE}

## Description

[Provide a brief description of the changes or features implemented in this pull request.]

## CI checks

- [x] PHPSpec
- [x] Behat
- [x] Vitest
- [x] Playwright

## Risks and rollout notes

[Include any additional information or notes that may be helpful for deployment.]
```

Use a GitHub Actions `pull_request` workflow for optional checks controlled by a PR description checkbox.

The workflow SHALL use these event types:

```yaml
on:
  pull_request:
    types: [opened, edited, synchronize, reopened]
```

Optional jobs SHALL be gated by the checked state in the PR body:

```yaml
if: contains(github.event.pull_request.body, '- [x] PHPSpec')
```

## Consequences

- Lefthook runs checks before commit and push.
- Hook commands work through containers without requiring running services.
- Pre-commit stays fast and focused on local feedback.
- Pre-push runs medium-cost checks before sharing work.
- Commit messages follow Conventional Commits.
- PR checkboxes control optional CI jobs.
- Checkbox labels must stay stable between the PR template and workflows.
- Hook and workflow configuration need ongoing maintenance as checks change.
