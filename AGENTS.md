# Agent guide

Conventions for agent-driven work in this repository.
Read this before making changes; the README covers human onboarding.

## Everyday commands

`make help` lists every target.
The common ones:

| Command      | Purpose                                                     |
|--------------|-------------------------------------------------------------|
| `make up`    | Start the development stack                                 |
| `make down`  | Stop the development stack                                  |
| `make logs`  | Follow service logs (`make logs service=php`)               |
| `make shell` | Open a shell in a container (`make shell service=node`)     |
| `make db`    | Rebuild the test database and fixtures snapshot             |
| `make tests` | Run the full test suite (PHPSpec, Behat, Vitest, Playwright)|
| `make lint`  | Run all linting and static analysis checks                  |

## Repository layout

| Path              | Contents                                            |
|-------------------|-----------------------------------------------------|
| `apps/api`        | Symfony HTTP application                            |
| `apps/web`        | React + Vite frontend (Feature-Sliced Design)       |
| `packages/core`   | Framework-free PHP domain logic                     |
| `infrastructure`  | AWS CDK deployment app (`live` and `preview`)       |
| `docker`          | Container images, nginx config, generated certs     |
| `docs/decisions`  | Architecture Decision Records                       |
| `docs/rules`      | Distilled engineering rules, by category/subcategory |
| `specs/changes`   | Change specifications and task lists                |

## Decisions and specs

- `.specify/memory/constitution.md` holds the project's non-negotiable
  principles and outranks conflicting guidance elsewhere.
- `docs/decisions/` holds ADRs; `ADR-0000` defines project constants
  (hostnames, namespaces, database schema).
  Consult relevant ADRs before structural changes,
  and propose a new ADR instead of silently diverging from one.

## Coding Rules

- `docs/rules/<category>/<subcategory>.md` holds distilled, checkable engineering rules
  (`WHEN` a condition applies, `THEN` what to do, with a repo-grounded example) —
  decisions already made once that should guide every future change in that area.
- Before writing or changing code in an area, check `docs/rules/` for a file matching that
  category/subcategory and follow it — e.g. adding a repository under `packages/core` →
  read `docs/rules/database/persistence.md` first.
- When a change encodes a decision worth repeating (a correction during review, a convention
  established by a diff), use the `extract-rule` skill to distill it into `docs/rules/`
  instead of leaving it implicit for the next agent to rediscover.

## Engineering docs

| File                                                    | Purpose                      | Read When                                  |
|---------------------------------------------------------|------------------------------|--------------------------------------------|
| [hexagonal.md](docs/engineering/backend/hexagonal.md)   | Hexagonal Architecture rules | Writing PHP code                           |
| [fsd.md](docs/engineering/frontend/fsd.md)              | Feature-Sliced Design rules  | Writing Typescript code                    |
| [testing.md](docs/engineering/testing/testing.md)       | Testing conventions          | Writing tests                              |
| [monorepo.md](docs/engineering/scaffolding/monorepo.md) | Monorepo layout              | Modifing containers, structure and tools   |
| [make.md](docs/engineering/scaffolding/make.md)         | Unify development commands   | Working on development flow common actions |
| [quality.md](docs/engineering/quality/quality.md)       | Ensure code quality          | Tweaking code quality tools and hooks      |

## Claude Code setup

`make setup-claude` symlinks `.agents/skills` into `.claude/skills`
and links `CLAUDE.md` to this file.

<!-- SPECKIT START -->
## Active feature (Spec Kit)

- Feature: Propose a Movement
- Plan: [specs/001-propose-movement/plan.md](specs/001-propose-movement/plan.md)
- Spec: [specs/001-propose-movement/spec.md](specs/001-propose-movement/spec.md)
<!-- SPECKIT END -->

