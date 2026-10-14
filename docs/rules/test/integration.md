# Test / Integration

## test-integration-0001: Behat contexts arrange through the buses

**WHEN** a Behat context in `apps/api/tests/Behat` needs to create state or read an entity the scenario depends on

**THEN** go through `App\Messenger\CommandBus` for writes and `App\Messenger\QueryBus` for reads, using the same
commands and queries as the controllers. Never inject a core service, repository, or provider. `Connection` is only
for asserting persisted rows and resetting the database, not for arranging state.

**Example:**

| Wrong                                                      | Right                                                                  |
|------------------------------------------------------------|------------------------------------------------------------------------|
| `UserService::currentUser($email)` in `MovementContext`    | `$this->queryBus->dispatch(new CurrentUserQuery($email))`              |
| inserting a user row through `Connection` to sign someone in | `$this->commandBus->dispatch(SignInCommand::fromPayload([...]))`     |
| `Connection::fetchAssociative()` asserting a saved movement | unchanged: assertions may read the table                              |
