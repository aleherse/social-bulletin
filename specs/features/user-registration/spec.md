# User Registration

## Requirement: Unauthenticated visitors can register via the home page.
A visitor SHALL be able to create a new account without leaving the home page.

### Scenario: Register button visible when unauthenticated
- **WHEN** a visitor loads the home page and `GET /api/me` returns 401
- **THEN** a "Register" button is visible in the top-right of the navigation bar

### Scenario: Register button hidden when authenticated
- **WHEN** a visitor loads the home page and `GET /api/me` returns 200
- **THEN** no "Register" button is rendered

---

## Requirement: Clicking Register opens a modal with the registration form.
The registration modal SHALL contain an email field, a password field, a confirm-password field, a terms-and-conditions checkbox, and a submit button.

### Scenario: Modal opens on Register click
- **WHEN** an unauthenticated visitor clicks the Register button
- **THEN** a dialog opens containing all four form fields and the submit button

---

## Requirement: Passwords must match and terms must be accepted to submit.
The submit button SHALL be disabled until both password fields match and the terms checkbox is checked.

### Scenario: Mismatched passwords disable submit
- **WHEN** the password and confirm-password fields contain different values
- **THEN** the "Create account" button is disabled

### Scenario: Terms not accepted disables submit
- **WHEN** the terms-and-conditions checkbox is unchecked
- **THEN** the "Create account" button is disabled regardless of password state

---

## Requirement: Successful registration creates a user account and authenticates the session.
`POST /api/register` SHALL persist a new user, issue a signed JWT as an httpOnly cookie, and return 201 with `{ "userId": "<uuid-v6>" }`.

### Scenario: Happy path registration
- **WHEN** a valid email, strong password (STRENGTH_MEDIUM), and accepted terms are submitted
- **THEN** the API responds 201, sets an httpOnly `token` cookie, and returns `{ "userId" }` in the body

### Scenario: Post-registration redirect
- **WHEN** registration succeeds
- **THEN** the modal closes, `isAuthenticated` is set to true, and the Register button is no longer visible

---

## Requirement: Duplicate email registration is rejected with 409.
`POST /api/register` SHALL return 409 with `{ "error": "email_already_registered" }` when the email is already in use.

### Scenario: Duplicate email returns inline error
- **WHEN** a visitor submits registration with an email that already has an account
- **THEN** the API returns 409, the modal remains open, and an error message is shown inline

---

## Requirement: Weak password is rejected with 422.
`POST /api/register` SHALL return 422 with a field error for `password` when the password does not meet STRENGTH_MEDIUM.

### Scenario: Weak password blocked
- **WHEN** a password below STRENGTH_MEDIUM is submitted
- **THEN** the API returns 422 with `{ "errors": [{ "field": "password", "message": "..." }] }`

---

## Requirement: Terms not accepted is rejected with 422.
`POST /api/register` SHALL return 422 with a field error for `termsAccepted` when the value is `false`.

### Scenario: Terms refused at API boundary
- **WHEN** a request is submitted with `"termsAccepted": false`
- **THEN** the API returns 422 with `{ "errors": [{ "field": "termsAccepted", "message": "..." }] }`

---

## Requirement: Authenticated users can retrieve their identity.
`GET /api/me` SHALL return 200 with `{ "userId" }` for requests with a valid JWT cookie.

### Scenario: Authenticated access to /api/me
- **WHEN** a request includes a valid JWT `token` cookie
- **THEN** the API returns 200 with `{ "userId": "<uuid-v6>" }`

### Scenario: Unauthenticated access to /api/me
- **WHEN** a request does not include a valid JWT cookie
- **THEN** the API returns 401

---

## Requirement: Terms and conditions are available on a dedicated page.
The system SHALL expose a static `/terms` route containing the terms-and-conditions content.

### Scenario: Terms page accessible
- **WHEN** a visitor navigates to `/terms`
- **THEN** the terms-and-conditions page is rendered with its content

### Scenario: Terms link in registration form
- **WHEN** the registration modal is open
- **THEN** the T&C checkbox label contains a link to `/terms`

---

## Requirement: User accounts have a stable UUID v6 identifier.
Every registered user SHALL be assigned a UUID v6 as their system-wide identifier, stored in `api_users.id`.

### Scenario: UUID v6 assigned at registration
- **WHEN** a user is successfully registered
- **THEN** their `userId` in the response is a valid UUID v6 string
