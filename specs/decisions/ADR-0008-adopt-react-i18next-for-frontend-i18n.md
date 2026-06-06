# ADR-0008: Adopt react-i18next for Frontend Internationalisation

- Status: Accepted
- Date: 2026-05-16

## Context

The React frontend needs translation catalogues, locale detection, and runtime language switching before product UI is implemented.

`react-i18next` fits this need because it has a large React ecosystem, plugin-based detection/loading, namespace support, and a small hook API. `react-intl` is stronger for day-one ICU-heavy formatting but requires more component-level plumbing and custom detection.

## Decision

Adopt **`i18next`**, **`react-i18next`**, and **`i18next-browser-languagedetector`** as the internationalisation stack for `apps/web`.

All translation files live under `apps/web/src/shared/i18n/locales/<lang>/` as static JSON. The `shared/i18n` public API re-exports `useTranslation` so slices never import directly from `react-i18next`, keeping the adapter swappable.

A dedicated `I18nProvider` wraps `i18next`'s `I18nextProvider` and is composed into the application provider stack.

## Consequences

- Locale detection, switching, and translation lookup work out of the box with minimal configuration.
- FSD slices remain unaware of the specific i18n library through the `shared/i18n` re-export boundary.
- Static JSON resources avoid a network round-trip and simplify the initial build.
- TypeScript augmentation catches missing or renamed keys at compile time.
- ICU plural syntax requires an additional i18next plugin (`i18next-icu`) if needed in future; not included now.
- Static resource loading means new locales require a rebuild rather than a remote catalogue fetch.
- Add `i18next-icu` if plural/ordinal formatting requirements emerge.
- Evaluate remote catalogue loading (e.g., `i18next-http-backend`) if translation files grow large enough to warrant code-splitting.
