---
name: project-testing
description: Project-specific test writing guidance. Use when writing, changing, reviewing, or debugging tests in packages/core, apps/api, or apps/web; choosing PHPSpec, Behat, Vitest, Testing Library, Playwright, or mtdowling/jmespath.php; creating Behat fixtures; checking API responses in Then steps; or working with DSLR database snapshot restore in tests.
---

# Project Testing

Use this skill when writing or changing tests. This skill owns day-to-day test authoring rules.

## Test Tool Selection

- `packages/core`: use PHPSpec for unit tests of pure PHP domain objects.
- `apps/api`: use Behat with friends-of-behat/symfony-extension for integration and API end-to-end tests.
- `apps/web`: use Vitest for unit tests, Testing Library for component behaviour tests, and Playwright for browser end-to-end journeys.

## General Rules

- Follow red -> green -> refactor for behaviour changes.
- Test observable behaviour, not implementation details.
- Prefer real collaborators inside the boundary under test, EXCEPT in unit tests.
- Mock only true external systems or uncontrollable side effects.
- Keep each test isolated and independent.
- Keep fixtures small, intention-revealing, and deterministic.
- Control clocks, randomness, network boundaries, and other non-deterministic inputs.
- Do not use sleep-based assertions unless time itself is behaviour under test.
- Reproduce regressions with one focused failing test before fixing code.

## `packages/core` PHPSpec

- Write behaviour-first specifications for domain objects.
- Keep specs framework-free; `packages/core` must not depend on Symfony.
- Express behaviour through PHP classes with explicit collaborators.
- Prefer examples named after outcomes and business rules.

## `apps/api` Behat

Use Behat for:

- Integration tests of use-case boundaries, persistence adapters, HTTP handlers, and messaging flows through the real Symfony stack.
- API end-to-end tests of critical HTTP journeys through real API behaviour.

Behat scenarios run inside the Symfony process booted by FriendsOfBehat SymfonyExtension. Inject services directly to Behat contexts (e.g. `AbstractBrowser`). Do not require a running web server for API Behat tests.

### Response Assertions

- Use `mtdowling/jmespath.php` in Behat `Then` steps to query and assert JSON API responses.
- Prefer JMESPath expressions over manual nested array traversal in step definitions.
- Prefer reusable `Then` steps that accept JMESPath expressions from feature files, so expected response shape is explicit in the scenario.
- Keep feature files readable by using concise JMESPath expressions and business-meaningful expected values.
- Assert values at response boundaries, such as status, error code, translated message, resource identifier, collection length, and field presence or absence.

### Tags

- Use `@core` for integration scenarios.
- Use `@e2e` for API end-to-end scenarios.
- Use `@fixtures` only for fixture-loading feature or scenarios.
- Configure default Behat runs to exclude `@fixtures`:

```yaml
gherkin:
    filters:
        tags: ~@fixtures
```

### Folder Structure

```text
apps/
└── api/
    ├── features/
    │   ├── bootstrap/
    │   │   └── ApiContext.php       # Behat context for e2e tests
    │   │   └── OrderContext.php     # Behat context for aggregate
    │   │   └── CustomerContext.php  # Behat context for aggregate
    │   ├── fixtures.feature         # Default system data
    │   ├── Order/                   # Aggregate folder
    │   │   ├── get_order.feature    # use case scenarios
    │   │   └── ship_order.feature   # use case scenarios
    │   └── Customer/
    │       └── ...
    └── src/
        └─ Behat/                         
```

### Behat Fixtures

- Keep a dedicated `fixtures.feature` file for baseline generic reusable `Given` steps.
- Include only state reused by multiple unrelated scenarios.
- Prefer representative sensible examples over exhaustive edge-case data.
- Do not add scenario-specific or single-use data to `fixtures.feature`.
- Put unusual or scenario-specific state directly in that scenario's feature file.
- Every `Given` step must create data by invoking existing application code, such as command handlers, domain factories, or service objects.
- Do not insert rows directly into the database from steps.
- Do not create fixture state by making API calls from steps.
- Name steps by intent and state being created, not mechanics.
- Re-run `make db` whenever schema or fixture dataset changes.

## `apps/web` Frontend Tests

- Use Vitest for pure functions, hooks, and isolated component logic.
- Use Testing Library for component behaviour through accessible queries.
- Prefer queries by role, label, and visible text before falling back to test IDs.
- Use Playwright only for critical browser journeys, production wiring, and cross-page regressions.
- Playwright tests run against the real API.
- Restore the DSLR database snapshot before each Playwright test so browser journeys start from known state.

## Useful Companion Skills

- Use `playwright-best-practices` for detailed Playwright mechanics.
- Use `e2e-testing-patterns` for broader E2E suite design.
