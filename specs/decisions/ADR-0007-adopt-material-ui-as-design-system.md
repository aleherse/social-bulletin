# ADR-0007: Adopt Material UI as the Frontend Design System

- Status: Accepted
- Date: 2026-05-16

## Context

The web application (ADR-0006) has no design system. UI components are rendered as unstyled raw HTML elements with no shared colour palette, typography scale, spacing system, or accessible component library. As product features grow, building consistent and accessible UI without a component library becomes expensive, error-prone, and inconsistent.

The project needs a design system that:

- Provides a rich, accessible component library out of the box.
- Supports a centralised theme so brand tokens can be defined once and applied everywhere.
- Integrates naturally with the existing React and TypeScript stack.
- Does not require a global CSS file or class-based naming convention that would conflict with Feature-Sliced Design layer boundaries.

## Decision

Adopt Material UI (MUI) as the design system for the web application.

MUI is installed as `@mui/material` together with its required peer dependencies `@emotion/react` and `@emotion/styled` for CSS-in-JS rendering, and `@mui/icons-material` for the icon set.

A single theme definition module (`src/shared/config/theme.ts`) exports the project theme via `createTheme`. This module has no React dependency so it can be imported by tests, tooling, or future Storybook configuration without rendering overhead. All palette, typography, and spacing decisions are centralised here.

A dedicated `MuiProvider` component wraps `ThemeProvider` and `CssBaseline`. It is composed into the application provider stack alongside `I18nProvider`, following the existing provider pattern. `CssBaseline` normalises browser default styles globally, removing the need for a separate CSS reset file.

The Roboto typeface is loaded from Google Fonts via `<link>` tags in `index.html` using the recommended `preconnect` + `stylesheet` pattern. No npm font package is used, keeping the compiled bundle lean and leveraging shared browser caching of the CDN asset.

UI components across the application SHALL use MUI primitives (`Box`, `Typography`, `Button`, etc.) in preference to raw HTML elements, so that spacing, colour, and typography always derive from the theme.

## Consequences

Positive outcomes:

- Provides a complete, accessible, and well-maintained component library immediately available to all future UI work.
- Centralising theme tokens means brand colour, typography, and spacing changes propagate everywhere from a single file.
- Emotion CSS-in-JS co-locates styles with components, keeping FSD layer boundaries clean without global class names.
- `CssBaseline` eliminates browser style inconsistencies without an additional dependency.
- MUI's accessibility defaults (ARIA roles, keyboard navigation, focus management) reduce the manual accessibility burden on product feature work.

Tradeoffs:

- MUI adds approximately 75 npm packages and ~341 kB to the production bundle (110 kB gzipped). Bundle size must be monitored as the component set grows.
- Emotion CSS-in-JS adds a small runtime cost compared to static CSS. Acceptable at this scale; revisit if performance budgets are introduced.
- Google Fonts CDN loading introduces a third-party network dependency. If offline or strict CSP environments are required later, the font must be self-hosted.
- The MUI version must be kept up to date with breaking-change upgrades managed carefully.
- Custom visual design that deviates significantly from Material Design will require theme overrides and may conflict with MUI's default component opinions.

Follow-ups:

- Define final brand colour tokens in `src/shared/config/theme.ts` once the visual identity is confirmed; replace placeholder defaults.
- Establish a bundle size budget and add a build-time check when the first significant UI feature ships.
- Evaluate self-hosted Roboto if strict Content Security Policy requirements are introduced.
- Add frontend linting rules (per ADR-0006 follow-up) that enforce MUI primitive usage over raw HTML elements where applicable.
