# Developer Workflow

### Requirement: Local development environment is reproducible
The system SHALL provide developer commands that initialise, start, stop, inspect, test, and clean the local development environment without requiring host-level project runtimes.

#### Scenario: Developer initialises local environment
- **WHEN** a developer runs the initialisation command from a fresh checkout with required container tooling available
- **THEN** the system builds the local runtime environment, installs project dependencies, and compiles the web frontend

#### Scenario: Developer starts local API
- **WHEN** a developer runs the start command after initialisation
- **THEN** the system serves the API through the configured local hostname

#### Scenario: Developer starts local web application
- **WHEN** a developer runs the start command after initialisation
- **THEN** the system serves the compiled web frontend through the configured local hostname

#### Scenario: Developer starts web frontend dev server
- **WHEN** a developer runs the dev server command after initialisation
- **THEN** the system starts the web frontend with hot-module replacement and serves it on a local port accessible from the host

#### Scenario: Dev server accepts requests under the configured local hostname
- **WHEN** a browser requests the dev server using the configured local hostname
- **THEN** the dev server accepts the connection without blocking it as an unauthorised host

#### Scenario: Developer starts local database
- **WHEN** a developer runs the start command after initialisation
- **THEN** the system starts a PostgreSQL database service for the API connection

#### Scenario: Developer runs all tests
- **WHEN** a developer runs the full test command
- **THEN** the system runs the API behaviour checks, core package specifications, and web frontend behaviour checks

#### Scenario: Developer runs scoped tests
- **WHEN** a developer runs a scoped test command for the API, core package, or web frontend
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

### Requirement: API documentation is accessible during local development
The system SHALL expose the OpenAPI specification for the running API without requiring a separate documentation build step.

#### Scenario: Developer retrieves OpenAPI specification as JSON
- **WHEN** a developer requests `GET /doc.json` from the running API
- **THEN** the system returns a valid OpenAPI 3.x specification document in JSON format describing all documented endpoints

#### Scenario: Developer retrieves OpenAPI specification as YAML
- **WHEN** a developer requests `GET /doc.yaml` from the running API
- **THEN** the system returns a valid OpenAPI 3.x specification document in YAML format describing all documented endpoints

### Requirement: API application log output is accessible during local development
The system SHALL emit structured log output from the API application that is accessible through the local development log inspection command without requiring additional configuration.

#### Scenario: Developer inspects structured API logs
- **WHEN** a developer runs the log inspection command while the API handles requests
- **THEN** the system streams structured log events from the API application including request outcomes and error context
