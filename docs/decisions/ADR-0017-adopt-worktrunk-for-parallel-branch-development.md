# ADR-0017: Adopt Worktrunk for Parallel Branch Development

- Status: Accepted
- Date: 2026-08-09

## Context

Per [ADR-0002](ADR-0002-adopt-docker-based-development.md), the stack
runs as one Docker Compose project with fixed host ports and a single
`.env`. That only supports one branch at a time; developers working
on more than one branch concurrently (e.g. a feature branch and a
hotfix) hit port collisions and manual `.env` juggling.

## Decision

Adopt [Worktrunk](https://worktrunk.dev) to manage one git worktree
per branch, each with its own Docker Compose project, so several
branches' stacks run at the same time without collisions.

Worktrunk SHALL be configured through `.config/wt.toml`:

- `pre-start` SHALL write a per-worktree `.env`
  that sets a `COMPOSE_PROJECT_NAME` derived from the branch name,
  so Compose projects do not collide,
  and sets every host port variable declared in `.env.dist`
  (e.g. app server, reverse proxy, database)
  to a deterministic port hashed from the branch name.
- `copy` SHALL run `wt step copy-ignored --require-include` to copy
  files listed in `.worktreeinclude` into the new worktree.
- `init` SHALL run `make init` to bring the worktree's stack up.
- `pre-remove` SHALL run `make down` to tear down the worktree's
  stack before the worktree is deleted.
- `list` SHALL expose each worktree's URL using its hashed HTTP port.

`.worktreeinclude` SHALL list generated, git-ignored paths a new
worktree cannot produce on its own (e.g. TLS certificates, signing
keys, or other generated secrets).

`make init` SHALL be the single entrypoint Worktrunk calls to
prepare a new worktree end to end (environment setup, dependency
install, database).

## Consequences

- Multiple branches' stacks can run concurrently, each on its own
  hashed ports and Compose project.
- New worktrees automatically receive generated secrets and
  certificates they cannot regenerate themselves, via
  `.worktreeinclude`.
- `make init` remains a single, scriptable entrypoint usable both by
  a human and by Worktrunk's `init` step.
- Host ports for app-facing services are no longer fixed; developers
  must use `wt list` (or the per-branch `.env`) to find a worktree's
  URL instead of assuming a fixed `localhost` port.
- Onboarding requires installing Rust/Cargo and Worktrunk
  (`cargo install worktrunk`) to get parallel worktrees; single-branch
  development still only needs Docker and `make`.
- A new published port needs a variable in `docker-compose.yml`, `.env.dist`
  and `.config/wt.toml`;
  `.worktreeinclude` needs updating whenever a new generated/ignored path
  is introduced that worktrees cannot regenerate.
