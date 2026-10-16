# ADR-0022: Self-host map tiles and place lookup

- Status: Proposed
- Date: 2026-10-16

## Context

Feature 002 (`specs/002-map-pinned-location/`) lets an author pin a movement's location on a map,
and fills the Location field with the local official name of the place at the rank the movement's area names:
`national` › `state` › `province` › `region` › `municipality` › `neighborhood`.

The feature's clarification session (2026-10-16, FR-011) ruled out
any third-party request at runtime and any separately running service.
The first draft of the plan used the public OpenStreetMap tile servers
and the public Nominatim instance, called straight from the browser;
both are third parties, and Nominatim's usage policy forbids relying on it for an application's traffic.

The pinned point is personal data about where an author is or works.
FR-005 requires that it never appear in a URL, a log line, an error message, or a table:
only the name is saved.

The stack this decision builds on:

- `apps/web` is React and Vite under Feature-Sliced Design
  ([ADR-0007](ADR-0007-adopt-react-vite-npm-and-fsd.md)),
  with lazily loaded route chunks
  ([ADR-0019](ADR-0019-adopt-react-router.md)).
- nginx serves the compiled bundle locally
  ([ADR-0004](ADR-0004-adopt-nginx-to-serve-php-and-compiled-frontend.md));
  in AWS a private S3 bucket behind CloudFront does
  ([ADR-0016](ADR-0016-aws-serverless-deployment.md)).
- The API runs on Bref Lambda against Aurora Serverless v2 PostgreSQL 17
  ([ADR-0016](ADR-0016-aws-serverless-deployment.md)),
  through DBAL with raw-SQL migrations
  ([ADR-0009](ADR-0009-adopt-postgresql-and-doctrine-dbal.md)).
- Reads and writes are split into queries and commands
  ([ADR-0021](ADR-0021-adopt-cqrs.md)).

## Decision

### Map rendering

`apps/web` SHALL depend on `leaflet` `1.9.4`, `react-leaflet` `5.0.0`,
and `protomaps-leaflet` `5.1.0`, all pinned exactly,
with `@types/leaflet` `1.9.22` as a development dependency.

The basemap SHALL be drawn by `protomaps-leaflet`'s `leafletLayer({ url, flavor: 'light' })`,
which reads a PMTiles archive directly and draws to a 2D canvas, so the map needs no WebGL.

`apps/web/src/shared/map` SHALL be the only slice that imports
`leaflet`, `react-leaflet`, or `protomaps-leaflet`
(`application-frontend-0001`).
It SHALL expose a `LocationMap` component and the `INITIAL_VIEW` and `TILES_URL` constants,
and nothing from the libraries themselves.

The map SHALL always show the attribution
"© OpenStreetMap contributors, Protomaps", linking to the OSM copyright page,
as the Open Database License requires.

### Map tiles

The basemap SHALL be one Protomaps PMTiles extract, `spain-uk.pmtiles`,
covering mainland Spain, the Balearic and Canary Islands, Ceuta, Melilla,
Great Britain, and Northern Ireland, to zoom 15.

It SHALL be served as a static file at the relative path `/tiles/spain-uk.pmtiles`
on the frontend's own origin, read by the browser with HTTP range requests:

- locally, nginx SHALL serve `/tiles/` from `var/map/tiles/`,
  answering a missing file with 404 rather than the SPA's `index.html`;
- in AWS, a private map-data S3 bucket SHALL sit behind a `/tiles/*` behaviour
  on the existing frontend CloudFront distribution,
  through origin access control and the managed caching-optimised policy.

The extract SHALL be cut with the `pmtiles` CLI from a dated Protomaps daily build,
limited to the polygon in `infrastructure/map/coverage.geojson`,
so the planet file is never downloaded.

### Place lookup

Place names SHALL come from OpenStreetMap administrative boundaries
stored in the existing PostgreSQL database with the PostGIS extension:

- `bulletin.place_boundaries`: `boundary=administrative` relations
  at `admin_level` 2, 4, 6, 7, 8, or 10 with a `name`,
  as `geometry(MultiPolygon, 4326)` with a GiST index;
- `bulletin.place_neighbourhoods`: `place=neighbourhood|quarter|suburb` nodes with a `name`,
  as `geometry(Point, 4326)` with a GiST index.

Each stored name SHALL be the OSM `name` tag, which is the local official name;
`name:*` translations SHALL NOT be stored.

Area levels SHALL map to OSM ranks in `packages/core`'s `Domain/Place/PlaceLevel`:
`national` 2, `state` 4, `province` 6, `region` 7, `municipality` 8, `neighborhood` 10.
`international` SHALL be rejected.
For `neighborhood` only, when no level-10 boundary covers the point,
the nearest neighbourhood node within 1000 m (`ST_DistanceSphere`) SHALL answer.
No other level SHALL fall back to a neighbouring rank.

