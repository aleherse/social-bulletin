# Feature Specification: Pin a Movement's Location on a Map

**Feature Branch**: `002-map-pinned-location`

**Created**: 2026-10-13

**Status**: Draft

**Input**: User description: "A movement author can pin a movement's location on a map instead of only typing it.
Clicking the map or using "use my location" places a marker.
The location field is then filled with the place name that matches the chosen area level
(country for national, state/province/region, city for municipality, neighbourhood),
and the point's coordinates are saved with the movement.
The author can still type or correct the name by hand;
coordinates are optional, so a typed-only location remains valid.
When the author views or edits a movement that has coordinates, the map shows that point.
International movements carry neither a map nor coordinates.
Coordinates outside valid latitude/longitude ranges are rejected.
If geolocation is denied or reverse geocoding fails,
the author is told and can still type the location."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Pin the location by clicking the map (Priority: P1)

While creating or editing a draft movement with a non-`international` area,
the author sees a map next to the location field.
They click a point on the map; a marker appears there,
and the location field is filled with the name of the place at that point
that matches the chosen area level.
When the author saves, the movement keeps both the location name
and the point's coordinates.
The author can still overwrite the name by hand before saving,
and an author who never touches the map can save a typed-only location
exactly as before.

**Why this priority**: Pinning a point and getting the right place name back
is the core of the feature;
every other story builds on a movement that carries coordinates.

**Independent Test**: Can be fully tested by creating a `municipality` draft,
clicking a point inside a known city,
confirming the location field shows that city's name,
saving, and confirming the saved movement carries that name and those coordinates.

**Acceptance Scenarios**:

1. **Given** a signed-in author on the new-movement form with area `municipality`,
   **When** they click the map at latitude 53.3811, longitude -1.4701 (central Sheffield),
   **Then** a marker appears at that point
   and the location field is filled with "Sheffield".
2. **Given** the author has pinned that point and the field reads "Sheffield",
   **When** they save the draft,
   **Then** the movement is saved with location "Sheffield"
   and coordinates 53.3811, -1.4701.
3. **Given** a pinned point with area `national`,
   **When** the place name is filled in,
   **Then** the field holds the name of the country containing the point
   (for the Sheffield point, "United Kingdom").
4. **Given** the location field was filled from a pinned point,
   **When** the author replaces the text with "City of Sheffield" and saves,
   **Then** the movement is saved with location "City of Sheffield"
   and the pinned coordinates are kept.
5. **Given** a signed-in author who never interacts with the map,
   **When** they type location "Sheffield" and save,
   **Then** the movement is saved with location "Sheffield" and no coordinates.
6. **Given** a marker already placed,
   **When** the author clicks a different point on the map,
   **Then** the marker moves to the new point,
   the location field is refilled for the new point,
   and only the new point's coordinates are saved.
7. **Given** a marker already placed,
   **When** the author removes the pin,
   **Then** the marker disappears,
   the location field keeps its current text,
   and the movement is saved without coordinates.

---

### User Story 2 - Pin the author's current position (Priority: P2)

Instead of finding the place on the map,
the author chooses "use my location".
After they grant permission, a marker is placed at their current position
and the location field is filled exactly as if they had clicked that point.
If they refuse permission, or the place name cannot be found,
they are told so and can carry on typing the location by hand.

**Why this priority**: Most movements are local to their author,
so using the current position is the fastest way to pin one,
but clicking the map already delivers the feature without it.

**Independent Test**: Can be tested by granting a simulated position,
choosing "use my location",
and confirming the marker and location name match that position;
then denying permission and confirming the author is told and can still type.

**Acceptance Scenarios**:

1. **Given** an author on the form with area `municipality`
   whose device reports latitude 53.3811, longitude -1.4701,
   **When** they choose "use my location" and grant permission,
   **Then** a marker appears at that point
   and the location field is filled with "Sheffield".
2. **Given** an author who denies location permission,
   **When** they choose "use my location",
   **Then** they are told their location could not be used,
   no marker is placed,
   the location field is unchanged and still editable,
   and the draft can be saved with a typed location.
3. **Given** an author who has placed a marker
   but no place name can be found for that point,
   **When** the lookup fails,
   **Then** they are told the place name could not be found,
   the marker stays where it was placed,
   the location field is unchanged and still editable,
   and saving keeps the pinned coordinates with whatever name the author types.

---

### User Story 3 - See the pinned point when viewing or editing (Priority: P3)

When the author opens a movement that has coordinates,
the map shows a marker at the saved point —
read-only on the movement's detail view,
movable on the edit form while the movement is a draft.
A movement without coordinates shows no map on its detail view.

**Why this priority**: Showing the saved point confirms the pin was kept
and lets the author correct it,
but pinning and saving already deliver value without it.

**Independent Test**: Can be tested by opening the detail and edit views
of a movement saved with coordinates and confirming the marker sits at the saved point,
then opening one without coordinates and confirming no map appears on its detail view.

**Acceptance Scenarios**:

1. **Given** a movement saved with location "Sheffield" and coordinates 53.3811, -1.4701,
   **When** its author views it,
   **Then** a map shows a marker at that point
   alongside the location name.
2. **Given** that movement is still a `draft`,
   **When** its author opens it for editing,
   **Then** the map shows the marker at the saved point
   and the author can move or remove it.
3. **Given** a movement saved with a typed-only location,
   **When** its author views it,
   **Then** no map is shown and the location name is displayed as before.

---

### Edge Cases

