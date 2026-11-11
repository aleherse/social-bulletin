# ADR-0024: Use OpenStreetMap tiles and a self-hosted place lookup

- Status: Proposed
- Date: 2026-10-16

## Context

Feature 002 (`specs/002-map-pinned-location/`) lets an author pin a movement's location on a map,
and fills the Location field with the local official name of the place at the rank the movement's area names:
`national` › `state` › `province` › `region` › `municipality` › `neighborhood`.

The feature's clarification session (2026-10-16, FR-011) settled where each half comes from.
The place lookup is the platform's own, with no separate service and no third-party geocoder:
the public Nominatim instance's usage policy forbids relying on it for an application's traffic,
and it would receive the pinned point.
The map's tiles come from OpenStreetMap's public tile servers.
A self-hosted PMTiles archive was built first and dropped:
it cost a 6.2 GB file per build, a bucket behaviour, and a tile-cutting tool,
for an MVP that does not need it.

The pinned point is personal data about where an author is or works.
FR-005 requires that it never appear in a URL, a log line, an error message, or a table:
only the name is saved.

FR-012 requires every point on Spanish land —
the mainland, the Balearic and Canary Islands, Ceuta, and Melilla —
to be named at `national` and `state`.
The first real build lost Spain's country boundary and Ceuta's,
because parts of both lie outside Geofabrik's Spain extract.

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

`apps/web` SHALL depend on `leaflet` `1.9.4` and `react-leaflet` `5.0.0`, pinned exactly,
with `@types/leaflet` `1.9.22` as a development dependency.

The basemap SHALL be drawn by react-leaflet's `<TileLayer>`
from `https://tile.openstreetmap.org/{z}/{x}/{y}.png` with `maxZoom` 19.

`apps/web/src/shared/map` SHALL be the only slice that imports `leaflet` or `react-leaflet`
(`application-frontend-0001`).
It SHALL expose a `LocationMap` component and the `INITIAL_VIEW` constant,
and nothing from the libraries themselves.

The map SHALL always show the attribution "© OpenStreetMap contributors",
linking to the OSM copyright page,
as the Open Database License and OSM's tile usage policy require.

### Map tiles

The browser SHALL request tiles straight from OpenStreetMap's public tile servers.
The platform SHALL NOT build, store, proxy, or serve tiles,
and SHALL NOT set a `Referrer-Policy` that strips the `Referer` the tile usage policy asks for.

OSM receives the map view the author looks at, never the pinned point.
After a pin the map flies to the point at zoom 14,
so the tiles requested then show the area around it, to about a kilometre.

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

A `map` tools container (`docker/map/`: `osmium-tool`, `jq`, `curl`),
under the Compose `tools` profile, SHALL build the places file:

- `make map-data` SHALL download the Geofabrik extracts for Spain, the Canary Islands, and the United Kingdom,
  fetch the complete Spain (`admin_level` 2, `ISO3166-1=ES`) and Ceuta (`ISO3166-2=ES-CE`) relations
  with every member from the Overpass API (`docker/map/overpass-boundaries.ql`),
  merge all four with `osmium`, filter them,
  and write `var/map/places.geojsonl`
  (one GeoJSON feature per line, in the shape of `apps/api/features/fixtures/places.geojsonl`);
- the build SHALL then run `docker/map/check-places.sh`,
  failing unless the file holds España at `admin_level` 2
  and Ceuta, Melilla, and Illes Balears at `admin_level` 4;
  an Overpass failure SHALL fail the build too;
- `make map-import` SHALL load `var/map/places.geojsonl` into the development database.

The import SHALL be the console command `app:places:import <path-or-url>`,
dispatching `ImportPlacesCommand`, whose handler reads features through
the `Domain/Helper/PlaceFeed` port (`domain-helpers-0002`)
and replaces both tables in one transaction through `PlaceRepository::replaceAll()`,
so lookups see the old data until the commit and a malformed line rolls the import back.

For `live` and `preview`, the manually dispatched `.github/workflows/map-data.yml`
SHALL build the file, upload it to the private map-data bucket's `places/` prefix,
presign it, and invoke the console Lambda with
`app:places:import '<presigned URL>'`, the URL single-quoted.
Map data SHALL be published on demand, not on every deploy.

### Testing

