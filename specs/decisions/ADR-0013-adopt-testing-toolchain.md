# ADR-0013: Adopt Testing Toolchain

- Status: Accepted
- Date: 2026-05-18

## Context

The monorepo contains three independently testable layers:

- `packages/core` — PHP domain package with pure business logic and no framework dependency.
- `packages/api` — Symfony application exposing HTTP endpoints and owning persistence adapters.
- `apps/web` — React single-page application calling the API over HTTP.

Each layer has different isolation needs. Without a documented toolchain, test strategy drifts and scenarios can share mutable database state, making tests unreliable and slow.

A shared fixture strategy is also required. Integration and end-to-end tests must be able to create representative system state quickly without duplicating setup logic or writing directly to the database outside the application boundary. Database state must be fully reset between scenarios to ensure test isolation.

## Decision

### Backend — `packages/core`

Use **PHPSpec** for unit tests in the `core` package.

PHPSpec encourages behaviour-first specification of PHP objects. It is well-suited to the domain layer because `core` has no framework dependency and all behaviour is expressed through PHP classes with explicit collaborators.

### Backend — `apps/api`

Use **Behat** with the **friends-of-behat/symfony-extension** for integration and end-to-end tests in the `api` package.

Behat scenarios describe observable API behaviour in plain language. The SymfonyExtension boots the real Symfony kernel inside Behat, giving each scenario full access to the application container, HTTP client, database, and message bus without a running web server. This is used for:

- **Integration tests** — (`@integration` tag) use-case boundaries, persistence adapters, HTTP handlers, and messaging flows exercised through the real application stack.
- **End-to-end API tests** — (`@e2e` tag) critical HTTP journeys tested through the real API surface from the outside.

Install **mtdowling/jmespath.php**, create a `JmespathContext.php` Behat context file and use it to add generic `Then` steps that check response data.

All scenario `Given` steps must create state through already implemented application code. They must not write directly to database tables with raw SQL or DBAL inserts that bypass the application boundary. If required setup cannot be expressed through existing code, implement the missing application capability first or keep the scenario smaller.

### Fixtures Strategy

Create a dedicated `fixtures.feature` (`@fixtures` tag) file inside `packages/api` that contains a baseline set of generic, reusable `Given` steps representing sensible default system state.

The fixture dataset is not exhaustive. Scenarios that require specific or unusual state MUST add their own `Given` steps directly in their feature file rather than adding narrow or one-off data to the shared fixture file.

Rules for the fixture file:

- Include only data that is genuinely reusable across multiple unrelated scenarios.
- Prefer representative, sensible examples over an exhaustive set of edge cases.
- Do not add scenario-specific or single-use data to this file.

Use this snippet to not execute the `@fixtures` tag scenarios by default:

```yaml
gherkin:
    filters:
        tags: ~@fixtures
```

### Database Snapshot and Restore

Use **DSLR** (via `pip install DSLR`) to work with database snapshots. Using `dslr snapshot fixtures` to create the snapshot and `dslr restore fixtures` to restore the snapshot.

The `db` Makefile target creates the database, runs migrations, loads fixture data, and creates a DSLR snapshot.

The full `db` sequence is therefore:

1. Create the database.
2. Run all migrations.
3. Load the `fixtures.feature` dataset through application behaviour.
4. Create the DSLR snapshot.

`make db` must be re-run whenever the schema or fixture dataset changes.

This approach:

- Eliminates scenario-to-scenario state leakage without requiring a full database wipe and reload on every test.
- Keeps scenario setup time proportional to the snapshot restore time rather than the full fixture creation time.
- Allows the fixture snapshot to be created once and reused across all subsequent test runs until explicitly regenerated.

#### Snapshot contract for test execution

Test execution MUST NOT recreate the snapshot. Behat and Playwright treat a valid snapshot as a precondition:

- Each scenario restores the snapshot at startup.
- Behat MUST invoke snapshot/restore through the `symfony/process` component, via a console command resolved from the test container.

### Frontend — `apps/web`

Use the following toolchain for the React frontend:

- **Vitest** for unit tests covering pure functions, hooks, and isolated component logic.
- **Testing Library** for component-level behaviour tests via accessible queries (role, label, visible text).
- **Playwright** for end-to-end journeys that run against the real API.

Playwright scenarios restore the database snapshot via the same DSLR mechanism before each test so the frontend E2E suite operates against a known, consistent system state.

## Consequences

- Each layer's toolchain matches its isolation boundary: PHPSpec for pure PHP objects, Behat for application and HTTP boundaries, and Vitest/Testing Library/Playwright for frontend layers.
- The SymfonyExtension allows Behat to test the full Symfony stack in-process without a running web server, keeping API integration tests fast.
- Behat snapshot restore remains inside the same Symfony process as the scenario setup, keeping database reset behaviour visible to the application test container.
- The shared `fixtures.feature` file eliminates duplicated setup across feature files and ensures fixture data is always created through implemented application code.
- The dedicated fixture tag prevents fixture-loading scenarios from being reported or executed as product behaviour tests.
- DSLR snapshot/restore removes per-scenario database overhead and guarantees identical state for every scenario without manual teardown logic.
- Frontend Playwright tests share the same snapshot mechanism as Behat, keeping the state contract consistent across all test types.
- PHPSpec and Behat are separate tools with separate configuration surfaces; developers must understand both.
- The snapshot must be regenerated via `make db` whenever the fixture dataset or schema changes; failing to do so leaves tests running against stale state.
- Fixtures created through application code are coupled to those boundaries. If application APIs or repositories change, fixture steps must also change.
- Playwright requires the API to be running and accessible during test execution, adding infrastructure dependency to the frontend E2E suite.
- Separating snapshot creation (`make db`) from test execution means a missing snapshot produces a hard failure rather than silent misbehaviour; developers must understand this as a setup step, not a bug.
- Configure DSLR in the `packages/api` and `apps/web` test setup scripts to restore the snapshot at the start of each scenario.
- Create the initial `fixtures.feature` file with representative `Given` steps that reuse implemented code and repositories for common system states.
- Document the required setup sequence (`make db` before first test run and after schema or fixture changes) in the project README or a dedicated testing guide.
