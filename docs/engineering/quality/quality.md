## Quality gates

Every change must pass `make lint` and the relevant test targets
before it is committed:

- PHP: Deptrac (architecture), PHPStan (static analysis),
  Easy Coding Standard (style).
- Web: `tsc`, ESLint, Prettier, knip.
- Infrastructure: the CDK app's TypeScript typecheck.
- Suites: PHPSpec, Vitest, Behat, Playwright, and the frontend build.

Lefthook enforces the gates:
fast checks on `pre-commit`,
Conventional Commit messages on `commit-msg`
(optionally prefixed with a task ID, e.g. `[KEY-123] feat: …`),
and unit tests plus scanners on `pre-push`.
Do not bypass hooks with `--no-verify`.
