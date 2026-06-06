# ADR-0000: Project Documentation Constants

- Status: Accepted
- Date: 2026-06-06

## Context

Project documentation and decision records repeatedly mention the same project-specific names, local hostnames,
namespaces, schemas, and deployment naming fragments. Repeating literal values makes the documents harder to rename,
compare, and reuse because future changes require editing many unrelated records.

The project needs a stable vocabulary of symbolic constants that documents can reference when describing
project-specific values.

## Decision

Adopt the following documentation constants for project records, specifications, and onboarding documents.

| Constant            | Value                | Meaning                                                           |
|---------------------|----------------------|-------------------------------------------------------------------|
| `PROJECT_NAME`      | `SocialBulletin`     | Human-readable project name.                                      |
| `PROJECT_SLUG`      | `socialbulletin`     | Lowercase deployment, path, and resource-name prefix.             |
| `PROJECT_NAMESPACE` | `SocialBulletin`     | Root PHP namespace used by first-party PHP code.                  |
| `DATABASE_SCHEMA`   | `bulletin`           | Default PostgreSQL schema for application-owned database objects. |
| `API_URL`           | `api.bulletin.local` | Local development API hostname.                                   |
| `FRONTEND_URL`      | `app.bulletin.local` | Local development frontend hostname.                              |

Future project documents SHOULD reference these constants instead of repeating literal values when the value is
project-specific rather than part of an external API or generated code.

When a literal value is required for copy-paste commands, configuration snippets, code namespaces, or user-facing setup
instructions, the document MAY include the literal value alongside the constant.

## Consequences

Positive outcomes:

- Keeps project-specific values discoverable in one place.
- Makes future renames and environment changes easier to assess.
- Reduces drift between ADRs, specifications, and onboarding documents.

Tradeoffs:

- Readers must resolve constants through this ADR when they need exact values.
- Setup instructions may still need literal values where copy-paste accuracy matters.
- Existing records may keep historical literals when changing them would reduce clarity or alter recorded implementation
  detail.

Follow-ups:

- Prefer these constants in new ADRs and specifications.
- Update older documentation opportunistically when it is touched for related work.
