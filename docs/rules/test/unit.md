# Test / Unit

## test-unit-0001: A spec mirrors its class's path

**WHEN** adding, moving, or deleting a class in `packages/core/src` that has behaviour worth specifying

**THEN** keep one PHPSpec spec per handler or query at the same path under `packages/core/spec/`, named
`<Class>Spec`. A class that moves takes its spec along; a class that is deleted takes its spec with it.

**Example:**

| Class                                                        | Spec                                                              |
|--------------------------------------------------------------|-------------------------------------------------------------------|
| `src/Application/Movement/Command/CreateMovementHandler.php` | `spec/Application/Movement/Command/CreateMovementHandlerSpec.php` |
| `src/Application/Movement/Query/ListMovementsHandler.php`    | `spec/Application/Movement/Query/ListMovementsHandlerSpec.php`    |
| `src/Domain/Movement/MovementService.php` (deleted)          | `spec/Domain/Movement/MovementServiceSpec.php` (deleted)          |

Wrong: `spec/Application/Movement/CreateMovementHandlerSpec.php` once the handler lives in `Command/`.
