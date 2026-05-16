# ADR-0008: Adopt react-i18next for Frontend Internationalisation

- Status: Accepted
- Date: 2026-05-16

## Context

The React frontend (`apps/web`) has no internationalisation infrastructure.
All user-visible strings are hardcoded in components. Introducing i18n now,
while the codebase is a skeleton, avoids expensive retrofitting later.

A library is needed to manage translation catalogues, locale detection, and
runtime language switching in a way that fits the Feature-Sliced Design
structure already adopted (ADR-0006).

Two realistic options were considered:

**react-i18next** (i18next ecosystem)
- De-facto standard for React i18n with the largest ecosystem.
- Plugin architecture separates locale detection, resource loading, and interpolation into independent concerns.
- `useTranslation` hook gives components a clean, side-effect-free interface.
- Namespace support maps naturally to FSD slices owning their translation domains.
- `i18next-browser-languagedetector` handles `localStorage` + `navigator.language` detection without custom code.
- TypeScript key-checking via `CustomTypeOptions` augmentation.

**react-intl** (FormatJS)
- ICU message format built-in, strong for pluralisation and date/number formatting.
- Heavier API surface (`<FormattedMessage>` JSX or `useIntl` hook everywhere).
- No plugin-based locale detection; detection logic must be written by hand.
- Namespace-per-slice ownership is not a first-class concept.
- Better fit for applications where ICU pluralisation and number formatting are day-one requirements.

## Decision

Adopt **`i18next`**, **`react-i18next`**, and **`i18next-browser-languagedetector`**
as the internationalisation stack for `apps/web`.

All translation files live under `apps/web/src/shared/i18n/locales/<lang>/` as
static JSON. The `shared/i18n` public API re-exports `useTranslation` so slices
never import directly from `react-i18next`, keeping the adapter swappable.

## Consequences

Positive:
- Locale detection, switching, and translation lookup work out of the box with minimal configuration.
- FSD slices remain unaware of the specific i18n library through the `shared/i18n` re-export boundary.
- Static JSON resources avoid a network round-trip and simplify the initial build.
- TypeScript augmentation catches missing or renamed keys at compile time.

Tradeoffs:
- ICU plural syntax requires an additional i18next plugin (`i18next-icu`) if needed in future; not included now.
- A second `node_modules` package surface to keep updated.
- Static resource loading means new locales require a rebuild rather than a remote catalogue fetch.

Follow-ups:
- Add `i18next-icu` if plural/ordinal formatting requirements emerge.
- Evaluate remote catalogue loading (e.g., `i18next-http-backend`) if translation files grow large enough to warrant code-splitting.
- Propose this ADR to Airsync as team-scoped memory.