The lookup SHALL be `POST /api/places/lookup`
with `{"lat": <number>, "lon": <number>, "area": "<area>"}`,
behind the existing authenticated `^/api` firewall,
answering `200 {"name": "<name>"}` or `200 {"name": null}` when no place exists at the rank,
and `422` with JSON:API pointers for an invalid point or area.
It SHALL be a `packages/core` query (`LookUpPlaceQuery` → `PlaceProvider::nameAt()`)
answered by one indexed `ST_Covers` query, preferring the smallest area when boundaries overlap.

### The point is never kept

The point SHALL travel only in the lookup's request body.
It SHALL NOT be stored in any table, written to any log,
or included in any URL, error message, or response.
Validation failures SHALL carry translation keys, never the submitted values.
The movement itself SHALL keep storing only the location's name.

### Map data pipeline

A `map` tools container (`docker/map/`: `osmium-tool`, `jq`, a checksum-verified `pmtiles` release),
under the Compose `tools` profile, SHALL build both data files:

- `make map-data` SHALL download the Geofabrik extracts for Spain, the Canary Islands, and the United Kingdom,
  merge and filter them with `osmium`, and write `var/map/places.geojsonl`
  (one GeoJSON feature per line, in the shape of `apps/api/features/fixtures/places.geojsonl`),
  and cut `var/map/tiles/spain-uk.pmtiles`;
- `make map-import` SHALL load `var/map/places.geojsonl` into the development database.

The import SHALL be the console command `app:places:import <path-or-url>`,
dispatching `ImportPlacesCommand`, whose handler reads features through
the `Domain/Helper/PlaceFeed` port (`domain-helpers-0002`)
and replaces both tables in one transaction through `PlaceRepository::replaceAll()`,
so lookups see the old data until the commit and a malformed line rolls the import back.

For `live` and `preview`, the manually dispatched `.github/workflows/map-data.yml`
SHALL build the files, upload the archive to the map-data bucket's `tiles/` prefix
and the places to its `places/` prefix,
presign the places object, and invoke the console Lambda with
`app:places:import '<presigned URL>'`, the URL single-quoted.
Map data SHALL be published on demand, not on every deploy.

### Testing

Behat and Playwright SHALL share synthetic fixture places
(`apps/api/features/fixtures/places.geojsonl`),
loaded by `apps/api/features/fixtures.feature` through the same import command production uses,
so they live in the DSLR `fixtures` snapshot.
Playwright journeys SHALL run against the real lookup endpoint
and SHALL abort every request to `/tiles/**`, so no suite needs the archive or the network.

## Non-Goals

- Storing coordinates for a movement, or showing movements on a map.
- Forward search (typing a name to find a place on the map).
- Coverage beyond Spain and the United Kingdom.
  A point elsewhere answers "no place at that level"; widening coverage means a wider extract, not a code change.
- A tile server, a geocoder service, or any process beyond the API and the database.
- Refreshing map data automatically.

## Implementation Plan

### Affected paths

- `apps/web/package.json`, `apps/web/package-lock.json`
- `apps/web/src/shared/map/` (new; the only Leaflet importer)
- `apps/web/src/shared/geo/` (new; `GeoPoint`, `wrapLongitude`, `currentPosition`)
- `apps/web/src/entities/movement/api/look-up-place-name.ts`
- `apps/web/src/features/propose-movement/`
- `apps/web/e2e/` (journeys, `support/map.ts`, `stubMapTiles` fixture)
- `packages/core/src/Domain/Place/`, `packages/core/src/Domain/Helper/PlaceFeed.php`,
  `packages/core/src/Application/Place/`
- `apps/api/migrations/`, `apps/api/src/Controller/Place/`, `apps/api/src/Console/`,
  `apps/api/src/Place/`, `apps/api/config/services.yaml`
- `apps/api/features/places.feature`, `apps/api/features/fixtures.feature`,
  `apps/api/features/fixtures/*.geojsonl`, `apps/api/tests/Behat/PlaceContext.php`
- `db/schema.dbml`, `deptrac.yaml`
- `docker/postgres/`, `docker/map/`, `docker/nginx/conf.d/web.conf`, `docker-compose.yml`, `Makefile`
- `infrastructure/map/coverage.geojson`, `infrastructure/lib/frontend-stack.ts`
- `.github/workflows/map-data.yml`

### Steps

1. Build the local PostgreSQL image with PostGIS (`docker/postgres/Dockerfile`)
   and enable the extension in the migration that creates both place tables.
2. Add the `DomainPort` deptrac layer so `apps/api` may implement `Domain/Helper` interfaces.
3. Add the `Place` domain, the import command, the lookup query, the provider, and their specs.
4. Add the console command, the `PlaceFeed` adapter, the lookup controller, and the Behat features;
   load the fixture places in `fixtures.feature` and re-snapshot with `make db`.
5. Add `shared/geo`, `shared/map`, the lookup client, the picker, and their Vitest cases and journeys.
6. Add the `map` container, `make map-data` and `make map-import`, the nginx `/tiles/` location,
   the map-data bucket and `/tiles/*` behaviour, and the publishing workflow.

