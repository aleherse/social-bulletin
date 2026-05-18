# Agent guide

Conventions for agent-driven work in this repository.
Read this before making changes; the README covers human onboarding.

## Monorepo layout

- `apps/api` — Symfony HTTP layer only:
  controllers, security, Doctrine DBAL wiring, translations.
- `packages/core` — framework-free domain logic
  (`SocialBulletin\Core`, no Symfony/HTTP dependencies;
  Deptrac enforces the boundary).
- `apps/web` — React + Vite + TypeScript frontend structured with
  Feature-Sliced Design:
  `app/` providers, `pages/` slices, `shared/` infrastructure.
  Slices expose a public API through `index.ts`;
  import through it, never from slice internals.
  UI composes shadcn/ui primitives from `src/shared/ui`.
  Copy goes through the `shared/i18n` public API, never raw strings.
- `infrastructure` — AWS CDK TypeScript app
  with `live` and `preview` environments.
- `docker` — container images and nginx config;
  `docker/certs` is generated, never edit or commit it.

## Decisions and specs

- `specs/decisions/` holds ADRs; `ADR-0000` defines project constants
  (hostnames, namespaces, database schema).
  Consult relevant ADRs before structural changes,
  and propose a new ADR instead of silently diverging from one.
- `specs/changes/<change>/` holds a `spec.md` and `tasks.md` per change.
  Keep task checkboxes in sync with actual progress.

## Command surface: Makefile first

All development commands run through `make`,
which delegates to `docker compose run --rm` —
never run PHP, Composer, npm, or database tools on the host.
`make help` lists every target.
The ones agents need most:

| Command          | Purpose                                            |
|------------------|----------------------------------------------------|
| `make db`        | Rebuild the test database and DSLR snapshot        |
| `make tests`     | Full suite: PHPSpec, Behat, Vitest, Playwright     |
| `make php-unit`  | PHPSpec for `packages/core`                        |
| `make api-tests` | Behat for `apps/api` (needs a `make db` snapshot)  |
| `make web-unit`  | Vitest for `apps/web`                              |
| `make web-e2e`   | Playwright against the real API                    |
| `make lint`      | All linters and static analysis                    |
| `make console`   | Symfony console (`make console cmd="cache:clear"`) |

## Quality gates

Every change must pass `make lint` and the relevant test targets
before it is committed:

- PHP: Deptrac (architecture), PHPStan (static analysis),
  Easy Coding Standard (style).
- Web: `tsc`, ESLint, Prettier, knip.

Lefthook enforces the gates:
fast checks on `pre-commit`,
Conventional Commit messages on `commit-msg`
(optionally prefixed with a task ID, e.g. `TASK-123 feat: …`),
and unit tests plus scanners on `pre-push`.
Do not bypass hooks with `--no-verify`.

## Testing conventions

- API behaviour: Behat scenarios in `apps/api/features/`,
  JMESPath assertions in `Then` steps,
  `Given` steps create data through application code.
  Every scenario starts from the DSLR `fixtures` snapshot;
  `@fixtures`-tagged features run only via `make db`.
- Core logic: PHPSpec specs in `packages/core`.
- Frontend logic: Vitest + Testing Library next to the source.
- Browser journeys: Playwright specs in `apps/web/e2e/`
  run against the compiled frontend and real API.

## Claude Code setup

`make setup-claude` symlinks `.agents/skills` into `.claude/skills`
and links `CLAUDE.md` to this file.