Behat and Playwright SHALL share synthetic fixture places
(`apps/api/features/fixtures/places.geojsonl`),
loaded by `apps/api/features/fixtures.feature` through the same import command production uses,
so they live in the DSLR `fixtures` snapshot.
The fixture España SHALL be a multi-part boundary with mainland, Balearic, Ceuta, and Melilla parts,
so Behat proves `national` and `state` outside the mainland.
Playwright journeys SHALL run against the real lookup endpoint
and SHALL abort every request to `https://tile.openstreetmap.org/**`, so no suite reaches OSM.

## Non-Goals

- Storing coordinates for a movement, or showing movements on a map.
- Forward search (typing a name to find a place on the map).
- Place names beyond Spain and the United Kingdom.
  A point elsewhere answers "no place at that level"; widening coverage means wider extracts, not a code change.
- Naming Spanish waters or the *plazas de soberanía* (spec FR-012).
- A tile server, a tile proxy, a geocoder service, or any process beyond the API and the database.
- Refreshing map data automatically.

## Implementation Plan

### Affected paths

- `apps/web/package.json`, `apps/web/package-lock.json`
- `apps/web/src/shared/map/` (the only Leaflet importer)
- `apps/web/src/shared/geo/` (`GeoPoint`, `wrapLongitude`, `currentPosition`)
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
- `docker/postgres/`, `docker/map/`, `docker-compose.yml`, `Makefile`
- `infrastructure/lib/frontend-stack.ts`
- `.github/workflows/map-data.yml`

### Steps

1. Build the local PostgreSQL image with PostGIS (`docker/postgres/Dockerfile`)
   and enable the extension in the migration that creates both place tables.
2. Add the `DomainPort` deptrac layer so `apps/api` may implement `Domain/Helper` interfaces.
3. Add the `Place` domain, the import command, the lookup query, the provider, and their specs.
4. Add the console command, the `PlaceFeed` adapter, the lookup controller, and the Behat features;
   load the fixture places in `fixtures.feature` and re-snapshot with `make db`.
5. Add `shared/geo`, `shared/map`, the lookup client, the picker, and their Vitest cases and journeys.
6. Add the `map` container with the Overpass fetch and the guard,
   `make map-data` and `make map-import`, the map-data bucket, and the publishing workflow.

### Patterns to follow

- The point in a `POST` body; "no place" as `200` with `name: null`.
- Domain validation with translation keys (`place.point.out_of_range`, `place.area.unsupported`),
  rendered through `DomainErrorListener` like `InvalidMovement`.
- Providers read with DBAL, the repository writes (`application-queries-0001`).
- Fixture data through the import command, never raw SQL (`test-integration-0001`).

### Patterns to avoid

- Any absolute URL to a geocoder host in `apps/web`.
- `GET` with coordinates in the query string.
- Logging a request body, or building an exception message from input, anywhere on the lookup path.
- Importing `leaflet` or `react-leaflet` outside `shared/map`.

## Verification

- [ ] `make php-unit`, `make api-tests`, `make web-unit`, and `make web-e2e` pass,
      with the lookup endpoint answering every row of
      `specs/002-map-pinned-location/contracts/place-lookup-api.md`'s expected-lookups table.
- [ ] `make lint` passes, including `php-deptrac` with the `DomainPort` layer.
- [ ] `grep -rn "https://" apps/web/src/shared/map` finds only the tile template and the attribution link.
- [ ] `docker/map/check-places.sh` passes on `make map-data`'s output
      and fails on a places file without Spain's outlying boundaries.
- [ ] On `preview`, the lookups for Huelin (all six levels), central Sheffield (`national`, `municipality`),
      and Palma, Las Palmas de Gran Canaria, Ceuta, and Melilla (`national`, `state`)
      match the spec against real OSM data.
- [ ] The import of the full data set completes within half the console Lambda's 15-minute limit.

## Consequences

- PostGIS becomes a database dependency: the local image is built rather than pulled,
  and the Aurora cluster runs the `postgis` extension.
- The database holds a few hundred thousand rows of reference data
  that belong to no author and are replaced wholesale by each import.
- The frontend's new and edit movement chunks carry Leaflet; other routes do not.
- The map depends on OpenStreetMap's community-funded tile servers:
  if they are unreachable the map has no basemap, and pinning still works.
  Traffic beyond their usage policy means moving to a commercial tile provider.
