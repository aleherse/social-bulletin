# ADR-0015: Development quality gates

- Status: Accepted
- Date: 2026-06-06

## Context

The project uses Docker Compose for Symfony API, PHP core, React web, PostgreSQL, and nginx. Root Make targets cover setup, linting, static analysis, unit tests, integration tests, and end-to-end tests.

Contributors need fast local feedback, stronger validation before merge, and reliable hooks in the Docker-first setup.

## Decision

Use Lefthook as the Git hook runner. Add hook-safe Make targets that call `docker compose run --rm` for containerised commands, so hooks do not require services to already be running.

Configure Lefthook with these boundaries:

- `pre-commit` runs fast checks only: format, lint, type and coding-standard checks.
- `commit-msg` validates Conventional Commit messages that MAY start with a task management tool ID.
- `pre-push` runs medium-cost checks such as codebase scanners and unit tests. Do not run full API or E2E tests.

Add a GitHub Actions workflow for pull requests targeting `main`. It runs the full suite through `make init` and `make tests`, matching the documented local quality gate.

Add a pull request template covering:

- summary and change type
- affected task management items
- updates in documentation
- risks and rollout notes

## Consequences

- Local commits get quick feedback without running the full suite.
- Pushes and pull requests get stronger validation before merge.
- The Makefile becomes the stable interface for hooks and CI, keeping Docker details out of Lefthook where possible.
- The GitHub workflow runs the full suite only for pull requests targeting `main`, reducing CI cost for unproposed branches.
- Playwright and API Behat remain part of the full suite and CI gate, but not default local hooks, preserving developer speed.
- Pull requests become easier to review because behaviour changes, risks, and rollout notes are documented consistently.
