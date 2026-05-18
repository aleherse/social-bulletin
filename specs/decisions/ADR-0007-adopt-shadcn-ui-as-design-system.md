# ADR-0007: Adopt shadcn/ui as the Frontend Design System

- Status: Accepted
- Date: 2026-05-16

## Context

The web application (ADR-0006) has no design system. UI components are rendered as unstyled raw HTML elements with no shared colour palette, typography scale, spacing system, or accessible component library. As product features grow, building consistent and accessible UI without a component library becomes expensive, error-prone, and inconsistent.

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

Positive outcomes:

- Provides accessible component primitives while keeping implementation code visible and editable in the repository.
- Centralised CSS variables make brand colour, typography, radius, and dark-mode decisions explicit.
- Tailwind utility classes and semantic tokens support custom visual design.
- Generated source components can be adapted to project needs through normal code review instead of opaque package overrides.
- shadcn/ui's Radix-based composition patterns improve accessibility for dialogs, menus, selects, tabs, and related primitives.

Tradeoffs:

- shadcn/ui adds Tailwind CSS and a global CSS token file, so the project must enforce discipline to avoid feature-level global styling.
- Components are copied into the repository, so upstream updates require deliberate CLI-driven review rather than automatic package upgrades.
- Tailwind utility classes can become noisy if product components are not kept small and composed carefully.
- Accessibility defaults depend on correct component composition; misusing primitives can still create inaccessible UI.
- The project must establish linting and review conventions for semantic tokens, component placement, and Tailwind class usage.

Follow-ups:

- Initialise shadcn/ui in the web application when the first styled frontend implementation begins.
- Configure Tailwind CSS and the shadcn/ui theme variables in the web application's existing global stylesheet entrypoint.
- Place generated shared primitives under the FSD shared UI area and expose them through a clear public API.
- Define final brand tokens once visual identity is confirmed; replace placeholder theme defaults.
- Add frontend linting or review rules that prefer installed shadcn/ui primitives over raw HTML where applicable.
- Establish a process for reviewing shadcn/ui upstream component updates with CLI diff or dry-run output before applying changes.
