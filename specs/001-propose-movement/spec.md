# Feature Specification: Propose a Movement

**Feature Branch**: `001-propose-movement`

**Created**: 2026-07-19

**Status**: Draft

**Input**: User description: "A user can propose a `Movement`, it has:
a title; a description (in markdown);
a category (`animal_rights`, `anti-racism`, `black_power`, `cooperative`...);
an area (`international`, `national`, `state`, `province`, `region`,
`municipality`, `neighborhood`); a location to specify the area;
a status (`draft`, `proposed`, `published`)"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create a movement draft (Priority: P1)

A signed-in user starts a new movement proposal.
They provide a title, a category, an area level, and a location that
pins the area to a real place, and optionally a description written
in markdown — the description can be left empty while drafting.
The system saves the movement with status `draft`.

**Why this priority**: Capturing a movement with all its attributes is the
foundation of the feature; nothing else can happen without a saved draft.

**Independent Test**: Can be fully tested by creating a draft with valid
values and confirming it is saved with `draft` status,
visible only to its author.

**Acceptance Scenarios**:

1. **Given** a signed-in user, **When** they submit a movement with title
   "Community Gardens for Everyone", a markdown description, category
   `cooperative`, area `municipality`, and location "Sheffield",
   **Then** the movement is saved with status `draft`.
2. **Given** a signed-in user, **When** they save a movement draft with
   an empty description, **Then** the draft is saved with status `draft`.
3. **Given** a movement titled "Community Gardens for Everyone" already
   exists, **When** another user creates a movement with the same title,
   **Then** the new movement is saved and both movements coexist.
4. **Given** a signed-in user, **When** they submit a movement with a
   missing title, category, area, or location,
   **Then** the movement is not saved and the user is told which fields
   are missing.
5. **Given** a guest (not signed in), **When** they attempt to create
   a movement, **Then** they are asked to sign in first.

---

### User Story 2 - Submit a draft as a proposal (Priority: P2)

The author of a draft decides it is ready and submits it,
moving the movement from `draft` to `proposed`
so it can be considered for publication.

**Why this priority**: The feature is about *proposing* movements;
submission is the action that turns a private draft into a proposal.

**Independent Test**: Can be tested by submitting an existing draft and
confirming its status changes to `proposed` and it can no longer be
submitted again.

**Acceptance Scenarios**:

1. **Given** a movement in `draft` owned by the user with a non-empty
   description, **When** the user submits it,
   **Then** its status becomes `proposed`.
2. **Given** a movement in `draft` with an empty description,
   **When** its author submits it, **Then** the submission is rejected,
   the status remains `draft`, and the user is told a description is
   required to propose the movement.
3. **Given** a movement already in `proposed` status,
   **When** its author attempts to submit it again,
   **Then** the action is rejected and the current status is unchanged.
4. **Given** a draft owned by another user, **When** a different user
   attempts to submit it, **Then** the action is rejected.

---

### User Story 3 - Edit a draft before submission (Priority: P3)

The author revisits a movement they saved as a draft and changes its
title, description, category, area, or location before submitting it.

**Why this priority**: Proposals are rarely right on the first attempt;
editing makes drafting useful but the feature works without it.

**Independent Test**: Can be tested by editing a draft's fields and
confirming the changes persist while the movement is still a draft.

**Acceptance Scenarios**:

1. **Given** a movement in `draft` owned by the user, **When** the user
   changes its description and category, **Then** the changes are saved
   and the status remains `draft`.
2. **Given** a movement in `draft`, **When** the author changes its title,
   **Then** the new title is saved and the status remains `draft`.

---

### Edge Cases

- Markdown descriptions containing raw HTML or script content must be
  rendered safely, never executed.
- Area/location mismatches: a location must make sense for the chosen
  area level (for example a country for `national`, a neighbourhood
  name for `neighborhood`); `international` needs no specific location.
- Very long titles or descriptions are limited (200 and 20,000
  characters, FR-010) and the limit is communicated to the user.
- Two users creating movements with the same title at the same moment
  must both succeed.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST allow signed-in users to create a movement
  with a title, a category, an area level, and a location;
  the markdown description is optional while the movement is a `draft`;
  guests MUST NOT be able to create movements.
- **FR-002**: The category MUST be chosen from a managed list of
  categories (including at least `animal_rights`, `anti-racism`,
  `black_power`, `cooperative`); free-text categories MUST NOT be
  accepted.
- **FR-003**: The area MUST be one of `international`, `national`,
  `state`, `province`, `region`, `municipality`, `neighborhood`.
- **FR-004**: Every movement except `international` ones MUST have a
  location naming the place the area refers to;
  `international` movements carry no location.
- **FR-005**: A movement MUST always be in exactly one status:
  `draft`, `proposed`, or `published`; new movements start as `draft`.
- **FR-006**: The only status transition this feature provides is
  `draft` → `proposed`, performed by the author;
  any other transition MUST be rejected.
  Submitting a draft as `proposed` MUST be rejected while its
  description is empty.
- **FR-007**: Only the author MUST be able to view a movement while it
  is in `draft` or `proposed`; editing MUST be possible only while the
  movement is in `draft`, and only by its author.
- **FR-008**: The system MUST validate presence of the fields required
  at each stage (title, category, area, and location from the first
  save; description from submission onwards).
  Markdown descriptions are stored as the author wrote them; whenever
  the system renders one, the rendered output MUST NOT execute
  scripts or embed raw HTML.
- **FR-009**: The system MUST record who proposed each movement and when
  it was created and last updated.
- **FR-010**: Titles MUST be limited to 200 characters and
  descriptions to 20,000 characters; input beyond a limit MUST be
  rejected with a message stating the limit.

### Key Entities

- **Movement**: A social initiative proposed by a user.
  Attributes: title, markdown description (may be empty while a
  `draft`), category, area level, location, status, author,
  creation and update times.
- **Category**: A managed label classifying movements
  (`animal_rights`, `anti-racism`, `black_power`, `cooperative`, ...);
  the list can grow without changing the feature.
- **Location**: The named place a movement's area level refers to
  (a country, state, province, region, municipality, or neighbourhood).
- **User (author)**: The signed-in person who proposes the movement and
  owns it while it is a draft.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A signed-in user can create and save a movement draft,
  from opening the form to confirmation, in under 3 minutes.
- **SC-002**: 95% of users who start a movement proposal complete the
  draft without a validation error on required fields being unclear
  (measured by drop-off on the creation flow).
- **SC-003**: 100% of `proposed` movements originate from a `draft`
  submitted by its author and carry a non-empty description.

## Assumptions

- Any signed-in user may propose a movement; no special role is needed
  to create drafts.
- Only the description is deferred: title, category, area, and location
  are required from the first save of a draft.
- Moderation and publication (`proposed` → `published`, who performs
  it, and public visibility of published movements) are out of scope;
  a later specification will cover them.
  The `published` status value is kept so the lifecycle already
  anticipates it, but no movement reaches it through this feature.
- The category list is managed by the platform (seeded with the examples
  given) and extending it is out of scope for this feature.
- Location is captured as a named place appropriate to the area level;
  map integration, geocoding, or structured location hierarchies are
  out of scope for this feature.
- Deleting or archiving movements is out of scope for this feature.
- Slugs (readable identifiers derived from the title) are deliberately
  out of scope; a later specification may introduce them.
