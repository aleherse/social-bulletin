# ADR-0015: Adopt Testing Toolchain

- Status: Accepted
- Date: 2026-06-12

## Context

The project needs fast unit feedback, realistic API coverage, reliable browser journeys, and repeatable database state across test runs.

## Decision

### Backend — `packages/core`

Use **PHPSpec** for unit tests in the `core` package.

Entry `php-unit` SHALL be added to Makefile.

### Backend — `apps/api`

Use **Behat** with the **friends-of-behat/symfony-extension** for integration and API tests in the `api` package.

`Then` steps SHALL use **mtdowling/jmespath.php** query language to check JSON API responses.

`Given` steps SHALL use application code to create pre-existing data.

Entry `api-tests` shall be added to Makefile.

### Fixtures Strategy

Create a dedicated `fixtures.feature` (using `@fixtures` tag) file that contains a baseline set of generic, reusable `Given` steps representing sensible default system state.

The fixture dataset is not exhaustive. Scenarios that require specific or unusual state SHALL add their own `Given` steps directly in their feature file rather than adding narrow or one-off data to the shared fixture file.

Rules for the fixture file:

- Include only data that is genuinely reusable across multiple unrelated scenarios.
- Prefer representative, sensible examples over an exhaustive set of edge cases.
- Do not add scenario-specific or single-use data to this file.

Use this snippet in `behat.yml.dist` to not execute the `@fixtures` tag scenarios by default:

```yaml
gherkin:
    filters:
        tags: ~@fixtures
```

### Database Snapshot and Restore

Use **DSLR** (via `pip install DSLR`) to work with database snapshots. Using `dslr snapshot fixtures` to create the snapshot and `dslr restore fixtures` to restore the snapshot.

The full `make db` sequence SHALL be:

1. Create the database.
2. Run all migrations.
3. Load the `fixtures.feature` dataset.
4. Create the DSLR snapshot.

Test execution SHALL NOT recreate the snapshot.

Behat SHALL restore the snapshot before each scenario.

### Frontend — `apps/web`

Use the following toolchain for the React frontend:

- **Vitest** for unit tests covering pure functions, hooks, and isolated component logic.
- **Testing Library** for component-level behaviour tests via accessible queries (role, label, visible text).
- **Playwright** for end-to-end journeys that run against the real API.

Entry `web-unit` (Vitest), `web-e2e` (Playwright) and `web-e2e-ui` (Playwright UI) SHALL be added to Makefile.

Playwright tests SHALL build the frontend before execution and restore the snapshot before each scenario.

## Consequences

- Core behaviour is tested with PHPSpec.
- API behaviour is tested with Behat against Symfony.
- JSON assertions use JMESPath for clear response checks.
- Shared fixtures stay reusable and scenario-specific data stays local.
- DSLR snapshots keep database tests repeatable.
- Frontend unit and component tests use Vitest and Testing Library.
- Critical browser journeys use Playwright against the real API.
- Make targets become the standard test entrypoints.
- Test data, snapshots, and tool configs need ongoing maintenance.
