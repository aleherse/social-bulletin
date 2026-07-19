# API Contract: Movements

All endpoints live under the existing stateless `/api` firewall and
require the `token` httpOnly cookie (ADR-0011) unless noted.
Errors follow the existing API conventions: JSON body with a
translatable `message`; validation errors list per-field problems.

Movement JSON representation (all responses):

```json
{
  "id": "0198c2f4-…",
  "title": "Community Gardens for Everyone",
  "description": "## Why\n…raw markdown…",
  "category": "cooperative",
  "area": "municipality",
  "location": "Sheffield",
  "status": "draft",
  "createdAt": "2026-07-19T10:00:00+00:00",
  "updatedAt": "2026-07-19T10:00:00+00:00"
}
```

`location` is `null` when `area` is `"international"`.

## GET /api/categories

Returns the managed category list for the proposal form (FR-002).

- **Auth**: required (form is only for signed-in users).
- **200**: `{"categories": [{"id": "animal_rights"}, …]}` in sort
  order.

## POST /api/movements

Creates a movement draft (US1, FR-001, FR-005).

- **Request**: `title`, `category`, `area`, `location` (omit or null
  for `international`), optional `description` (defaults to `""`).
- **201**: the movement JSON, `status = "draft"`.
- **400**: missing/invalid fields (title empty or too long, unknown
  `category`, invalid `area`, location/area mismatch); body names each
  offending field (US1 scenario 4).
- **401**: no valid session cookie (US1 scenario 5).

## GET /api/movements

Lists the authenticated user's own movements, newest first (FR-007).

- **200**: `{"movements": [ …movement JSON… ]}`.
- **401**: no valid session cookie.

## GET /api/movements/{id}

Fetches one movement.

- **200**: movement JSON — only if the requester is the author
  (FR-007).
- **404**: unknown id, or the movement belongs to another user
  (existence is not revealed).
- **401**: no valid session cookie.

## PATCH /api/movements/{id}

Edits a draft (US3).

- **Request**: any subset of `title`, `description`, `category`,
  `area`, `location`.
- **200**: updated movement JSON; `updatedAt` refreshed (FR-009).
- **400**: same field validation as creation.
- **404**: unknown id or not the author.
- **409**: movement is not in `draft` status (edits are draft-only).
- **401**: no valid session cookie.

## POST /api/movements/{id}/submit

Submits a draft as a proposal (US2, FR-006).

- **200**: movement JSON with `status = "proposed"`.
- **400**: description is empty — body says a description is required
  to propose the movement (US2 scenario 2).
- **404**: unknown id or not the author (US2 scenario 4).
- **409**: movement is not in `draft` status (US2 scenario 3).
- **401**: no valid session cookie.

## Behat coverage map

`apps/api/features/movements.feature` must cover, via JMESPath
assertions (ADR-0015): every acceptance scenario in the spec, the 400
field-validation cases, the 401 guest cases, the cross-user 404s, and
the 409 double-submit case.
