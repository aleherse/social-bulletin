# Agent guide

Conventions for agent-driven work in this repository.
Read this before making changes; the README covers human onboarding.

## Decisions and specs

- `specs/decisions/` holds ADRs; `ADR-0000` defines project constants
  (hostnames, namespaces, database schema).
  Consult relevant ADRs before structural changes,
  and propose a new ADR instead of silently diverging from one.

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

