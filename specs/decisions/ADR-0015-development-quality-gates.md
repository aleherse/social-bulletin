# ADR-0015: Development quality gates

- Status: Accepted
- Date: 2026-06-06

## Context

Social Bulletin uses a Docker Compose development runtime with separate Symfony API, PHP core, React web, PostgreSQL, and nginx services. Existing project commands already define local setup, linting, static analysis, unit tests, integration tests, and end-to-end tests through the root `Makefile`.

Contributors need fast local feedback before commits, stronger validation before code reaches `main`, and a consistent pull request format that captures behaviour changes, test evidence, specification updates, and architectural decision records.

Git hooks must work reliably in the repository's Docker-first setup. Commands based on `docker compose exec` require already-running containers, which makes them fragile for hooks. Hook commands should use `docker compose run --rm` through Make targets when they need to be independent of `make up`.

## Decision

Use Lefthook as the repository Git hook runner.

Add hook-safe Make targets for checks that should run from Git hooks. These targets should call `docker compose run --rm` for containerised commands so hooks do not require services to already be running.

Configure Lefthook with these boundaries:

- `pre-commit` runs fast checks only: format, lint, type and coding-standard checks.
- `commit-msg` validates Conventional Commit messages that MAY start with a task management tool ID.
- `pre-push` runs medium-cost checks such as codebase scanners and unit tests. Do not RUN full API or E2E tests.

Add a GitHub Actions workflow for pull requests targeting `main`. The workflow should run the full project suite through the existing `make init` and `make tests` commands so CI follows the same quality gate documented for local development.

Add a GitHub pull request template that asks for:

- summary and change type
- link to the task management tool affected items
- updates in documentation
- risks and rollout notes

## Consequences

Local commits get quick feedback without making every commit wait for the full test suite.

Pushes and pull requests receive stronger validation before merge.

The Makefile becomes the stable interface for hook and CI commands, keeping Docker details out of Lefthook configuration where possible.

The GitHub workflow intentionally runs the full suite only for pull requests targeting `main`, reducing unnecessary CI cost for branches that are not proposed for merge.

Playwright remains part of the full suite and CI gate, but not a default local hook, preserving local developer speed.

Pull requests become easier to review because they consistently document behaviour changes, specification impact, decision impact, and verification performed.
