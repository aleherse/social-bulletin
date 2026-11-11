# Feature Specification: Pin a Movement's Location on a Map

**Feature Branch**: `002-map-pinned-location`

**Created**: 2026-10-13

**Status**: Implemented

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

**Note**: The Clarifications below supersede parts of this input:
coordinates are never saved, shown, or validated (Q1),
so the map is only an aid for filling in the location name;
the place-name lookup is hosted by the platform,
while the map's tiles come from OpenStreetMap's public tile servers (session 2026-10-16).

## Clarifications

### Session 2026-10-13

- Q: How precise should the saved point be,
  given the finest named level is a neighbourhood?
  → A: Do not store coordinates at all;
  the map only helps fill in the location name,
  and the pinned point is discarded once the form is closed.
- Q: Which place name fills the field for `state`, `province`, and `region`?
  → A: The area levels are ranks in one hierarchy, largest to smallest:
  `national` (España) › `state` (Andalucía) › `province` (Málaga)
  › `region` (a comarca, e.g. La Axarquía) › `municipality` (Málaga)
  › `neighborhood` (Huelin).
  Each level is filled from its rank, not from how a country labels its divisions.
- Q: In which language are filled-in place names written?
  → A: Always the local official name (España, Andalucía, Málaga),
  whatever the author's interface language.
- Q: What happens to the location field when the area level changes
  while a point is pinned?
  → A: Nothing; the field is never refilled on a level change.
  The author pins again or types the name.

### Session 2026-10-16

- Q: Who hosts the map and the place-name lookup?
  → A: The place name is looked up by the platform's own API,
  with no separate service and no third-party geocoder.
  The map's tiles come from OpenStreetMap's public tile servers (`tile.openstreetmap.org`),
  not from a self-hosted archive
  (revised later this session; the first answer self-hosted the tiles too).
- Q: Which places can be looked up?
  → A: Spain and the United Kingdom are required to start with.
  Other places are allowed but not required:
  a point elsewhere is named wherever the loaded place data happens to cover it,
  and otherwise is treated as a point with no place at the chosen level.
- Q: Since the point now reaches the platform, may it be logged?
  → A: No. The point may be used to answer the lookup
  but MUST NOT be stored or written to any log.
- Q: Which parts of Spain must be named at every level?
  → A: Every point in Spain —
  the mainland, the Balearic and Canary Islands, Ceuta and Melilla —
  MUST be named at `national` ("España") and `state`.
- Q: The first real import lost the country boundaries of Spain and Ceuta,
  because parts of both lie outside Geofabrik's Spain extract.
  How does the import get them?
  → A: The import fetches both boundaries complete from the Overpass API at build time,
  and the build fails if either is still missing.
- Q: Does "every point in Spain" include territorial waters
  and the *plazas de soberanía*, which belong to no autonomous community?
  → A: Land only, at both levels:
  the land of the autonomous communities, Ceuta, and Melilla.
  Points offshore and on the *plazas de soberanía* are not guaranteed at either level.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Pin the location by clicking the map (Priority: P1)

While creating or editing a draft movement with a non-`international` area,
the author sees a map next to the location field.
They click a point on the map; a marker appears there,
and the location field is filled with the name of the place at that point
that matches the chosen area level.
When the author saves, the movement keeps the location name only;
the pinned point is not stored.
The author can still overwrite the name by hand before saving,
and an author who never touches the map can save a typed-only location
exactly as before.

**Why this priority**: Pinning a point and getting the right place name back
is the core of the feature.

**Independent Test**: Can be fully tested by creating a `municipality` draft,
clicking a point inside a known city,
confirming the location field shows that city's name,
saving, and confirming the saved movement carries that name and no point.

**Acceptance Scenarios**:

1. **Given** a signed-in author on the new-movement form with area `municipality`,
   **When** they click the map at latitude 53.3811, longitude -1.4701 (central Sheffield),
   **Then** a marker appears at that point
   and the location field is filled with "Sheffield".
2. **Given** the author has pinned that point and the field reads "Sheffield",
   **When** they save the draft,
   **Then** the movement is saved with location "Sheffield"
   and no coordinates are stored.
3. **Given** a pinned point with area `national`,
   **When** the place name is filled in,
   **Then** the field holds the name of the country containing the point
   (for the Sheffield point, "United Kingdom").
