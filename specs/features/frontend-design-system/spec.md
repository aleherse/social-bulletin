# Frontend Design System

### Requirement: The application applies a consistent visual design system globally
The system SHALL apply a centralised visual design theme — including colour palette, typography scale, and browser style normalisation — to every screen and component without requiring per-component configuration.

#### Scenario: Design theme is active on all screens
- **WHEN** any screen or component is rendered
- **THEN** it inherits the global colour palette, typography scale, and spacing defined by the design system

#### Scenario: Browser default styles are normalised
- **WHEN** the application loads
- **THEN** browser-default margins, paddings, and font rendering inconsistencies are reset to a consistent baseline across all supported browsers

#### Scenario: Typography uses the defined typeface
- **WHEN** any text is rendered
- **THEN** it uses the design system typeface loaded from an external font service, falling back to system sans-serif fonts if unavailable

### Requirement: Error states use design-system primitives
The system SHALL render error and status messages using design-system-aware components so that they are visually consistent with the rest of the application.

#### Scenario: Service unavailability error uses design system
- **WHEN** the API is unavailable and the error state is displayed
- **THEN** the heading and body text use the typography scale defined by the design system
- **AND** the error region is still announced as an alert to assistive technologies
