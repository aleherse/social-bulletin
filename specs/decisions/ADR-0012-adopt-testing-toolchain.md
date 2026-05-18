# ADR-0012: Adopt Testing Toolchain

- Status: Accepted
- Date: 2026-05-18

## Context

The monorepo contains three independently testable layers:

- `packages/core` — PHP domain package with pure business logic and no framework dependency.
- `packages/api` — Symfony application exposing HTTP endpoints and owning persistence adapters.
- `apps/web` — React single-page application calling the API over HTTP.

Each layer has different isolation requirements and realistic boundary concerns. Without a documented toolchain, test strategies drift across packages and scenarios share mutable database state, making test suites unreliable and slow to run.

A shared fixture strategy is also required. Integration and end-to-end tests must be able to create representative system state quickly without duplicating setup logic or writing directly to the database outside the application boundary. Database state must be fully reset between scenarios to ensure test isolation.

## Decision

### Backend — `packages/core`

Use **PHPSpec** for unit tests in the `core` package.

PHPSpec encourages behaviour-first specification of PHP objects. It is well-suited to the domain layer because `core` has no framework dependency and all behaviour is expressed through PHP classes with explicit collaborators.

### Backend — `packages/api`

Use **Behat** with the **FriendsOfBehat SymfonyExtension** for integration and end-to-end tests in the `api` package.

Behat scenarios describe observable API behaviour in plain language. The SymfonyExtension boots the real Symfony kernel inside Behat, giving each scenario full access to the application container, HTTP client, database, and message bus without a running web server. This is used for:

- **Integration tests** — use-case boundaries, persistence adapters, HTTP handlers, and messaging flows exercised through the real application stack.
- **End-to-end API tests** — critical HTTP journeys tested through the real API surface from the outside.

### Fixtures Strategy

Create a dedicated `fixtures.feature` file inside `packages/api` (alongside the other Behat feature files) that contains a baseline set of generic, reusable `Given` steps representing sensible default system state.

The fixture dataset is not exhaustive. Scenarios that require specific or unusual state MUST add their own `Given` steps directly in their feature file rather than adding narrow or one-off data to the shared fixture file.

Rules for the fixture file:

- The fixture feature or scenarios MUST use a dedicated Behat tag, e.g. `@fixtures`, and normal test execution MUST exclude that tag so fixture loading is not run as part of the API test suite.
- Include only data that is genuinely reusable across multiple unrelated scenarios.
- Prefer representative, sensible examples over an exhaustive set of edge cases.
- Do not add scenario-specific or single-use data to this file.

Rules for all `Given` steps (both shared fixtures and scenario-specific):

- Every step MUST create data by invoking existing application code — command handlers, domain factories, or service objects — rather than inserting rows directly into the database.
- Steps must be intention-revealing and name the state being created, not the mechanics of how it is created.

### Database Snapshot and Restore

Use **DSLR** (via `pip install DSLR`) to take a database snapshot immediately after the fixture dataset is loaded, and to restore that snapshot before each Behat scenario and before each Playwright scenario.

The `db` Makefile target defined in ADR-0005 creates the database and runs migrations. The testing toolchain extends that target by appending two additional steps: load the `fixtures.feature` dataset, then create the DSLR snapshot. These two steps are a testing concern and must not leak into the core database infrastructure target.

The full `db` sequence is therefore:

1. Create the database. _(owned by ADR-0005)_
2. Run all migrations. _(owned by ADR-0005)_
3. Load the `fixtures.feature` dataset. _(owned by ADR-0012)_
4. Create the DSLR snapshot. _(owned by ADR-0012)_

`make db` must be re-run whenever the schema or fixture dataset changes.

This approach:

- Eliminates scenario-to-scenario state leakage without requiring a full database wipe and reload on every test.
- Keeps scenario setup time proportional to the snapshot restore time rather than the full fixture creation time.
- Allows the fixture snapshot to be created once and reused across all subsequent test runs until explicitly regenerated.

#### Snapshot contract for test execution

Test execution MUST NOT recreate the snapshot. Behat and Playwright treat a valid snapshot as a precondition:

- Each scenario restores the snapshot at startup.
- Behat MUST invoke DSLR restore through the Symfony process booted by FriendsOfBehat SymfonyExtension, for example via a Symfony service or console command resolved from the test container. Behat step definitions and hooks MUST NOT shell out directly to DSLR or bypass the Symfony application process.
- If no snapshot exists, the test run fails with a clear error indicating that `make db` must be run first.
- Snapshot existence is checked, not recreated, so test suite startup time stays minimal.

### Frontend — `apps/web`

Use the following toolchain for the React frontend:

- **Vitest** for unit tests covering pure functions, hooks, and isolated component logic.
- **Testing Library** for component-level behaviour tests via accessible queries (role, label, visible text).
- **Playwright** for end-to-end journeys that run against the real API.

Playwright scenarios restore the database snapshot via the same DSLR mechanism before each test so the frontend E2E suite operates against a known, consistent system state.

## Consequences

Positive outcomes:

- Each layer's toolchain matches its isolation boundary: PHPSpec for pure PHP objects, Behat for application and HTTP boundaries, and Vitest/Testing Library/Playwright for frontend layers.
- The SymfonyExtension allows Behat to test the full Symfony stack in-process without a running web server, keeping API integration tests fast.
- Behat snapshot restore remains inside the same Symfony process as the scenario setup, keeping database reset behaviour visible to the application test container.
- The shared `fixtures.feature` file eliminates duplicated setup across feature files and ensures fixture data is always created through the application boundary.
- The dedicated fixture tag prevents fixture-loading scenarios from being reported or executed as product behaviour tests.
- DSLR snapshot/restore removes per-scenario database overhead and guarantees identical state for every scenario without manual teardown logic.
- Frontend Playwright tests share the same snapshot mechanism as Behat, keeping the state contract consistent across all test types.

Tradeoffs:

- PHPSpec and Behat are separate tools with separate configuration surfaces; developers must understand both.
- The snapshot must be regenerated via `make db` whenever the fixture dataset or schema changes; failing to do so leaves tests running against stale state.
- Fixtures created through application code are coupled to the application boundary. If a domain API changes, fixture steps must also change.
- Playwright requires the API to be running and accessible during test execution, adding infrastructure dependency to the frontend E2E suite.
- Separating snapshot creation (`make db`) from test execution means a missing snapshot produces a hard failure rather than silent misbehaviour; developers must understand this as a setup step, not a bug.

Follow-ups:

- Implement the `db` Makefile target as described in ADR-0005.
- Configure DSLR (or chosen equivalent) in the `packages/api` and `apps/web` test setup scripts to restore the snapshot at the start of each scenario and fail fast with a descriptive error if the snapshot is absent.
- Create the initial `fixtures.feature` file with a representative set of `Given` steps covering the most common system states.
- Document the required setup sequence (`make db` before first test run and after schema or fixture changes) in the project README or a dedicated testing guide.
- Align Makefile test targets (`test-core`, `test-api`, `test-web`) so they restore the snapshot automatically but never create it.
