# ADR-0013: Development quality gates

## Context

The project needs fast local feedback while work is in progress,
consistent commit quality,
and one verdict that CI and contributors (people and Overboards agents)
reach the same way.
A gate that CI runs only when somebody ticks a box is a gate that does not run
on the change that needed it,
and a local gate that differs from CI's reports failures that belong to the
difference rather than to the change.

## Decision

### Three kinds of run

- **The full gate**, `make ci`, is the only verdict.
  It runs every CI check over the whole tree that is about to land.
- **The focussed check** is what work in progress owes before each commit:
  the linters narrowed to the changed files (`make lint FILES="..."`, or one
  check such as `make php-stan FILES="..."`) and the test files covering the
  touched behaviour (`make php-unit PATHS=...`, `make api-tests PATHS=...`,
  `make web-unit PATHS=...`, `make web-e2e PATHS=...`).
  Deptrac, `tsc -b`, knip and the infrastructure typecheck accept no file
  argument and run whole; their `make help` entries say so.
- **One named stage**, `make ci-stage STAGE=<stage>`, re-runs a single gate
  stage in the gate's own Compose project, after the stages it needs.
  It is a diagnostic and never a verdict.

Focussed checks and the gate run the same targets in the same containers with
the same configuration, so they cannot disagree about a file.

### The gate

`make ci` runs under its own Compose project
(`social-bulletin-gate-<checkout hash>`) with `docker-compose.gate.yml` merged
in, which publishes no host ports.
It never touches the development stack's containers or database,
and two checkouts can gate at once.
It removes its containers, network and database volume when it finishes,
pass or fail (`make ci-down`).

Its stages are declared once, in the Makefile, and `make ci-stages` lists
them from that declaration.
They run in tiers, each waiting only on what can change its verdict:

1. **prepare**: build the images, install Composer and npm dependencies and the
   JWT key pair.
2. **cheap**: Deptrac, PHPStan, ECS, `tsc`, ESLint, knip, Prettier and the
   infrastructure typecheck, in parallel with `make -k`,
   so every cheap failure is reported in one pass.
   Nothing that starts a service, migrates a database, builds or drives a
   browser runs until this tier is green.
3. **build**: PHPSpec, Vitest, the production frontend bundle and the database
   snapshot (`make db`), in parallel.
4. **suites**: Behat, then Playwright.
   They run one after the other because both restore the same DSLR snapshot
   into the same database.

There is one path through the gate:
no target, flag or variable skips the cheap tier.
Locally the gate applies the deterministic formatters (ECS `--fix`,
`prettier --write --list-different`) and names the files it changed;
under `CI=true` both check strictly, as the backstop for a contributor who
never ran the local gate.

Every CI check runs locally: `make ci` has no exclusions.

### Continuous integration

`.github/workflows/ci.yml` runs `make ci` on every pull request and every push
to `main`, and on demand.
It adds only `docker-compose.ci.yml` to the gate's Compose files,
a CI-only overlay that shares image layers through the Actions cache,
and caches the installed dependencies keyed on the lock files.
Runs group by branch and cancel superseded ones;
draft pull requests do not start the gate, and closing a pull request cancels
its own leftover runs.
The runner is chosen by the `ACTIONS_RUNNER_TARGET` repository variable
(`github-hosted`, the default, or `self-hosted`);
any other value fails the run.
Every job starts from `permissions: {}` and adds only what it needs.

### Git hooks

Lefthook is the Git hook runner, installed by `make init` when npm is available
on the host.
Hook commands call the same Make targets, so no services need to be running:

- `pre-commit` runs the fast checks (format, lint, type and coding standard)
  over the staged files, strictly, without rewriting them.
- `commit-msg` validates Conventional Commit messages that MAY start with a
  task management tool ID.
- `pre-push` runs medium-cost checks: codebase scanners and unit tests.
  It never runs the API or E2E suites.

## Consequences

- CI and contributors reach the same verdict through the same command.
- Every pull request pays for the whole gate; its cheap tier fails fast and
  reports every cheap failure together.
- The gate needs no free host ports and leaves nothing running behind it.
- A contributor who wants a quicker answer narrows with `FILES=` or `PATHS=`
  instead of skipping part of the gate.
- A new CI check joins a gate tier in the Makefile, never a new workflow job,
  so branch protection keeps requiring the one `make ci` check by name.
- Hook and gate configuration need maintenance as checks change.

Revisit the single-job workflow if the gate's wall-clock time on CI exceeds
fifteen minutes:
the tiers can then split across jobs without changing the local gate.
