# ADR-0008: Adopt shadcn/ui as the Frontend Design System

- Status: Accepted
- Date: 2026-06-12

## Context

The web app needs a reusable, accessible UI foundation with clear styling tokens and project-owned component source.

## Decision

Adopt shadcn/ui as the design system for the web application.

shadcn/ui components SHALL be added to the project as source code through the official CLI.

The project SHALL initialise shadcn/ui for the existing React/Vite application and use the project's package runner when invoking the CLI.

The design system SHALL use Tailwind CSS and CSS custom properties as the styling and token foundation. Global CSS is allowed only for Tailwind setup, shadcn/ui theme variables, and application-level base styles. Feature-specific styling must remain local to the owning FSD slice and use semantic tokens rather than raw colour values.

Generated shadcn/ui primitives SHALL live in the shared UI area because they are application-wide reusable building blocks, not product features. Product-specific components may compose those primitives in the appropriate FSD layer, but lower layers must not import from higher layers.

UI components across the application SHALL prefer shadcn/ui primitives and composed shared UI components over raw HTML elements when an equivalent primitive exists. Forms, dialogs, navigation, feedback, data display, and layout components should use the installed shadcn/ui components and follow their accessibility composition rules.

## Consequences

- shadcn/ui becomes the default source for reusable UI primitives.
- Components live as project source and can be customised locally.
- Tailwind CSS and CSS variables define styling tokens.
- Shared UI primitives stay in the FSD shared layer.
- Product components compose primitives in their owning slice.
- Teams must keep tokens semantic and avoid raw colour values.
- shadcn/ui updates require manual review because components are copied into the codebase.