4. **Given** an author on the form with an area level chosen,
   **When** they pin a point in the Huelin neighbourhood of Málaga
   (latitude 36.7033, longitude -4.4366),
   **Then** the field holds "España" for `national`,
   "Andalucía" for `state`,
   "Málaga" for `province`,
   "Málaga-Costa del Sol" (the comarca containing the point) for `region`,
   "Málaga" for `municipality`,
   and "Huelin" for `neighborhood`.
5. **Given** the location field was filled from a pinned point,
   **When** the author replaces the text with "City of Sheffield" and saves,
   **Then** the movement is saved with location "City of Sheffield".
6. **Given** a signed-in author who never interacts with the map,
   **When** they type location "Sheffield" and save,
   **Then** the movement is saved with location "Sheffield".
7. **Given** a marker already placed,
   **When** the author clicks a different point on the map,
   **Then** the marker moves to the new point
   and the location field is refilled for the new point.
8. **Given** a point pinned with area `municipality`
   and the field filled with "Málaga",
   **When** the author changes the area to `province`,
   **Then** the marker stays where it was
   and the field still reads "Málaga" until the author pins again or types.
9. **Given** a saved `draft` with location "Sheffield",
   **When** its author opens it for editing,
   **Then** the location field shows "Sheffield",
   the map shows no marker,
   and the author can pin a new point to refill the name.
10. **Given** an author on the form with area `national` or `state`,
    **When** they pin a point in Palma (39.5696, 2.6502),
    Las Palmas de Gran Canaria (28.1235, -15.4363),
    Ceuta (35.8894, -5.3213),
    or Melilla (35.2923, -2.9381),
    **Then** the field holds "España" for `national`,
    and "Illes Balears", "Canarias", "Ceuta", or "Melilla" respectively for `state`.

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
   and the draft can be saved with a typed location.
4. **Given** an author who has placed a marker,
   **When** the place-name service fails or gives no answer within 10 seconds,
   **Then** they are told the place could not be looked up,
   the marker stays where it was placed,
   the location field is unchanged and still editable,
   and the draft can be saved with a typed location.

---

### Edge Cases

- **International area**: no map is offered.
  Switching the area to `international` while a point is pinned
  removes the marker and hides the map,
  consistent with `international` movements carrying no location.
- **Area level changed after pinning**: switching between non-`international` levels
  keeps the marker and leaves the location field untouched;
  the author pins again to get the name for the new level, or types it.
- **Point with no place at the chosen level**
  (for example a point at sea, a point outside the loaded place data,
  or no neighbourhood containing or near that point):
  treated as a failed lookup — the author is told,
  the marker stays, and the field is left for them to type.
- **Spanish waters and the *plazas de soberanía***: a point offshore,
  or on a *plaza* that belongs to no autonomous community,
  may be named "España" at `national` but is not guaranteed at any level;
  where nothing is found, it is treated as a point with no place at the chosen level.
- **Map scrolled across the date line**: clicking a point on a map
  that has been scrolled past longitude ±180
  looks up the same real-world place as the equivalent point within range.
- **Map unavailable**: if the map cannot be displayed at all,
  it is left out of the form,
  the location field still works as a plain text field,
  "use my location" still works,
  and the movement can be saved with a typed location.
- **Basemap unavailable**: if the map's background imagery cannot be loaded,
  the map is shown blank
  and clicking it still places the marker and fills the location field.
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
- **FR-003**: When a marker is placed or moved —
  and only then, never on a change of area level —
  the system MUST fill the location field
  with the name of the place containing the point
  at the chosen area level.
  The levels are ranks in one hierarchy, largest to smallest,
  and each is filled from its rank:
  the country for `national`;
  the first-level division (e.g. a Spanish autonomous community) for `state`;
  the second-level division (e.g. a Spanish province) for `province`;
  the grouping of municipalities within a province (e.g. a Spanish comarca) for `region`;
  the city, town, or municipality for `municipality`;
  the neighbourhood for `neighborhood`.
  For `neighborhood` only,
  when no neighbourhood area contains the point,
  the nearest named neighbourhood within 1000 metres of the point is used instead,
  because many cities mark neighbourhoods as points rather than areas.
  The name MUST be the place's local official name,
  whatever the author's interface language.
- **FR-004**: The author MUST be able to type or correct the location name by hand
  at any time; editing the name MUST NOT move or remove the marker.
- **FR-005**: The system MUST NOT store or log the pinned point or the author's position.
  The point exists only while the form is open,
  is sent only to the platform's own place-name lookup,
  is used only to look up the place name,
  and is discarded when the form is saved or left.
  A request carrying the point MUST NOT place it in a URL,
  where request logs would record it.
