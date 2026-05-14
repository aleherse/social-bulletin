# Web Startup

### Requirement: Web application checks API availability on startup
The system SHALL check the configured API health endpoint once when the web application loads.

#### Scenario: API is available during startup
- **WHEN** a user loads the web application and the API health check reports `ok`
- **THEN** the web application shows no API availability error

#### Scenario: API is unavailable during startup
- **WHEN** a user loads the web application and the API health check fails or reports a value other than `ok`
- **THEN** the web application shows a safe API availability error

#### Scenario: Startup health check is not repeated
- **WHEN** the web application has completed its startup API health check
- **THEN** the system does not periodically re-check API availability
