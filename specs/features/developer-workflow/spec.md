# Developer Workflow

### Requirement: Local development environment is reproducible
The system SHALL provide developer commands that initialise, start, stop, inspect, test, and clean the local development environment without requiring host-level project runtimes.

#### Scenario: Developer initialises local environment
- **WHEN** a developer runs the initialisation command from a fresh checkout with required container tooling available
- **THEN** the system builds the local runtime environment and installs project dependencies

#### Scenario: Developer starts local API
- **WHEN** a developer runs the start command after initialisation
- **THEN** the system serves the API through the configured local hostname

#### Scenario: Developer starts local database
- **WHEN** a developer runs the start command after initialisation
- **THEN** the system starts a PostgreSQL database service for the API connection

#### Scenario: Developer runs all tests
- **WHEN** a developer runs the full test command
- **THEN** the system runs the API behaviour checks and core package specifications

#### Scenario: Developer runs scoped tests
- **WHEN** a developer runs a scoped test command for either the API or core package
- **THEN** the system runs only the requested test scope

#### Scenario: Developer opens runtime shell
- **WHEN** a developer runs the shell command
- **THEN** the system opens an interactive shell inside the project runtime environment

#### Scenario: Developer inspects service logs
- **WHEN** a developer runs the log inspection command
- **THEN** the system streams local service logs

#### Scenario: Developer cleans generated artefacts
- **WHEN** a developer runs the clean command
- **THEN** the system removes generated dependency and cache artefacts that can be recreated

### Requirement: API database connection is configured locally
The system SHALL configure the Symfony API with a local PostgreSQL connection available through Docker Compose without requiring a host-level database service.