### Patterns to follow

- The point in a `POST` body; "no place" as `200` with `name: null`.
- Domain validation with translation keys (`place.point.out_of_range`, `place.area.unsupported`),
  rendered through `DomainErrorListener` like `InvalidMovement`.
- Providers read with DBAL, the repository writes (`application-queries-0001`).
- Fixture data through the import command, never raw SQL (`test-integration-0001`).

### Patterns to avoid

- Any absolute URL to a tile, font, sprite, or geocoder host in `apps/web`.
- `GET` with coordinates in the query string.
- Logging a request body, or building an exception message from input, anywhere on the lookup path.
- Importing `leaflet` or `protomaps-leaflet` outside `shared/map`.

## Verification

- [ ] `make php-unit`, `make api-tests`, `make web-unit`, and `make web-e2e` pass,
      with the lookup endpoint answering every row of
      `specs/002-map-pinned-location/contracts/place-lookup-api.md`'s expected-lookups table.
- [ ] `make lint` passes, including `php-deptrac` with the `DomainPort` layer.
- [ ] `grep -rn "https://" apps/web/src/shared/map` finds only the attribution links.
- [ ] `curl -I -H 'Range: bytes=0-15'` on `/tiles/spain-uk.pmtiles` answers `206`
      locally after `make map-data` and on `preview` after the workflow runs.
- [ ] On `preview`, the lookups for Huelin (all six levels) and central Sheffield
      (`national`, `municipality`) match the contract's table against real OSM data.
- [ ] The import of the full data set completes within half the console Lambda's 15-minute limit.

## Consequences

- PostGIS becomes a database dependency: the local image is built rather than pulled,
  and the Aurora cluster runs the `postgis` extension.
- The database holds a few hundred thousand rows of reference data
  that belong to no author and are replaced wholesale by each import.
- The frontend's new and edit movement chunks carry Leaflet and `protomaps-leaflet`;
  other routes do not.
- A new bucket and CloudFront behaviour serve the archive, at the cost of its storage and transfer.
- Map data goes stale until someone runs the workflow; OSM boundaries change slowly.
- The pinned point never leaves the request that carries it,
  so no privacy review is needed for stored or logged coordinates.
- The OSM `name` tag is shown as-is,
  so a bilingual area appears with its bilingual name (for example "Donostia/San Sebastián").

## Revisit Triggers

- The import approaches the console Lambda's 15-minute limit:
  batch the inserts first (multi-row, capped by bind parameters),
  then consider importing from a container task instead.
- Coverage must grow past Spain and the UK:
  the tiles and the boundary rows grow with it; re-measure both before widening.
- Lookups miss the 3-second budget (SC-001) at the 95th percentile:
  measure the GiST query on Aurora before changing the approach.
- A Geofabrik extract stops yielding a country's `admin_level` 2 polygon:
  add the relation from a wider extract with `osmium getid` rather than changing the lookup.

## More Information

- Research and alternatives: `specs/002-map-pinned-location/research.md` R1–R10
  (self-hosted Nominatim, Photon, Pelias, a browser-side boundary file, raster tiles, MapLibre GL, OpenLayers).
- Corrects in place [ADR-0009](ADR-0009-adopt-postgresql-and-doctrine-dbal.md) (PostGIS)
  and [ADR-0016](ADR-0016-aws-serverless-deployment.md) (map-data bucket and `/tiles/*` behaviour).
- `protomaps-leaflet` `5.1.0` was the latest 5.x release at install (2026-10-16)
  and makes no network request of its own beyond the archive it is given.

### 2026-10-16: first build and import, measured locally

- `make map-data` took 17 minutes on a home connection,
  most of it downloading about 3.8 GB of Geofabrik extracts and cutting the tiles.
- `spain-uk.pmtiles` is 6.2 GB at `--maxzoom=15` from the 2026-10-12 Protomaps build.
- `places.geojsonl` is 490 MB: 32,729 boundaries and 33,067 neighbourhood nodes.
  `osmium export` names an assembled area `a<2 × relation id + 1>`,
  which the `jq` filter turns back into the relation id.
- `make map-import` took 29 seconds, peaking at about 160 MB:
  far inside the 7.5-minute rule, so `PlaceRepository::replaceAll()` keeps one insert per row.
  The largest boundary (Highland, 8.4 MB of GeoJSON) decodes to more than PHP's default 128 MB,
  so `app:places:import` raises its own memory limit to 512 MB;
  the console Lambda has 1024 MB.
- Against the real data, Huelin answers Andalucía, Málaga, Málaga-Costa del Sol, Málaga, and Huelin
  for `state` to `neighborhood`, and central Sheffield answers United Kingdom, England, and Sheffield,
  as the contract expects.
  Huelin at `national` answers `null`:
  Spain's country relation does not assemble from the clipped Geofabrik extract,
  the known risk recorded under Revisit Triggers.