- **International area**: no map is offered and no coordinates are accepted.
  Switching the area to `international` while a point is pinned
  removes the marker and the coordinates, and hides the map,
  consistent with `international` movements carrying no location.
- **Area level changed after pinning**: switching between non-`international` levels
  keeps the pinned point and refills the location field
  with the name matching the new level,
  replacing what the field held.
- **Point with no place at the chosen level**
  (for example a point at sea, or no neighbourhood known at that point):
  treated as a failed lookup — the author is told,
  the marker and coordinates stay, and the field is left for them to type.
- **Out-of-range coordinates**: a latitude outside -90 to 90
  or a longitude outside -180 to 180 is rejected
  and the movement is not saved.
- **Partial coordinates**: a latitude without a longitude, or the reverse,
  is rejected; coordinates are saved as a complete pair or not at all.
- **Map unavailable**: if the map cannot be displayed,
  the location field still works as a plain text field
  and the movement can be saved with a typed location.
- **Slow lookup**: the author can keep typing or save while a place-name lookup is pending;
  a lookup that completes after the author has typed in the field
  does not overwrite what they typed.
- **Typing does not move the marker**: editing the name by hand
  never changes or removes the pinned point.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: While creating or editing a draft movement
  whose area is not `international`,
  the system MUST offer a map on which the author can place a single marker
  by clicking a point.
- **FR-002**: The system MUST offer a "use my location" action
  that, once the author grants permission,
  places the marker at the author's current position.
  The author's position MUST NOT be requested
  until they choose this action.
- **FR-003**: When a marker is placed or moved,
  the system MUST fill the location field
  with the name of the place containing the point
  at the chosen area level:
  the country for `national`;
  the state, province, or region for `state`, `province`, and `region`;
  the city or town for `municipality`;
  the neighbourhood for `neighborhood`.
- **FR-004**: The author MUST be able to type or correct the location name by hand
  at any time; editing the name MUST NOT remove or move the pinned point.
- **FR-005**: When a movement with a pinned point is saved,
  the system MUST store the point's latitude and longitude with the movement.
- **FR-006**: Coordinates MUST be optional.
  A movement with a typed location and no coordinates
  MUST remain valid under all the rules of the Propose a Movement feature.
- **FR-007**: The author MUST be able to remove a pinned point;
  the movement is then saved without coordinates
  and the location name is left unchanged.
- **FR-008**: `international` movements MUST NOT show a map
  and MUST NOT carry coordinates;
  coordinates submitted for an `international` movement MUST be rejected.
- **FR-009**: The system MUST reject coordinates whose latitude is outside -90 to 90
  or whose longitude is outside -180 to 180,
  and MUST reject a latitude without a longitude or the reverse;
  the author MUST be told the location point is invalid.
- **FR-010**: If the author denies location permission,
  or their position cannot be obtained,
  the system MUST tell the author
  and leave the location field unchanged and editable.
- **FR-011**: If no place name can be found for a pinned point,
  the system MUST tell the author,
  keep the marker and its coordinates,
  and leave the location field unchanged and editable.
- **FR-012**: When the author views a movement that has coordinates,
  the system MUST show a map with a marker at the saved point;
  when the author edits a draft that has coordinates,
  the edit form's map MUST start with the marker at the saved point.
  A movement without coordinates MUST NOT show a map on its detail view.
- **FR-013**: Coordinates MUST follow the same visibility and editing rules
  as the rest of the movement:
  visible only where the movement is visible,
  changeable only by its author while it is a `draft`.

### Key Entities

- **Movement**: Extended with an optional point.
  A movement either carries a complete point or none;
  `international` movements never carry one.
  The location name remains required for every non-`international` movement,
  whether or not a point is pinned.
- **Point (coordinates)**: A latitude between -90 and 90
  and a longitude between -180 and 180,
  marking where on the map the author pinned the movement.
- **Place name suggestion**: The name looked up for a point
  at the movement's area level;
  it fills the location field but the author may change it,
  and only the name the author saves is kept.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An author can pin a location and see the place name filled in
  within 3 seconds of clicking the map, for 95% of pins.
- **SC-002**: An author can create a draft with a pinned location,
  from opening the form to confirmation,
  in under 3 minutes — no slower than a typed-only draft today.
- **SC-003**: 100% of saved coordinates lie within valid latitude and longitude ranges,
  and no `international` movement carries coordinates.
- **SC-004**: 100% of movements that could be saved with a typed-only location
  before this feature can still be saved the same way.
- **SC-005**: In every case where location permission is denied
  or no place name is found,
  the author sees an explanation and can still save the draft with a typed location.

## Assumptions

- The map, the place-name lookup, and device positioning
  rely on external services;
  their availability is not guaranteed,
  which is why the typed location remains a complete fallback.
- `state`, `province`, and `region` take the name
  of the administrative division matching that level where the lookup can tell them apart;
  where a country's divisions do not map cleanly onto the chosen level,
  the nearest matching division is used and the author corrects it by hand if needed.
- Place names are filled in the author's interface language where available,
  otherwise in the place's local name.
- Each movement has at most one point;
  drawing areas or boundaries is out of scope.
- Searching for a place by typing its name to move the marker
  is out of scope;
  typing only sets the name.
- The pinned point is stored at the precision the author chose.
  Because only the author can view a movement in `draft` or `proposed`,
  no one else sees the point;
  whether to reduce precision on publicly visible movements
  is left to the specification that introduces publication.
- Existing movements receive no coordinates;
  there is no backfill from their typed locations.
- Pinning is offered only where editing is allowed today —
  on drafts, by their author.
  The detail view shows the point read-only for any status the author can see.
