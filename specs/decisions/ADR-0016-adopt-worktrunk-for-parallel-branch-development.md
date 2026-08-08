# ADR-0016: Adopt Worktrunk for Parallel Branch Development

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

- `pre-start` SHALL generate a per-worktree
  `docker-compose.override.yml` that assigns every service binding a
  host port (e.g. app server, reverse proxy, database) a
  deterministic port hashed from the branch name, and set any
  per-worktree connection details services need to reach each other
  (e.g. a debugger's remote host/port). It SHALL also write a
  per-worktree `.env` with a `COMPOSE_PROJECT_NAME` derived from the
  branch name so Compose projects do not collide.
- `copy` SHALL run `wt step copy-ignored --require-include` to copy
  files listed in `.worktreeinclude` into the new worktree.
- `init` SHALL run `make init` to bring the worktree's stack up.
- `pre-remove` SHALL run `make down` to tear down the worktree's
  stack before the worktree is deleted.
- `list` SHALL expose each worktree's URL using its hashed HTTP port.

`.worktreeinclude` SHALL list generated, git-ignored paths a new
worktree cannot produce on its own (e.g. TLS certificates, signing
keys, or other generated secrets).

`docker-compose.yml` SHALL NOT bind static host ports for any
service that needs to run without collisions across worktrees; host
ports are assigned per branch through the generated override file
instead.

`.env` SHALL be git-ignored, since Worktrunk generates one per
worktree.

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
- `docker-compose.override.yml.dist` at the repo root remains the
  fallback for developers not using Worktrunk, but its ports may
  collide with a Worktrunk-managed worktree running at the same
  time.
- Onboarding requires installing Rust/Cargo and Worktrunk
  (`cargo install worktrunk`) to get parallel worktrees; single-branch
  development still only needs Docker and `make`.
- `.config/wt.toml` and `.worktreeinclude` need updating whenever a
  new service needs a per-branch port, or a new generated/ignored
  path is introduced that worktrees cannot regenerate.