- OSM sees each author's IP address and the map view, including the area around a pin.
- The map data build depends on Geofabrik and the Overpass API being reachable,
  and fails rather than shipping without Spain's outlying boundaries.
- Map data goes stale until someone runs the workflow; OSM boundaries change slowly.
- The pinned point never leaves the request that carries it,
  so no privacy review is needed for stored or logged coordinates.
- The OSM `name` tag is shown as-is,
  so a bilingual area appears with its bilingual name (for example "Donostia/San Sebastián").

## Revisit Triggers

- The import approaches the console Lambda's 15-minute limit:
  batch the inserts first (multi-row, capped by bind parameters),
  then consider importing from a container task instead.
- Coverage must grow past Spain and the UK: the boundary rows grow with it; re-measure the import before widening.
- Lookups miss the 3-second budget (SC-001) at the 95th percentile:
  measure the GiST query on Aurora before changing the approach.
- The guard fails although Overpass answered: check the relations' tags
  (`admin_level`, `ISO3166-1`, `ISO3166-2`, `name`) before changing the query or the guard.
- Traffic approaches OSM's tile usage policy, or authors object to OSM seeing their map views:
  move to a commercial tile provider or a caching tile proxy.

## More Information

- Research and alternatives: `specs/002-map-pinned-location/research.md` R1–R10
  (self-hosted Nominatim, Photon, Pelias, a browser-side boundary file,
  the self-hosted PMTiles archive, a commercial tile provider, MapLibre GL, OpenLayers).
- Corrects in place [ADR-0009](ADR-0009-adopt-postgresql-and-doctrine-dbal.md) (PostGIS)
  and [ADR-0016](ADR-0016-aws-serverless-deployment.md) (map-data bucket).

### 2026-10-16: first build and import, measured locally

- `make map-data` took 17 minutes on a home connection,
  most of it downloading about 3.8 GB of Geofabrik extracts and cutting the tiles.
- `spain-uk.pmtiles` was 6.2 GB at `--maxzoom=15` from the 2026-10-12 Protomaps build;
  the archive was dropped later the same day in favour of OSM's tile servers.
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
  Huelin at `national` answered `null`:
  Spain's country relation did not assemble from the clipped Geofabrik extract,
  and Ceuta's did not either, which led to the Overpass fetch and the guard (FR-012).

### 2026-10-16: build with the Overpass fetch and the guard, measured locally

- `make map-data` took 6 minutes 39 seconds without the tiles;
  the Overpass fetch of Spain's and Ceuta's boundaries took about 53 seconds
  and returned 7.0 MB of OSM XML.
- The guard passed: España at `admin_level` 2,
  and Ceuta, Melilla, and Illes Balears at `admin_level` 4,
  so Ceuta's relation is at the level the guard expects.
- `places.geojsonl` is 491 MB: 32,732 boundaries and 33,067 neighbourhood nodes,
  three boundaries more than the first build.
- `make map-import` took 32 seconds,
  still far inside the 7.5-minute rule.
- Against the real data, Huelin answers España, Andalucía, Málaga, Málaga-Costa del Sol, Málaga, and Huelin
  for `national` to `neighborhood`,
  and central Sheffield answers United Kingdom at `national` and Sheffield at `municipality`.
  At `national` every FR-012 point answers España;
  at `state`, Palma answers Illes Balears, Las Palmas de Gran Canaria answers Canarias,
  Ceuta answers Ceuta, and Melilla answers Melilla.

### 2026-11-11: rebuild with a duplicated node, measured locally

- The first rebuild failed in `osmium export` with "Node ID twice in input".
  The Spain and Canary Islands extracts overlap at their edge
  and come from different snapshots,
  so a ferry terminal node (345366405) carried version 20 and version 21;
  `osmium merge` removes only duplicates identical in id and version.
  The 2026-10-16 builds worked because both extracts held the same versions then.
  The script now merges Spain and the United Kingdom,
  then applies the Canary Islands and Overpass files with `osmium apply-changes`,
  which keeps the newest version of each object.
- `make map-data` took 7 minutes 12 seconds,
  including one Overpass 502 that `--retry` recovered from.
  `places.geojsonl` is 490 MB: 32,746 boundaries and 33,203 neighbourhood nodes.
- `make map-import` took 33.3 seconds.
- Against the real data, every answer of the 2026-10-16 entry above is unchanged,
  for Huelin, central Sheffield, and the four FR-012 points.
