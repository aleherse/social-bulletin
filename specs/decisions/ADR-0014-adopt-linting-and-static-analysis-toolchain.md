# ADR-0014: Adopt Linting and Static Analysis Toolchain

- Status: Accepted
- Date: 2026-06-02

## Context

The monorepo contains PHP backend packages and a TypeScript frontend application. Each language ecosystem needs automated checks that catch code style drift, dependency boundary violations, typing mistakes, and common maintainability issues before changes are merged.

Without a documented linting and static analysis toolchain, contributors may choose overlapping or inconsistent tools per package. That increases configuration drift, weakens CI feedback, and makes code review carry concerns that should be handled automatically.

The selected tools must support the existing project shape:

- `packages/core` and `packages/api` use PHP and need architectural dependency checks, static type analysis, automated coding-standard checks, and local debugging or coverage support.
- `apps/web` uses TypeScript and needs linting, deterministic formatting, and checks for unused dependencies, files, and exports.

## Decision

Adopt the following linting and static analysis toolchain.

### PHP

Use **deptrac/deptrac** for dependency rule enforcement.

Deptrac defines and checks allowed dependencies between PHP layers and packages. It is the primary guardrail for architectural boundaries, especially between domain code, application code, framework adapters, and infrastructure concerns.

Use **phpstan/phpstan** for PHP static analysis.

PHPStan detects type errors, invalid method calls, unreachable branches, and other defects not reliably caught by syntax checks or coding standards. PHPStan configuration should be strict enough to provide useful feedback while allowing incremental hardening when legacy or generated code makes immediate maximum strictness impractical.

Use **symplify/easy-coding-standard** for PHP coding standards and formatting.

Easy Coding Standard is the canonical PHP style tool. It should own PHP code style, formatting, and fixable coding-standard rules so reviewers do not need to enforce style manually.

Configure Easy Coding Standard with sensible Symfony/PHP defaults: PSR-12-compatible formatting, ordered imports, unused import removal, short array syntax, strict type declarations where safe, and fixable readability rules. Easy Coding Standard must not duplicate architectural dependency rules that belong to Deptrac or type-system checks that belong to PHPStan.

Use **xdebug** for PHP debugging and coverage instrumentation.

Xdebug is the canonical PHP runtime extension for step debugging and coverage collection. It should be available in development and test containers via `xdebug.start_with_request=trigger`.

PHP tool dependencies must be declared in the relevant Composer manifests or container images. At minimum, PHP packages that participate in the backend quality gate must have access to `deptrac/deptrac`, `phpstan/phpstan`, `symplify/easy-coding-standard`, and the `xdebug` extension in development or test runtime images.

PHP checks must have clear ownership:

- Deptrac owns package and layer dependency rules.
- PHPStan owns type and static correctness rules.
- Easy Coding Standard owns formatting and fixable coding-standard rules.
- Xdebug owns debugging and coverage instrumentation only, and must stay disabled unless explicitly requested.

### TypeScript

Use **eslint** for TypeScript linting.

ESLint is the canonical TypeScript and React linting tool. It should check code-quality, correctness, React, accessibility, import, and TypeScript-specific rules where appropriate.

Use **prettier** for TypeScript formatting.

Prettier is the canonical formatter for TypeScript, JavaScript, JSON, CSS, and other frontend text formats it supports. ESLint should not duplicate formatting concerns that Prettier owns.

Use **knip** for TypeScript project hygiene checks.

Knip is the canonical frontend tool for detecting unused dependencies, unused dev dependencies, unused files, and unused exports. It should complement ESLint and the TypeScript compiler by checking project graph hygiene rather than code-level lint rules or type correctness.

Configure Prettier with small, deterministic defaults for the frontend:

- `semi: true`
- `singleQuote: true`
- `jsxSingleQuote: false`
- `trailingComma: "all"`
- `printWidth: 100`
- `tabWidth: 2`
- `useTabs: false`
- `bracketSpacing: true`
- `bracketSameLine: false`
- `arrowParens: "always"`
- `endOfLine: "lf"`

Prettier must ignore generated and dependency output such as `dist`, `build`, `coverage`, `node_modules`, and `.vite`.

The frontend package must include the required integration dependencies for the selected ESLint and Prettier responsibilities. At minimum, `apps/web` must declare:

- `eslint`
- `typescript-eslint`
- `typescript`
- `eslint-plugin-react-hooks`
- `eslint-plugin-react-refresh`
- `eslint-plugin-jsx-a11y`
- `prettier`
- `eslint-config-prettier`
- `knip`

