# ADR-0000: Project Documentation Constants

- Status: Accepted
- Date: 2026-06-06

## Context

Project records repeat project names, hostnames, namespaces, schemas, and deployment prefixes. A small shared vocabulary keeps those values discoverable and reduces rename drift.

## Decision

Adopt these documentation constants.

| Constant            | Value                | Meaning                                                           |
|---------------------|----------------------|-------------------------------------------------------------------|
| `PROJECT_NAME`      | `SocialBulletin`     | Human-readable project name.                                      |
| `PROJECT_SLUG`      | `socialbulletin`     | Lowercase deployment, path, and resource-name prefix.             |
| `PROJECT_NAMESPACE` | `SocialBulletin`     | Root PHP namespace used by first-party PHP code.                  |
| `DATABASE_SCHEMA`   | `bulletin`           | Default PostgreSQL schema for application-owned database objects. |
| `API_URL`           | `api.bulletin.local` | Local development API hostname.                                   |
| `FRONTEND_URL`      | `app.bulletin.local` | Local development frontend hostname.                              |

Use constants for project-specific values in ADRs and specs. Use literal values where copy-paste commands, code, config, or user setup instructions need exact text.

## Consequences

- Keeps project-specific values discoverable in one place.
- Makes future renames and environment changes easier to assess.
- Reduces drift between ADRs, specifications, and onboarding documents.
- Readers must resolve constants through this ADR when they need exact values.
- Setup instructions may still need literal values where copy-paste accuracy matters.
