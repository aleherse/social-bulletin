# ADR-0017: Adopt Frontend Architecture Linting

- Status: Proposed
- Date: 2026-09-14

## Context

Per [ADR-0007](ADR-0007-adopt-react-vite-npm-and-fsd.md),
`apps/web` is structured with Feature-Sliced Design,
and layers are added only as real code needs them.

Per [ADR-0012](ADR-0012-adopt-linting-and-static-analysis-toolchain.md),
architectural boundaries are an explicit goal of the toolchain,
and Deptrac owns them for PHP.
TypeScript has no equivalent owner:
the ESLint ownership list stops at code-quality, React, accessibility, import, and maintainability rules.

The Feature-Sliced Design rules in
[fsd.md](../../docs/engineering/frontend/fsd.md)
are therefore prose only.
Nothing fails when a slice is imported through its internals instead of its public API,
when a lower layer imports an upper one,
or when two slices in the same layer import each other.
The frontend is small enough today that the rules hold by inspection;
that stops being true as `widgets`, `features`, and `entities` arrive.

The current code already contains one boundary question that no tool answers:
`@/shared/lib/utils` is imported directly at four call sites,
past a segment that has no `index.ts`,
and `components.json` hardcodes that path as the shadcn `utils` alias.

## Decision

Adopt two complementary frontend architecture linters for `apps/web`.

### Import Boundaries — `eslint-plugin-boundaries`

Use **eslint-plugin-boundaries** for Feature-Sliced Design import rules.

It SHALL be configured inside the existing `apps/web/eslint.config.js`
so it runs in the editor, in `make web-eslint`, and on `pre-commit`
with no new entrypoint.

Feature-Sliced Design layers SHALL be declared as elements captured by slice:

```js
const layers = ['app', 'pages', 'widgets', 'features', 'entities', 'shared'];

// settings['boundaries/elements']:
//   { type: layer, pattern: `src/${layer}/*`, mode: 'folder', capture: ['slice'] }
```

The element list SHALL be derived from that layer array
so adding a layer needs no configuration change.

These rules SHALL be enabled as errors:

- `boundaries/element-types` — a layer may import strictly lower layers only,
  plus its own slice.
- `boundaries/entry-point` — cross-slice imports resolve to the slice `index.ts`.
- `boundaries/no-private` — no reaching into another slice's internals.
- `boundaries/no-unknown-files` — every file under `src/` belongs to a layer.

Two exceptions SHALL be configured, and only these two:

- Application roots `src/main.tsx`, `src/index.css`, and `src/test-setup.ts`
  sit outside every layer and SHALL be excluded from `boundaries/include`.
- The `shared` layer SHALL be exempt from `boundaries/entry-point`.
  Upstream Feature-Sliced Design permits deep imports inside `shared`,
  whose segments are too broad for a single barrel,
  and the shadcn generator writes `@/shared/lib/utils` by configuration.
  `fsd.md` SHALL record this exemption
  so the exemption and the documented rule do not contradict each other.

### Structural Conventions — Steiger

Use **steiger** with **@feature-sliced/steiger-plugin**
for the structural conventions ESLint cannot see.

Steiger is the linter maintained by the Feature-Sliced Design authors.
It reads the project as a file tree rather than a module graph,
so it checks slice and segment shape, naming, and granularity:
segmentless slices, excessive or insignificant slicing,
repetitive or ambiguous slice names, and misspelled layer names.

Configuration SHALL live in `apps/web/steiger.config.ts`
and SHALL extend the plugin's recommended configuration.

Steiger SHALL run as its own check rather than inside ESLint,
because it scans the whole project tree in one pass.

### Tool Ownership

Per [ADR-0012](ADR-0012-adopt-linting-and-static-analysis-toolchain.md),
tools SHALL NOT duplicate each other's rules.
Frontend architecture checks SHALL split as follows:

- `eslint-plugin-boundaries` owns import direction and public-API access.
- Steiger owns slice and segment structure, naming, and granularity.

The Steiger rules that overlap import ownership
— those forbidding cross-layer imports and public-API sidesteps —
SHALL be disabled in `steiger.config.ts`,
with a comment naming this ADR as the reason.

A violation SHALL therefore be reported by exactly one tool.

### Execution Contract

The root `Makefile` SHALL expose Steiger through a `web-steiger` target,
and `make lint` SHALL include it.

Per [ADR-0013](ADR-0013-development-quality-gates.md),
hook placement follows check cost:

- `eslint-plugin-boundaries` runs inside the existing `web-eslint` `pre-commit` command.
- `web-steiger` runs on `pre-push`, alongside the other codebase scanners.

This mirrors the PHP side,
where Easy Coding Standard runs on `pre-commit` and Deptrac on `pre-push`.

Knip SHALL keep treating slice and segment `index.ts` files as entry points,
as it already does.

## Non-Goals

- Renaming, moving, or re-slicing existing code.
  The two linters land against the structure that exists today.
- Adding Feature-Sliced Design layers that no code needs,
  which would contradict [ADR-0007](ADR-0007-adopt-react-vite-npm-and-fsd.md).
- Enforcing the cross-import `@x` notation.
  No `entities` layer exists yet;
  when one arrives, this ADR is revisited to decide which tool owns `@x`.
- Replacing Prettier, knip, or the TypeScript compiler in any part of their ownership.

## Implementation Plan

- **Affected paths**:
  `apps/web/package.json`,
  `apps/web/eslint.config.js`,
  `apps/web/steiger.config.ts` (new),
  `Makefile`,
  `lefthook.yml`,
  `docs/engineering/frontend/fsd.md`,
  `docs/engineering/quality/quality.md`.
- **Dependencies**: add as `devDependencies` of `apps/web`,
  installed through the Node container per
  [ADR-0002](ADR-0002-adopt-docker-based-development.md):
  `eslint-plugin-boundaries`, `steiger`, `@feature-sliced/steiger-plugin`.
- **Pattern**: configuration carries an `ADR-0017` comment at each entry point,
  matching the existing `ADR-0012` and `ADR-0013` comments
  in `eslint.config.js`, `knip.jsonc`, and `lefthook.yml`.
- **Documentation**: `fsd.md` gains the `shared` deep-import exemption
  and names the two linters that enforce its rules;
  `quality.md` adds Steiger to the Web gate list.
- **Tests**: none. These are lint gates, verified by running them.

## Verification

- [ ] `make web-eslint` passes on the current tree with the new rules enabled.
- [ ] `make web-steiger` passes on the current tree.
- [ ] `make lint` runs both.
- [ ] An import from `src/shared` into `src/pages` fails `make web-eslint`.
- [ ] An import of `@/pages/home/model/email` from outside that slice
      fails `make web-eslint`.
- [ ] `@/shared/lib/utils` continues to pass.
- [ ] No violation is reported by both tools.
- [ ] `make web-knip` reports no unused dependency after the install.

## Consequences

- Feature-Sliced Design rules become enforced rather than documented.
- Frontend architecture gains an owner, closing the asymmetry with Deptrac.
- Import violations surface in the editor, before commit.
- Structural drift surfaces before push.
- Two more devDependencies and two more configuration files need maintenance.
- The `shared` entry-point exemption is a deliberate divergence
  from the blanket rule stated in `fsd.md`, and is recorded there.
- Layer additions need no configuration change;
  new ownership questions, such as `@x` cross-imports, need this ADR revisited.
