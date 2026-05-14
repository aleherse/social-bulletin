# API Availability

### Requirement: API health status is observable
The system SHALL expose a health check that confirms the API is available without invoking any product domain behaviour.

#### Scenario: Health check reports availability
- **WHEN** a client requests the API health check
- **THEN** the system responds successfully with a status value of `ok`

#### Scenario: Health check avoids domain behaviour
- **WHEN** a client requests the API health check
- **THEN** the system responds without requiring authentication, persistence, user data, or product feature state

#### Scenario: Health check allows configured browser clients
- **WHEN** a browser client from an allowed origin requests the API health check
- **THEN** the system permits the browser client to read the health check response
