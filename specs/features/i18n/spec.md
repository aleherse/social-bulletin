# Internationalisation

### Requirement: The system MUST resolve the active locale before serving content.

The system SHALL determine the user's preferred language from available signals,
apply it to all rendered text, and fall back to English when no supported locale
can be identified.

#### Scenario: Preferred locale previously selected

- **WHEN** the user has previously selected a locale
- **THEN** the system uses that stored preference as the active language

#### Scenario: Browser language preference is supported

- **WHEN** no stored locale exists and the browser reports a preferred language
  that the system supports
- **THEN** the system uses the browser-reported language

#### Scenario: Browser language preference is not supported

- **WHEN** no stored locale exists and the browser reports a preferred language
  that the system does not support
- **THEN** the system falls back to English

#### Scenario: No detectable preference

- **WHEN** no stored locale and no browser language preference are available
- **THEN** the system uses English as the active locale

---

### Requirement: The API MUST resolve the request locale from the client's stated language preference.

The system SHALL inspect the client's language preference on every incoming
request and apply a supported locale for that request's lifecycle.

#### Scenario: Client states a supported language

- **WHEN** the request carries a language preference matching a supported locale
- **THEN** the system applies that locale to the request

#### Scenario: Client states an unsupported language

- **WHEN** the request carries a language preference not in the supported locale list
- **THEN** the request locale falls back to English

#### Scenario: No language preference stated

- **WHEN** the request carries no language preference
- **THEN** the request locale defaults to English

---

### Requirement: The system MUST render all user-visible text from translation catalogues.

All user-facing strings SHALL be sourced from the active locale's translation
catalogue. No hardcoded string literals are permitted in rendered output.

#### Scenario: Translation exists in active locale

- **WHEN** a component requests a translation for a known key
- **THEN** the system returns the string for the active locale

#### Scenario: Translation missing in active locale

- **WHEN** a translation key has no entry in the active locale
- **THEN** the system returns the English fallback translation

#### Scenario: Translation missing from all catalogues

- **WHEN** a translation key has no entry in any catalogue
- **THEN** the system returns the key identifier as a visible string

---

### Requirement: The API MUST return translated operational error messages.

Error responses for operational failures SHALL include a user-facing message
string drawn from the resolved request locale's translation catalogue.

#### Scenario: Known operational error occurs

- **WHEN** an operational failure is raised during request handling
- **THEN** the response body includes a translated user-facing error message

#### Scenario: Unexpected system error occurs

- **WHEN** an unhandled failure reaches the system boundary
- **THEN** the response body includes a generic safe message that does not
  expose internal system details

---

### Requirement: The system MUST allow the active locale to be changed at runtime.

#### Scenario: User selects a different language

- **WHEN** the user selects a supported language
- **THEN** the active language changes immediately without a full page reload
- **AND** the selected locale is retained for future sessions
