# Test / Integration

## test-integration-0001: Tests arrange through the buses

**WHEN** a Behat context in `apps/api/tests/Behat` or a PHPUnit test in `apps/api/tests/Integration` needs to create
state or read an entity the test depends on

**THEN** go through `App\Messenger\CommandBus` for writes and `App\Messenger\QueryBus` for reads, using the same
commands and queries as the controllers. Never inject a core service, repository, or provider to arrange state — the
class under test is the only exception. `Connection` is only for asserting persisted rows and resetting the database,
not for arranging state.

**Example:**

| Wrong                                                        | Right                                                              |
|--------------------------------------------------------------|--------------------------------------------------------------------|
| `UserService::currentUser($email)` in `MovementContext`      | `$this->queryBus->dispatch(new CurrentUserQuery($email))`          |
| inserting a user row through `Connection` to sign someone in | `$this->commandBus->dispatch(SignInCommand::fromPayload([...]))`   |
| `Connection::fetchAssociative()` asserting a saved movement  | unchanged: assertions may read the table                           |
| `MovementRepositoryTest` signing the author in by SQL        | `IntegrationTestCase::signIn()`, which dispatches `SignInCommand`  |

## test-integration-0002: Cases below HTTP are PHPUnit tests

**WHEN** a case in `apps/api` needs the kernel or the database but no HTTP request — a repository, a provider, bus
wiring, event publishing

**THEN** write a `KernelTestCase` in `apps/api/tests/Integration/` extending `IntegrationTestCase`, run by
`make api-integration`. Behat scenarios in `apps/api/features/` assert only the HTTP contract: status, headers, JSON
shape. Never prove one case in both tools.

**Example:**

| Wrong                                                           | Right                                                                       |
|-----------------------------------------------------------------|-----------------------------------------------------------------------------|
| a Behat scenario asserting an unknown category is not stored    | `MovementRepositoryTest::testItRejectsACategoryOutsideTheManagedList`       |
| a Behat step reading `bulletin.movements` to compare timestamps | `MovementRepositoryTest::testSavingAgainUpdatesTheRowAndMovesUpdatedAt…`    |
| moving the blank-title 400 scenario to PHPUnit                  | stays in Behat: a status mapping is an HTTP fact                            |

## test-integration-0003: The snapshot is restored through DatabaseSnapshot

**WHEN** a test harness in `apps/api` restores the DSLR `fixtures` snapshot before a test or scenario

**THEN** call `App\Tests\Support\DatabaseSnapshot::restore()` — from `DatabaseContext` for Behat and
`IntegrationTestCase` for PHPUnit — and never re-implement the `dslr … restore` call.

**Example:**

| Wrong                                                      | Right                                                          |
|------------------------------------------------------------|----------------------------------------------------------------|
| `exec('dslr … restore fixtures')` in a new test case       | `DatabaseSnapshot::restore(self::service(Connection::class))`  |
| a second restore helper under `tests/Integration/`         | the one class in `tests/Support/`                              |
