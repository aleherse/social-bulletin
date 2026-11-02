# Domain / Events

## domain-events-0001: Aggregates record events, repositories publish them

**WHEN** a domain fact other parts of the system may react to occurs in an aggregate

**THEN** the aggregate `use`s `Domain\Helper\RecordsEvents` and calls `recordThat()` where the fact occurs,
never in `restore()`. Its repository's `save()` ends with `$this->events->dispatch(...$aggregate->pullEvents())`
through the `Domain\Helper\EventDispatcher` port (`domain-helpers-0002`), so nothing is published unless the write
succeeded. Handlers and controllers never dispatch.

**Example:**

| Wrong                                                    | Right                                                      |
|----------------------------------------------------------|------------------------------------------------------------|
| `CreateMovementHandler` dispatches after `save()`        | `MovementRepository::save()` dispatches after its `INSERT` |
| dispatching before the `INSERT` or inside the `catch`    | dispatching as the last statement of `save()`              |
| `Movement::restore()` recording `MovementHasBeenCreated` | only `Movement::draft()` records it                        |

## domain-events-0002: Events are past-tense classes in the aggregate's Events folder

**WHEN** adding a domain event

**THEN** create a `final readonly` class implementing `Domain\Helper\DomainEvent` in `Domain/<Aggregate>/Events/`,
named for what happened (`<Aggregate>HasBeen<Done>`), carrying the ids a listener needs and no behaviour.

**Example:**

| Wrong                                           | Right                                                              |
|-------------------------------------------------|--------------------------------------------------------------------|
| `Domain/Movement/CreateMovementEvent.php`       | `Domain/Movement/Events/MovementHasBeenCreated.php`                |
| an event holding the whole `Movement` aggregate | `MovementHasBeenCreated(MovementId $movementId, UserId $authorId)` |
