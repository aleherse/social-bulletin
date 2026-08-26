# ADR-0013: Development quality gates

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

Use a GitHub Actions `pull_request` workflow
for optional checks controlled by a PR description checkbox.

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

Every job runs on its own runner,
so the containers and dependencies a gated job needs
cannot be prepared before those jobs start.
A dedicated setup job SHALL prepare them once per run,
and the gated jobs SHALL restore that work from the workflow cache
in parallel rather than repeating it.

Container images and installed dependencies SHALL be cached separately.
The development stack bind-mounts the repository into its containers,
so dependency trees live in the workspace rather than inside an image,
and a prepared image alone would still leave every job installing them.

Build configuration that only CI can satisfy
SHALL be applied as a CI-only overlay
rather than added to the development stack definition,
so that local builds keep working unchanged.

`make setup-ci` SHALL be the only entry point for that preparation,
and SHALL be decomposable per service
so a job prepares just the part of the stack it uses.

The gating condition SHALL cover the setup job as well as the checks,
so a pull request that requests no checks runs nothing.

## Consequences

- Lefthook runs checks before commit and push.
- Hook commands work through containers without requiring running services.
- Pre-commit stays fast and focused on local feedback.
- Pre-push runs medium-cost checks before sharing work.
- Commit messages follow Conventional Commits.
- PR checkboxes control optional CI jobs.
- Checkbox labels must stay stable across the PR template, the gated jobs,
  and the setup job condition that repeats them.
- Gated jobs restore cached image layers and dependencies
  rather than building them.
- A run that changes an image definition or a dependency lock file
  misses the caches and saves nothing.
- Hook and workflow configuration need ongoing maintenance as checks change.
