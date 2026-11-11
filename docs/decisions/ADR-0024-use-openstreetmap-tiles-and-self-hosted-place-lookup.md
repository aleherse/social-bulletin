# ADR-0024: Use OpenStreetMap Tiles And A Self-Hosted Place Lookup

- Status: Accepted
- Date: 2026-10-16

## Context

An author can pin a movement's location on a map.
The Location field is then filled with the local official name of the place at the rank the movement's area names:
`national` › `state` › `province` › `region` › `municipality` › `neighborhood`
(`specs/002-map-pinned-location/`).

The pinned point is personal data about where an author is or works,
so it must never reach a third-party geocoder, a URL, a log line, an error message, or a table.
The public Nominatim instance also forbids relying on it for an application's traffic.
A self-hosted PMTiles archive for the basemap was built first and dropped:
a 6.2 GB file per build and a tile-cutting tool, for an MVP that does not need them.

Every point on Spanish land, including the Balearic and Canary Islands, Ceuta and Melilla,
must be named at `national` and `state`.
Geofabrik's Spain extract lacks parts of Spain's and Ceuta's boundaries, so the build needs more than the extracts.

## Decision

### Map rendering and tiles

- `apps/web` SHALL depend on `leaflet` and `react-leaflet`, pinned exactly, and draw the basemap with `<TileLayer>`
  from `https://tile.openstreetmap.org/{z}/{x}/{y}.png`.
- `apps/web/src/shared/map` SHALL be the only slice that imports either library (`application-frontend-0001`).
- The map SHALL always show the attribution "© OpenStreetMap contributors", linking to the OSM copyright page.
- The browser SHALL request tiles straight from OpenStreetMap.
  The platform SHALL NOT build, store, proxy or serve tiles,
  nor set a `Referrer-Policy` that strips the `Referer` OSM's usage policy asks for.
  OSM sees the map view, never the pinned point.

### Place lookup

- Names SHALL come from OpenStreetMap administrative boundaries held in the existing PostgreSQL database with PostGIS:
  `bulletin.place_boundaries` (`admin_level` 2, 4, 6, 7, 8 or 10, `MultiPolygon`) and
  `bulletin.place_neighbourhoods` (`place=neighbourhood|quarter|suburb` nodes, `Point`), both with GiST indexes.
  The stored name is the OSM `name` tag; translations are not stored.
- PostgreSQL SHALL run with the PostGIS extension.
  The local container SHALL be built from `docker/postgres/Dockerfile` (`postgres:17` plus `postgresql-17-postgis-3`),
  matching Aurora PostgreSQL 17 ([ADR-0016](ADR-0016-aws-serverless-deployment.md)).
  A migration SHALL enable the extension (`CREATE EXTENSION IF NOT EXISTS postgis`) and create both tables,
  so the same migration runs on Aurora.
- `Domain/Place/PlaceLevel` SHALL map the areas to OSM ranks:
  `national` 2, `state` 4, `province` 6, `region` 7, `municipality` 8, `neighborhood` 10.
  `international` is rejected.
  Only `neighborhood` falls back, to the nearest neighbourhood node within 1000 m.
- `POST /api/places/lookup` with `lat`, `lon` and `area`, behind the authenticated `^/api` firewall,
  SHALL answer `200` with the name, `200` with `name: null` when no place exists at that rank,
  and `422` for an invalid point or area.
  It is a `LookUpPlaceQuery` answered by `PlaceProvider::nameAt()` with one indexed `ST_Covers` query,
  preferring the smallest area when boundaries overlap.
- The point SHALL travel only in the request body.
  Validation failures SHALL carry translation keys, never the submitted values.
  The movement keeps storing only the location's name.

### Map data

- `make map-data` SHALL run a `map` tools container (`docker/map/`) that downloads the Geofabrik extracts for Spain,
  the Canary Islands and the United Kingdom, fetches Spain's and Ceuta's complete relations from the Overpass API,
  merges them with `osmium apply-changes` (the extracts overlap and come from different snapshots),
  and writes `var/map/places.geojsonl`, one GeoJSON feature per line.
  `docker/map/check-places.sh` SHALL then fail the build unless España, Ceuta, Melilla and Illes Balears are present.
- `app:places:import <path-or-url>` SHALL dispatch `ImportPlacesCommand`.
  Its handler reads the file through `Application/Helper/JsonLinesReader`
  and replaces both tables in one transaction through `PlaceRepository::replaceAll()`,
  so lookups see the old data until the commit and a malformed line rolls the import back.
- `make map-import` SHALL load the file locally.
  For `live` and `preview`, the manual `.github/workflows/map-data.yml` SHALL build the file,
  upload it to the private map-data bucket, and run the import on the console Lambda with a presigned URL.
  Map data is published on demand, not on every deploy.

### Testing

- Behat and Playwright SHALL share synthetic fixture places (`apps/api/features/fixtures/places.geojsonl`),
  loaded by `fixtures.feature` through the import command production uses (`test-integration-0001`).
- Journeys SHALL use the real lookup endpoint and abort every request to `https://tile.openstreetmap.org/**`.

## Consequences

- PostGIS becomes a database dependency: the local image is built rather than pulled,
  and Aurora runs the `postgis` extension.
- The database holds about 65,000 rows of reference data that belong to no author and are replaced by each import.
  A full build takes about seven minutes and the import about 30 seconds locally,
  far inside half the console Lambda's 15-minute limit, so inserts stay one per row.
  If the import approaches that limit, batch the inserts before moving it to a container task.
- `app:places:import` raises its own memory limit to 512 MB,
  because the largest boundary (Highland) decodes to more than PHP's default 128 MB.
- The map data file lives in its own private bucket, not behind CloudFront
  ([ADR-0016](ADR-0016-aws-serverless-deployment.md)).
- The frontend's new and edit movement chunks carry Leaflet; other routes do not.
- The map depends on OpenStreetMap's tile servers and shows no basemap if they are unreachable; pinning still works.
  OSM sees each author's IP address and the map view, including the area around a pin.
  Traffic beyond their usage policy means moving to a commercial tile provider or a caching proxy.
- The build depends on Geofabrik and the Overpass API, and fails rather than ship without Spain's outlying boundaries.
- Coverage is Spain and the United Kingdom; elsewhere the lookup answers `null`.
  Widening it means wider extracts and a re-measured import, not a code change.
- Map data goes stale until someone runs the workflow; OSM boundaries change slowly.
- The OSM `name` tag is shown as-is, so a bilingual area appears with its bilingual name.
- Research and rejected alternatives (self-hosted Nominatim, Photon, Pelias, MapLibre GL, OpenLayers) are in
  `specs/002-map-pinned-location/research.md`.
