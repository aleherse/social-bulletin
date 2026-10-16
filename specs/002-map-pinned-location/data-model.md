# Data Model: Pin a Movement's Location on a Map

## Movements: no change

The `Movement` aggregate, the `bulletin.movements` table,
and the movement API payloads keep exactly the fields defined in
[001 data-model.md](../001-propose-movement/data-model.md):
`location` stays a nullable string,
required unless `area` is `international` (FR-006).

FR-005 is satisfied structurally:
`MovementDraftInput` has no coordinate fields,
and no table below has a column that could hold an author's point.

## Persisted reference data: places (new)

OpenStreetMap data for Spain and the United Kingdom (FR-011, research R1),
replaced wholesale by each import (research R7).
It belongs to no author and holds no personal data.
Migration: `CREATE EXTENSION IF NOT EXISTS postgis` and both tables;
`db/schema.dbml` documents them.

### `bulletin.place_boundaries`

| Column | Type | Rule |
|--------|------|------|
| `osm_id` | `bigint` | PK; the OSM relation id |
| `admin_level` | `smallint` | NOT NULL; one of 2, 4, 6, 7, 8, 10 (`CHECK`) |
| `name` | `text` | NOT NULL, non-empty; the OSM `name` tag (research R2) |
| `geom` | `geometry(MultiPolygon, 4326)` | NOT NULL |

Indexes: GiST on `geom`; b-tree on `admin_level`.

### `bulletin.place_neighbourhoods`

| Column | Type | Rule |
|--------|------|------|
| `osm_id` | `bigint` | PK; the OSM node id |
| `name` | `text` | NOT NULL, non-empty |
| `geom` | `geometry(Point, 4326)` | NOT NULL |

Index: GiST on `geom`.

## Core values (`packages/core`)

### `Domain/Place/GeoPoint`

| Field | Type | Rule |
|-------|------|------|
| `latitude` | float | −90 … 90 |
| `longitude` | float | −180 … 180 |

`GeoPoint::fromDegrees(float $latitude, float $longitude)`
throws `InvalidPlaceLookup` with the field key (`lat` or `lon`) mapped to `place.point.out_of_range`.
No message or error carries the coordinates (FR-005).

### `Domain/Place/PlaceLevel`

`PlaceLevel::forArea(Area $area): self`, holding the OSM `admin_level` (research R3):
`national` 2, `state` 4, `province` 6, `region` 7, `municipality` 8, `neighborhood` 10.
`international` throws `InvalidPlaceLookup` with `area` mapped to `place.area.unsupported`.
`usesNeighbourhoodFallback(): bool` is true for level 10 only.

### `Domain/Place/InvalidPlaceLookup`

A `DomainError` (`domain-helpers-0003`), `STATUS_CODE = 422`,
carrying `errors` (field → translation key) like `InvalidMovement`,
so `DomainErrorListener` renders JSON:API pointers for it as well.

### `Domain/Place/Boundary` and `Domain/Place/Neighbourhood`

What one import line becomes.
`Boundary::fromFeature(array $feature)` and `Neighbourhood::fromFeature(array $feature)`
read `properties.osm_id`, `properties.name`, `properties.admin_level` (boundaries only),
and the GeoJSON `geometry`, kept as a JSON string for `ST_GeomFromGeoJSON`.
A feature with no name, an unsupported rank, or the wrong geometry type
throws `InvalidPlaceFeed` naming the line number.

### `Domain/Helper/PlaceFeed` (port)

`features(string $source): iterable<int, array<string, mixed>>`,
keyed by line number, one decoded feature per line.
`apps/api` implements it as `GeoJsonSeqPlaceFeed`
over a local path or an HTTPS URL (research R7).

### `Domain/Place/PlaceRepository`

Concrete, constructed with `Connection` (`database-dbal-0001`).
`replaceAll(iterable<Boundary|Neighbourhood> $places): void`
deletes both tables and inserts in batches inside one transaction.

