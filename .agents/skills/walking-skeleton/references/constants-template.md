# ADR-0000: Project Documentation Constants

> **Template** — Copy to `specs/decisions/ADR-0000-project-documentation-constants.md` and replace every `<placeholder>` with project-specific values.

- Status: Accepted
- Date: <YYYY-MM-DD>

## Context

Project records repeat project names, hostnames, namespaces, schemas, and deployment prefixes. A small shared vocabulary
keeps those values discoverable and reduces rename drift.

## Decision

Adopt these documentation constants.

| Constant            | Value              | Meaning                                                           |
|---------------------|--------------------|-------------------------------------------------------------------|
| `PROJECT_NAME`      | `<Project Name>`   | Human-readable project name.                                      |
| `PROJECT_SLUG`      | `<project-slug>`   | Lowercase deployment, path, and resource-name prefix.             |
| `PROJECT_NAMESPACE` | `<ProjectName>`    | Root PHP namespace used by first-party PHP code.                  |
| `DATABASE_SCHEMA`   | `<schema>`         | Default PostgreSQL schema for application-owned database objects. |
| `DEV_API_URL`       | `<dev-api-host>`   | Local development API hostname.                                   |
| `DEV_FRONT_URL`     | `<dev-front-host>` | Local development frontend hostname.                              |
| `DEV_TLS_HOSTNAME`  | `<dev-tls-host>`   | TLS certificate hostname.                                         |
| `LIVE_API_URL`      | `<live-api-host>`  | Live API URL.                                                     |
| `LIVE_FRONT_URL`    | `<live-front-host>`| Live frontend URL.                                                |

Use constants for project-specific values in ADRs and specs. Use literal values where copy-paste commands, code, config,
or user setup instructions need exact text.

## Consequences

- Keeps project-specific values discoverable in one place.
- Makes future renames and environment changes easier to assess.
- Reduces drift between ADRs, specifications, and onboarding documents.
- Readers must resolve constants through this ADR when they need exact values.
- Setup instructions may still need literal values where copy-paste accuracy matters.
