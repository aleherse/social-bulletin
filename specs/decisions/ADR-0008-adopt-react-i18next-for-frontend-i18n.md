# ADR-0008: Adopt react-i18next for Frontend Internationalisation

- Status: Accepted
- Date: 2026-05-16

## Context

The web app needs frontend translations with language detection and an adapter boundary that fits the shared layer.

## Decision

Adopt **`i18next`**, **`react-i18next`**, and **`i18next-browser-languagedetector`** as the internationalisation stack for `apps/web`.

All translation files live under `apps/web/src/shared/i18n/locales/<lang>/` as static JSON. The `shared/i18n` public API re-exports `useTranslation` so slices never import directly from `react-i18next`, keeping the adapter swappable.

A dedicated `I18nProvider` wraps `i18next`'s `I18nextProvider` and is composed into the application provider stack.

## Consequences

- Frontend text uses i18next translation keys.
- Locale JSON files live under `shared/i18n`.
- App code imports translations through the shared public API.
- Language detection runs in the browser.
- The i18n provider becomes part of app startup.
- Translation keys and locale files must be kept complete and in sync.
