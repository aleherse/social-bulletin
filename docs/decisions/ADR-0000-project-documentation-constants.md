# ADR-0000: Project Documentation Constants

- Status: Accepted
- Date: 2026-07-10

## Context

Project records repeat project names, hostnames, namespaces, schemas, and deployment prefixes.
A small shared vocabulary keeps those values discoverable and reduces rename drift.

## Decision

Adopt these documentation constants.

| Constant            | Value                       | Meaning                                                           |
|---------------------|-----------------------------|-------------------------------------------------------------------|
| `PROJECT_NAME`      | Social Bulletin             | Human-readable project name.                                      |
| `PROJECT_SLUG`      | social-bulletin             | Lowercase deployment, path, and resource-name prefix.             |
| `PROJECT_NAMESPACE` | SocialBulletin              | Root PHP namespace used by first-party PHP code.                  |
| `DATABASE_SCHEMA`   | bulletin                    | Default PostgreSQL schema for application-owned database objects. |
| `DEV_API_URL`       | dev.api.social.aleherse.com | Local development API hostname.                                   |
| `DEV_FRONT_URL`     | dev.app.social.aleherse.com | Local development frontend hostname.                              |
| `DEV_TLS_HOSTNAME`  | social.aleherse.com         | TLS certificate hostname.                                         |
| `LIVE_API_URL`      | api.social.aleherse.com     | Live API URL.                                                     |
| `LIVE_FRONT_URL`    | app.social.aleherse.com     | Live frontend URL.                                                |

Use constants for project-specific values in ADRs and specs.
Use literal values where copy-paste commands, code, config, or user setup instructions need exact text.

## Consequences

- Keeps project-specific values discoverable in one place.
- Makes future renames and environment changes easier to assess.
- Reduces drift between ADRs, specifications, and onboarding documents.
- Readers must resolve constants through this ADR when they need exact values.
- Setup instructions may still need literal values where copy-paste accuracy matters.