### `Application/Place/Provider/PlaceProvider` and `Model/Place`

`nameAt(GeoPoint $point, PlaceLevel $level): ?Place`
(`application-queries-0001`, `-0002`),
where `Model\Place` is `final readonly class Place { public string $name; }`:

1. the boundary at `$level` whose `geom` covers the point (`ST_Covers`),
   preferring the smallest area if two overlap;
2. for level 10 only, when no boundary covers it,
   the nearest neighbourhood point (`geom <-> point`)
   if `ST_DistanceSphere` is at most 1000 m;
3. otherwise `null`.

### Use cases

| Use case | Class | Result |
|----------|-------|--------|
| Look up a place name | `Application/Place/Query/LookUpPlaceQuery` (`fromPayload`: `lat`, `lon`, `area`) → `LookUpPlaceHandler` | `?Place` |
| Import places | `Application/Place/Command/ImportPlacesCommand` (`source`) → `ImportPlacesHandler` | `void` |

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

### Place-name lookup — `entities/movement`

`lookUpPlaceName(point: GeoPoint, area: Area, signal: AbortSignal): Promise<string | null>`
posts to `/api/places/lookup` ([contracts/place-lookup-api.md](contracts/place-lookup-api.md)).
`null` means "no place at the chosen level" (FR-009);
a non-2xx response, a network failure, a malformed body,
or no answer within 10 s rejects with `PlaceLookupUnavailable`.

## Location picker state — `features/propose-movement`

Unchanged from the first round apart from the lookup's source.
`LocationPicker` holds the marker and both request statuses;
the movement form holds only what decides whether a found name may fill its field.
Unmounting the picker (area set to `international`) drops its state.

| State | Held by | Type | Meaning |
|-------|---------|------|---------|
| `point` | `LocationPicker` | `GeoPoint \| null` | Current marker; `null` on a new form and on every edit form (FR-010) |
| `lookup` | `LocationPicker` (via `usePlaceLookup`) | `'idle' \| 'pending' \| 'not-found' \| 'unavailable'` | Status of the latest place-name lookup |
| `position` | `LocationPicker` | `'idle' \| 'pending' \| 'denied' \| 'unavailable'` | Status of the latest "use my location" request |
| `editedSincePin` | `MovementForm` (a ref; it never drives rendering) | boolean | Set when the author types in the location field after the latest pin |

### Transitions

| Event | Effect |
|-------|--------|
| Map click at `p` | Abort any in-flight lookup; `point = wrap(p)`; `editedSincePin = false`; `lookup = 'pending'`; look up `point` with the current area |
| "Use my location" | `position = 'pending'`; on success behave as a map click at the position, `position = 'idle'`; on permission refusal `position = 'denied'`; on any other failure `position = 'unavailable'`; location field untouched (FR-008) |
| Lookup resolves with a name | `lookup = 'idle'`; if `editedSincePin` is false, set the location field to the name (spec: slow-lookup edge case) |
| Lookup resolves with `null` | `lookup = 'not-found'`; marker kept; field untouched (FR-009) |
| Lookup fails (network, non-2xx, malformed body, no answer within 10 s) | `lookup = 'unavailable'`; marker kept; field untouched (FR-009) |
| Author types in location field | `editedSincePin = true`; marker unchanged (FR-004) |
| Area changes between non-`international` levels | Nothing; no lookup, field untouched (clarification Q4, FR-003) |
| Area changes to `international` | Picker unmounts: lookup aborted, `point` dropped, map hidden (FR-007); a later return to a non-`international` level starts with no marker |
| Map fails to render | Error boundary drops the map; Location field and "use my location" keep working (spec edge case) |
| Save | Submits the existing `MovementDraftInput`; `point` is dropped with the form (FR-005) |

The lookup is made with the area selected **at pin time**;
a later area change does not re-run or re-target it.
