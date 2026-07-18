# Hexagonal Architecture

## CRITICAL: The Dependency Rule

Dependencies point **inward only**. Outer layers depend on inner layers, never the reverse.

```
Infrastructure → Application → Domain
   (adapters)     (use cases)    (core)
```

## Quick Decision Trees

### "Where does this code go?"

```
Where does it go?
├─ Pure business logic, no I/O           → domain/
├─ Orchestrates domain + has side effects → application/
├─ Talks to external systems              → infrastructure/
├─ Defines HOW to interact (interface)    → port (domain or application)
└─ Implements a port                      → adapter (infrastructure)
```

### "Is this an Entity or Value Object?"

```
Entity or Value Object?
├─ Has unique identity that persists → Entity
├─ Defined only by its attributes    → Value Object
├─ "Is this THE same thing?"         → Entity (identity comparison)
└─ "Does this have the same value?"  → Value Object (structural equality)
```

### "Should this be its own Aggregate?"

```
Aggregate boundaries?
├─ Must be consistent together in a transaction → Same aggregate
├─ Can be eventually consistent                 → Separate aggregates
├─ Referenced by ID only                        → Separate aggregates
└─ >10 entities in aggregate                    → Split it
```

**Rule:** One aggregate per transaction. Cross-aggregate consistency via domain events (eventual consistency).

## DDD Building Blocks

| Pattern                 | Purpose                 | Layer       | Key Rule                           |
|-------------------------|-------------------------|-------------|------------------------------------|
| **Entity**              | Identity + behavior     | Domain      | Equality by ID                     |
| **Value Object**        | Immutable data          | Domain      | Equality by value, no setters      |
| **Aggregate**           | Consistency boundary    | Domain      | Only root is referenced externally |
| **Domain Event**        | Record of change        | Domain      | Past tense naming (`OrderPlaced`)  |
| **Repository**          | Persistence abstraction | Domain      | Per aggregate, not per table       |
| **Domain Service**      | Stateless logic         | Domain      | When logic doesn't fit an entity   |
| **Application Service** | Orchestration           | Application | Coordinates domain + infra         |

## Anti-Patterns (CRITICAL)

| Anti-Pattern               | Problem                                                           | Fix                                  |
|----------------------------|-------------------------------------------------------------------|--------------------------------------|
| **Anemic Domain Model**    | Entities are data bags, logic in services                         | Move behavior INTO entities          |
| **Repository per Entity**  | Breaks aggregate boundaries                                       | One repository per AGGREGATE         |
| **Leaking Infrastructure** | Domain imports DB/HTTP libs                                       | Domain has ZERO external deps        |
| **God Aggregate**          | Too many entities, slow transactions                              | Split into smaller aggregates        |
| **Skipping Use Cases**     | Controllers call repositories directly in a use-case architecture | Route through application use cases  |
| **CRUD Thinking**          | Modeling data, not behavior                                       | Model business operations            |
| **Premature CQRS**         | Adding complexity before needed                                   | Start with simple read/write, evolve |
| **Cross-Aggregate TX**     | Multiple aggregates in one transaction                            | Use domain events for consistency    |

## Reference Documentation

| File                                                       | Purpose                                                    |
|------------------------------------------------------------|------------------------------------------------------------|
| [hexagonal/LAYERS.md](hexagonal/LAYERS.md)                | Complete layer specifications                              |
| [hexagonal/DDD-TACTICAL.md](hexagonal/DDD-TACTICAL.md)   | Entities, value objects, aggregates, repository, providers |
