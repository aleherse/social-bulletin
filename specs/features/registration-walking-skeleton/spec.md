# Registration walking skeleton

### Requirement: Anonymous visitors can continue with email only
The system SHALL show an email-only continuation form when the current browser session is not authenticated.

#### Scenario: Anonymous visitor sees email form
- **WHEN** a visitor opens the homepage without a valid authenticated session
- **THEN** the system SHALL show a form asking only for an email address.

### Requirement: Email submission creates or authenticates the user
The system SHALL accept an email address and authenticate the browser session for the matching user, creating the user first when needed.

#### Scenario: Email does not exist
- **WHEN** an anonymous visitor submits a valid email address that does not belong to an existing user
- **THEN** the system SHALL create a user for that email address.
- **THEN** the system SHALL authenticate the browser session for that user.

#### Scenario: Email already exists
- **WHEN** an anonymous visitor submits a valid email address that belongs to an existing user
- **THEN** the system SHALL authenticate the browser session for the existing user.

#### Scenario: Email is invalid
- **WHEN** a visitor submits an invalid email address
- **THEN** the system SHALL reject the request with a safe validation message.

### Requirement: Authenticated visitors are greeted
The system SHALL show an authenticated hello view that greets the current user by email address.

#### Scenario: Session is valid on page load
- **WHEN** a visitor opens the homepage with a valid authenticated session
- **THEN** the system SHALL show a hello view containing the authenticated user's email address.

#### Scenario: Authentication succeeds after email submission
- **WHEN** a visitor submits an email address and authentication succeeds
- **THEN** the system SHALL navigate to or re-render as the hello view.

### Requirement: Current session can be checked
The system SHALL expose whether the browser session has a valid authenticated user.

#### Scenario: Session is valid
- **WHEN** the current session is checked with a valid authentication cookie
- **THEN** the system SHALL return the authenticated user's email address.

#### Scenario: Session is missing or invalid
- **WHEN** the current session is checked without valid authentication
- **THEN** the system SHALL report that authentication is required.

### Requirement: Authenticated users can log out
The system SHALL allow authenticated users to clear the authenticated browser session.

#### Scenario: User logs out
- **WHEN** an authenticated user activates the logout control
- **THEN** the system SHALL clear the authenticated session cookie.
- **THEN** the system SHALL show the email-only continuation form.

### Requirement: Authentication scope stays minimal
The walking skeleton SHALL NOT include passwords, email verification, refresh tokens, roles, or account-management screens.

#### Scenario: User continues with email only
- **WHEN** the user follows the walking skeleton journey
- **THEN** the system SHALL require no password, email verification, role selection, refresh-token action, or account-management step.
