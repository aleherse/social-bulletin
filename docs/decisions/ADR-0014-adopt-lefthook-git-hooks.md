# ADR-0014: Adopt Lefthook git hooks

- Status: Accepted
- Date: 2026-06-12

## Context

The project needs fast local feedback and consistent commit quality
before work is shared.

## Decision

Use Lefthook as the Git hook runner.
Hook commands SHALL call hook-safe Make targets that use `docker compose run --rm`,
so hooks do not require services to already be running.

Lefthook SHALL be configured with these boundaries:

- `pre-commit` runs fast checks only: format, lint, type and coding-standard checks,
  narrowed to the staged files where the tool allows it.
  Formatters run with `CI=true` so they check rather than rewrite,
  and a commit never records files that differ from what was checked.
- `commit-msg` validates Conventional Commit messages
  that MAY start with a task ID without whitespace (`[KEY-123] ` or `KEY-123 | `).
  `WIP`, merge and revert messages are let through.
- `pre-push` runs medium-cost checks: codebase scanners and unit suites.
  Do not run the API or E2E suites.

Lefthook SHALL be installed by `make init` through `make hooks`,
which installs the npm package from the node container
for the host's operating system and CPU,
then runs its native binary on the host,
so neither Node nor npm is needed on the host.

## Consequences

- Lefthook runs checks before commit and push.
- Hook commands work through containers without requiring running services.
- Pre-commit stays fast and focused on the staged files.
- Pre-push runs medium-cost checks before sharing work.
- Commit messages follow Conventional Commits.
- Hook configuration needs ongoing maintenance as checks change.
