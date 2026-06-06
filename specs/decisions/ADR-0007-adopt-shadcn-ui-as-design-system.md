# ADR-0007: Adopt shadcn/ui as the Frontend Design System

- Status: Accepted
- Date: 2026-05-16

## Context

The web app needs a shared design system. Raw HTML with no colour palette, typography scale, spacing system, or accessible component primitives will become inconsistent and costly as product features grow.

The project needs a design system that:

- Provides accessible, reusable UI primitives for common product interfaces.
- Supports centralised brand tokens for colour, typography, spacing, radius, and dark-mode behaviour.
- Integrates naturally with the existing React, TypeScript, Vite, and Feature-Sliced Design stack.
- Keeps component implementation visible in the repository so project-specific changes are explicit and reviewable.
- Avoids adopting a highly opinionated visual language that makes custom product design fight the component library.

## Decision

Adopt shadcn/ui as the design system for the web application.

shadcn/ui components are added to the project as source code through the official CLI rather than consumed as a versioned runtime component package. The project SHALL initialise shadcn/ui for the existing React/Vite application and use the project's package runner when invoking the CLI.

The design system SHALL use Tailwind CSS and CSS custom properties as the styling and token foundation. Global CSS is allowed only for Tailwind setup, shadcn/ui theme variables, and application-level base styles. Feature-specific styling must remain local to the owning FSD slice and use semantic tokens rather than raw colour values.

Generated shadcn/ui primitives SHALL live in the shared UI area because they are application-wide reusable building blocks, not product features. Product-specific components may compose those primitives in the appropriate FSD layer, but lower layers must not import from higher layers.

UI components across the application SHALL prefer shadcn/ui primitives and composed shared UI components over raw HTML elements when an equivalent primitive exists. Forms, dialogs, navigation, feedback, data display, and layout components should use the installed shadcn/ui components and follow their accessibility composition rules.

## Consequences

- Provides accessible component primitives while keeping implementation code visible and editable in the repository.
- Centralised CSS variables make brand colour, typography, radius, and dark-mode decisions explicit.
- Tailwind utility classes and semantic tokens support custom visual design.
- Generated source components can be adapted to project needs through normal code review instead of opaque package overrides.
- shadcn/ui's Radix-based composition patterns improve accessibility for dialogs, menus, selects, tabs, and related primitives.
- shadcn/ui adds Tailwind CSS and a global CSS token file, so the project must enforce discipline to avoid feature-level global styling.
- Components are copied into the repository, so upstream updates require deliberate CLI-driven review rather than automatic package upgrades.
- Tailwind utility classes can become noisy if product components are not kept small and composed carefully.
- Accessibility defaults depend on correct component composition; misusing primitives can still create inaccessible UI.
- The project must establish linting and review conventions for semantic tokens, component placement, and Tailwind class usage.
