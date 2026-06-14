# ADR-0013: Adopt Linting and Static Analysis Toolchain

- Status: Accepted
- Date: 2026-06-12

## Context

The project needs automated checks for code style, type safety, architecture boundaries, formatting, and unused code across PHP and TypeScript.

## Decision

Adopt the following linting and static analysis toolchain.

### PHP

Use **deptrac/deptrac** for dependency rule enforcement.

Deptrac defines and checks allowed dependencies between PHP layers and packages. It is the primary guardrail for architectural boundaries, especially between domain code, application code, framework adapters, and infrastructure concerns.

Use **phpstan/phpstan** for PHP static analysis.

PHPStan detects type errors, invalid method calls, unreachable branches, and other defects not reliably caught by syntax checks or coding standards. PHPStan configuration should be strict enough to provide useful feedback while allowing incremental hardening when legacy or generated code makes immediate maximum strictness impractical.

Use **symplify/easy-coding-standard** for PHP coding standards and formatting.

Easy Coding Standard is the canonical PHP style tool. It should own PHP code style, formatting, and fixable coding-standard rules so reviewers do not need to enforce style manually.

Easy Coding Standard SHALL be configured with sensible Symfony/PHP defaults. Easy Coding Standard SHALL not duplicate architectural dependency rules that belong to Deptrac or type-system checks that belong to PHPStan.

Use **xdebug** for PHP debugging and coverage instrumentation.

Xdebug is the canonical PHP runtime extension for step debugging, coverage collection, and profiling. It should be available in development and test containers with this runtime configuration:

```ini
xdebug.mode=debug,coverage,profile
xdebug.start_with_request=trigger
xdebug.client_host = ${XDEBUG_REMOTE_HOST}
xdebug.client_port = ${XDEBUG_REMOTE_PORT}
xdebug.output_dir =  ${XDEBUG_DIR}
```

PHP checks SHALL have clear ownership:

- Deptrac owns package and layer dependency rules.
- PHPStan owns type and static correctness rules.
- Easy Coding Standard owns formatting and fixable coding-standard rules.
- Xdebug owns debugging and coverage instrumentation only, and must stay disabled unless explicitly requested.

### TypeScript

Use **eslint** for TypeScript linting.

ESLint is the canonical TypeScript and React linting tool. It should check code-quality, correctness, React, accessibility, import, and TypeScript-specific rules where appropriate.

ESLint must extend or include `eslint-config-prettier` last so formatting rules owned by Prettier are disabled in ESLint.

Use **prettier** for TypeScript formatting.

Prettier is the canonical formatter for TypeScript, JavaScript, JSON, CSS, and other frontend text formats it supports. ESLint should not duplicate formatting concerns that Prettier owns.

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

Use **knip** for TypeScript project hygiene checks.

Knip is the canonical frontend tool for detecting unused dependencies, unused dev dependencies, unused files, and unused exports. It should complement ESLint and the TypeScript compiler by checking project graph hygiene rather than code-level lint rules or type correctness.

Knip SHALL take into account the existing architecture to avoid flagging false positives.

TypeScript linting SHALL use sensible defaults.

Frontend checks must have clear ownership:

- TypeScript compiler owns type checking and emit correctness.
- ESLint owns code-quality, React, accessibility, import, and maintainability rules.
- Prettier owns deterministic formatting for supported frontend text files.
- Knip owns unused dependency, unused file, and unused export detection for frontend project hygiene.
- `eslint-config-prettier` owns conflict prevention between ESLint and Prettier.

Root file `.editorconfig` SHALL be created with the configuration from Easy Coding Standard and Prettier.

### Execution Contract

The root `Makefile` should expose linting and static analysis through stable developer entrypoints. At minimum, the project should provide commands that can run:

- all linting and static analysis checks together for local pre-merge verification;
- PHP dependency analysis through Deptrac;
- PHP static analysis through PHPStan;
- PHP coding-standard checks through Easy Coding Standard;
- TypeScript type checking through the TypeScript compiler;
- TypeScript linting through ESLint;
- frontend unused dependency, file, and export checks through Knip;
- frontend formatting checks through Prettier.

## Consequences

- PHP architecture rules are enforced by Deptrac.
- PHP static correctness is checked by PHPStan.
- PHP style and formatting are handled by Easy Coding Standard.
- TypeScript and React code quality is checked by ESLint.
- Frontend formatting is handled by Prettier.
- Frontend project hygiene is checked by Knip.
- Xdebug supports debugging and coverage when explicitly enabled.
- Make targets provide stable check entrypoints.
- Tool ownership must stay clear to avoid duplicate or conflicting rules.
- Configs need ongoing maintenance as packages and boundaries change.
