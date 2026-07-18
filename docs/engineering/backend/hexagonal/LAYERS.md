# Layer Structure - Complete Reference

## The Three Layers

| Layer | Responsibility                             | Dependencies |
|-------|--------------------------------------------|--------------|
| **Domain** | Business logic, entities, rules            | None (pure) |
| **Application** | Use cases, orchestration                   | Domain |
| **Infrastructure** | External systems, frameworks, Entry points | Application, Domain |

The key is **dependency direction**: outer layers import inner layers, never the reverse.

Apply **Dependency Inversion Principle**.

---

## Domain Layer (Innermost)

The **heart of the system**. Contains business logic and rules with **minimal external dependencies**.

### Contents

```
core/
└── src/
    └── Domain/
        ├── Order/                      # Aggregate folder
        │   ├── Order.php               # Aggregate root entity
        │   ├── OrderItem.php           # Child entity
        │   ├── OrderStatus.php         # Value object
        │   ├── OrderPlaced.php         # Events
        │   ├── OrderRepository.php     # Write model repository and aggregate root complex operations
        │   ├── OrderProvider.php       # Read model repository
        │   ├── OrderPricing.php        # Service to orchestrate complex operations that involves several aggregates
        │   └── Exceptions/             # Domain error folder
        │       └── OrderNotFound.php   # Domain exception
        ├── Customer/
        │   └── ...
        ├── Product/
        │   └── ...
        └── Shared/
            ├── RecordsEvents.php           # Common aggregated root traits 
            ├── Price.php                   # Base value object
            └── Exceptions/
                └── UnauthorisedAccess.php  # Base domain exception
```

### Rules

1. **No infrastructure concerns** - No ORM decorators, no HTTP libraries, no Symfony Framework imports
2. **Pure business logic** - Only language primitives and core libraries
3. **Rich behavior** - Methods that enforce business rules
4. **Core dependencies** - Doctrine DBAL, Symfony Contracts and well established PHP dependencies

### Example: Repository Implementation

```php
```

### Example: Provider Implementation

```php
```

---

## Application Layer

Orchestrates use cases by coordinating domain objects. Contains **application-specific business rules**.

### Contents

```
core/
└── src/
    └── Application/
        ├── Order/                        # Aggregate folder
        │   ├── PlaceOrder/               # Use case
        │   │   ├── PlaceOrderCommand.php # Command object
        │   │   └── PlaceOrderHandler.php # Command handler
        │   ├── ShipOrder/
        │   │   └── ...
        │   └── GetOrder/
        │       ├── GetOrderQuery.php     # Query object
        │       ├── GetOrderHandler.php   # Query handler
        │       └── OrderDto.php          # Read model
        ├── Customer/                     # Aggregate folder
        │   └── ...
        └── Shared/
            └── JobConnector.php          # Interface with external service
```

### Rules

1. **Inward dependency** - Depends on domain, Symfony Messenger and Symfony Serializer
2. **Orchestrates, doesn't implement** - Calls domain methods
3. **Transaction boundary** - Manages unit of work

### Example: Use Case Handler

```php
```

### Example: Command/Query DTOs

```php
```

---

## Infrastructure Layer

Implements interfaces defined in Domain and Application layers. Contains **all external concerns**.

### Contents

```
Apps/
└── Api/
    └── src/
        ├─ Command/                         
        │   └── SyncCrmCommand.php          # Symfony console command
        ├─ Controller/
        │   ├── Order/                      # Aggregate folder
        │   │   └ GetOrderController.php    # Use case controller
        │   │   └ PlaceOrderController.php  # Use case controller
        │   └── Customer/                   # Aggregate folder
        │       └── ...
        ├─ Entity/                          # Non domain entities
        ├─ EventSubscriber/                 # Capture domain events
        ├─ Repository/                      # Non domain repositories
        ├─ Connector/                       # Integration with external services
        │   ├── AwsJobConnector.php         # Implementation of application interface
        │   └── DevJobConnector.php         # Mocked implementation
        └─ Security/                        # Symfony security    
```

### Rules

1. **Implements ports** - Concrete classes for domain/application interfaces
2. **Contains framework code** - HTTP frameworks, Event orchestration, etc

### Example: Controller Implementation

```php
```
