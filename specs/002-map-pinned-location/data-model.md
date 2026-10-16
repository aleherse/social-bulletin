# Data Model: Pin a Movement's Location on a Map

## Persisted data

**No change.**
The `Movement` aggregate, the `bulletin.movements` table,
and the API payloads keep exactly the fields defined in
[001 data-model.md](../001-propose-movement/data-model.md):
`location` stays a nullable string,
required unless `area` is `international` (FR-006).
No migration, no `db/schema.dbml` change.

FR-005 is satisfied structurally:
`MovementDraftInput` has no coordinate fields,
so the form cannot submit a point,
and the geocoder is called from the browser (research R7),
so the point never reaches the platform's servers or logs.

## Client-side values (`apps/web`)

### GeoPoint — `shared/geo`

| Field | Type | Rule |
|-------|------|------|
| `lat` | number | −90 … 90 |
| `lon` | number | −180 … 180, normalised by `wrapLongitude` |

Produced by a map click or by the browser's position;
lives only in component state while the form is open.
`wrapLongitude` maps any longitude into range,
so a click on a map scrolled past the date line
looks up the same real place (spec edge case).

### PlaceHierarchy — `shared/geo`

The subset of the geocoder response the app reads
(see [contracts/reverse-geocoding.md](contracts/reverse-geocoding.md)):

| Field | Type | Source |
|-------|------|--------|
| `country` | string \| null | `properties.geocoding.country` |
| `admin` | `Partial<Record<'level4' \| 'level6' \| 'level7' \| 'level8' \| 'level10', string>>` | `properties.geocoding.admin` |
| `locality` | string \| null | `properties.geocoding.locality` |

An empty `features` array yields `null` (nothing at that point, e.g. at sea).

### Place name for an area — `entities/movement`

`placeNameFor(area: Area, place: PlaceHierarchy): string | null`,
a pure function implementing research R3:

| `area` | Returns |
|--------|---------|
| `national` | `country` |
| `state` | `admin.level4` |
| `province` | `admin.level6` |
| `region` | `admin.level7` |
| `municipality` | `admin.level8` |
| `neighborhood` | `admin.level10`, else `locality` |
| `international` | `null` (never called: no map) |

`null` means "no place at the chosen level" (FR-009).

## Location picker state — `features/propose-movement`

Held by the movement form alongside its existing fields.

| State | Type | Meaning |
|-------|------|---------|
| `point` | `GeoPoint \| null` | Current marker; `null` on a new form and on every edit form (FR-010) |
| `lookup` | `'idle' \| 'pending' \| 'not-found' \| 'unavailable'` | Status of the latest place-name lookup |
| `position` | `'idle' \| 'pending' \| 'denied' \| 'unavailable'` | Status of the latest "use my location" request |
| `editedSincePin` | boolean | Set when the author types in the location field after the latest pin |

### Transitions

| Event | Effect |
|-------|--------|
| Map click at `p` | Abort any in-flight lookup; `point = wrap(p)`; `editedSincePin = false`; `lookup = 'pending'`; request lookup for `point` with the current area |
| "Use my location" | `position = 'pending'`; on success behave as a map click at the position, `position = 'idle'`; on permission refusal `position = 'denied'`; on any other failure `position = 'unavailable'`; location field untouched (FR-008) |
| Lookup resolves with a name | `lookup = 'idle'`; if `editedSincePin` is false, set the location field to the name (spec: slow-lookup edge case) |
| Lookup resolves with `null` name | `lookup = 'not-found'`; marker kept; field untouched (FR-009) |
| Lookup fails (network, non-2xx, malformed body) | `lookup = 'unavailable'`; marker kept; field untouched (FR-009) |
| Author types in location field | `editedSincePin = true`; marker unchanged (FR-004) |
| Area changes between non-`international` levels | Nothing; no lookup, field untouched (clarification Q4, FR-003) |
| Area changes to `international` | Abort lookup; `point = null`; `lookup = 'idle'`; map hidden (FR-007) |
| Save | Submits the existing `MovementDraftInput`; `point` is dropped with the form (FR-005) |

The lookup is made with the area selected **at pin time**;
a later area change does not re-run or re-target it.