If import boundary or unresolved-import rules are enabled, `apps/web` must also declare the matching import tooling, such as `eslint-plugin-import` and `eslint-import-resolver-typescript`. These dependencies are required together because TypeScript path aliases and type-aware import resolution must be understood by ESLint to avoid false positives.

ESLint must extend or include `eslint-config-prettier` last so formatting rules owned by Prettier are disabled in ESLint. The project must not use ESLint formatting rules, `eslint-plugin-prettier`, or Prettier-as-an-ESLint-rule by default; formatting should run as a separate Prettier check.

TypeScript linting should use sensible defaults: recommended JavaScript rules, recommended TypeScript rules, React Hooks rules, React Refresh rules for Vite development safety, accessibility rules for JSX, and project-specific import or module-boundary rules only where they reflect actual architecture decisions. Type-aware ESLint rules may be enabled when they provide useful correctness feedback, but must be configured with the project TypeScript configuration and kept fast enough for local use.

Knip should be configured with the frontend package entrypoints, Vite configuration, test setup, and generated-output ignores so it reports real unused project surface without flagging framework-required files, generated assets, or externally referenced entrypoints as false positives.

Frontend checks must have clear ownership:

- TypeScript compiler owns type checking and emit correctness.
- ESLint owns code-quality, React, accessibility, import, and maintainability rules.
- Prettier owns deterministic formatting for supported frontend text files.
- Knip owns unused dependency, unused file, and unused export detection for frontend project hygiene.
- `eslint-config-prettier` owns conflict prevention between ESLint and Prettier.

### Execution Contract

The root `Makefile` from ADR-0003 should expose linting and static analysis through stable developer entrypoints. At minimum, the project should provide commands that can run:

- all linting and static analysis checks together for local pre-merge verification;
- PHP dependency analysis through Deptrac;
- PHP static analysis through PHPStan;
- PHP coding-standard checks through Easy Coding Standard;
- PHP debugging or coverage commands with Xdebug enabled when required;
- TypeScript type checking through the TypeScript compiler;
- TypeScript linting through ESLint;
- frontend unused dependency, file, and export checks through Knip;
- frontend formatting checks through Prettier.

CI must run the full linting and static analysis suite before accepting changes. Automated fix commands may exist for developer convenience, but CI should use check-only commands.

The full check order should be deterministic and fail fast: PHP dependency analysis, PHP static analysis, PHP coding-standard check, TypeScript type check, TypeScript lint check, Knip project hygiene check, then Prettier format check. Tool-specific ordering may be adjusted for performance in CI, but failures must remain attributable to one owning tool.

## Consequences

Positive outcomes:

- PHP architecture rules become executable through Deptrac instead of living only in documentation.
- PHP type and correctness issues are caught earlier through PHPStan.
- PHP style and fixable coding-standard issues are handled consistently through Easy Coding Standard.
- PHP debugging and coverage workflows use a standard runtime extension through Xdebug.
- TypeScript linting and formatting responsibilities are split clearly between ESLint and Prettier.
- TypeScript dependency, file, and export hygiene becomes executable through Knip instead of relying on manual review.
- Required integration dependencies prevent ESLint, TypeScript, and Prettier from producing conflicting or misleading feedback.
- Prettier defaults reduce frontend formatting debate while keeping configuration small.
- CI gains a stable quality gate for both backend and frontend code.
- Code review can focus more on behaviour, design, and correctness rather than repeatable style checks.

Tradeoffs:

- The project now has multiple quality-tool configurations to maintain.
- Strict static analysis may require baselines or incremental rule adoption if existing code cannot satisfy all checks immediately.
- ESLint and Prettier must be configured to avoid conflicting formatting rules.
- Type-aware ESLint and import-resolution rules may require extra dependencies and careful performance monitoring.
- Knip requires maintained entrypoint and ignore configuration to avoid false positives for framework, generated, or externally referenced files.
- Deptrac rules must be updated when package boundaries or architecture decisions change.
- Xdebug adds runtime overhead and must stay opt-in for normal development commands.
- Developers need the Makefile or documented package commands to understand which checks to run locally.

Follow-ups:

- Add required tool dependencies and PHP extension setup to the relevant PHP and TypeScript package manifests or container images.
- Add configuration files for Deptrac, PHPStan, Easy Coding Standard, ESLint, Prettier, Knip, and Xdebug runtime modes.
- Add Makefile targets for aggregate checks and tool-specific checks.
- Wire the aggregate linting and static analysis command into CI.
- Document local usage in the project README or a dedicated development guide.