- **FR-006**: The movement's saved location MUST remain the name alone,
  under all the rules of the Propose a Movement feature;
  a typed-only location MUST remain valid.
- **FR-007**: `international` movements MUST NOT show a map.
- **FR-008**: If the author denies location permission,
  or their position cannot be obtained,
  the system MUST tell the author
  and leave the location field unchanged and editable.
- **FR-009**: If no place name can be found for a pinned point,
  or the place-name lookup fails or gives no answer within 10 seconds,
  the system MUST tell the author,
  keep the marker,
  and leave the location field unchanged and editable.
  A point with no place at the chosen level
  and a lookup that fails
  are reported with different messages.
- **FR-010**: When the author edits a saved draft,
  the map MUST start without a marker
  and the location field MUST show the saved name.
  The movement's detail view is unchanged by this feature and shows no map.
- **FR-011**: The place-name lookup MUST be served by the platform itself,
  with no request to a third-party geocoding service.
  The map's tiles MUST come from OpenStreetMap's public tile servers (`tile.openstreetmap.org`),
  which receive the map view the author is looking at but never the pinned point,
  and the map MUST credit OpenStreetMap as their usage policy requires.
  Place names MUST be available for points in Spain and the United Kingdom.
  Other countries are not required:
  a point elsewhere is named wherever the loaded place data happens to cover it,
  and otherwise is reported as having no place at the chosen level (FR-009).
- **FR-012**: Every point on Spanish land —
  the mainland, the Balearic and Canary Islands, Ceuta, and Melilla —
  MUST be named at `national` ("España") and at `state`.
  Points offshore and on the *plazas de soberanía*
  (Peñón de Vélez de la Gomera, Alhucemas, the Chafarinas)
  are not guaranteed at either level.
  Where the regional OpenStreetMap extract the place data is built from
  clips the country boundary of Spain or the boundary of Ceuta,
  the build MUST fetch that boundary complete from the Overpass API,
  and the build MUST fail if either boundary is still missing afterwards.

### Key Entities

- **Movement**: Unchanged.
  The location name remains required for every non-`international` movement
  and is the only location data stored.
- **Pinned point**: A latitude and longitude the author marks on the map
  while the form is open.
  It is never stored; it exists only to look up a place name.
- **Place name suggestion**: The name looked up for a pinned point
  at the movement's area level;
  it fills the location field but the author may change it,
  and only the name the author saves is kept.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An author can pin a location and see the place name filled in
  within 3 seconds of clicking the map, for 95% of pins.
  It is checked by hand against the deployed `preview` environment before release,
  not by an automated test,
  since only the deployed database and API show real response times.
- **SC-002**: An author can create a draft with a pinned location,
  from opening the form to confirmation,
  in under 3 minutes — no slower than a typed-only draft today.
- **SC-003**: No saved movement, database table, or log contains a pinned point
  or the author's position.
- **SC-004**: 100% of movements that could be saved with a typed-only location
  before this feature can still be saved the same way.
- **SC-005**: In every case where location permission is denied
  or no place name is found,
  the author sees an explanation and can still save the draft with a typed location.

## Assumptions

- Device positioning relies on the author's browser and device,
  and the map and place-name lookup can still fail;
  the typed location remains a complete fallback.
- The pinned point leaves the author's device only to be sent
  to the platform's own place-name lookup,
  which answers from it and records it nowhere.
- The map shows the whole world, drawn by OpenStreetMap's public tile servers;
  place names are guaranteed only for Spain and the United Kingdom.
  Wider place-name coverage is a later change of data, not of behaviour.
- Place names come from OpenStreetMap boundary data
  loaded into the platform;
  they are as current as the last import.
  The platform's own traffic stays within OpenStreetMap's tile usage policy;
  heavy use would mean moving to another tile provider, a later change.
- Levels are filled by rank, not by a country's own labels,
  wherever the place data covers a point:
  in Canada, for example, a province is a first-level division
  and so would fill `state`.
  Where a country has no division at a rank
  (for example no comarca-like grouping for `region`),
  this is a failed lookup and the author types the name by hand.
- Local official names are kept in their own script,
  so a point in Tokyo, if its place data were loaded, would fill 東京都;
  an author who prefers another form types it by hand.
- Each form has at most one pinned point;
  drawing areas or boundaries is out of scope.
- Searching for a place by typing its name to move the marker
  is out of scope;
  typing only sets the name.
- Showing a movement's location on a map outside the form
  (detail view, listings) is out of scope;
  with no stored point there is nothing to show.
- Pinning is offered only where editing is allowed today —
  on drafts, by their author.
