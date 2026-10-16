# Research: Pin a Movement's Location on a Map

Phase 0 output for [plan.md](plan.md).
Each entry records the decision, why it was chosen, and what was rejected.

Revised 2026-10-16 for the self-hosting clarifications (spec session 2026-10-16, FR-011).
The first round chose public OSM Nominatim and OSM raster tiles called from the browser;
R1, R5, R6, and R7 of that round are superseded by R1, R4, R5, and R6 below.

## R1. Place-name lookup: PostGIS point-in-polygon in the existing database

**Decision**: Import OpenStreetMap administrative boundaries for Spain and the United Kingdom
into two tables in the existing PostgreSQL database (`bulletin` schema, ADR-0000),
with the PostGIS extension enabled,
and answer each lookup with one indexed `ST_Covers` query
behind a new `apps/api` endpoint backed by `packages/core`.

- `bulletin.place_boundaries`: `boundary=administrative` relations
  with `admin_level` 2, 4, 6, 7, 8, or 10 and a `name`,
  as `geometry(MultiPolygon, 4326)` with a GiST index.
- `bulletin.place_neighbourhoods`: `place=neighbourhood|quarter|suburb` nodes with a `name`,
  as `geometry(Point, 4326)` with a GiST index,
  for the neighbourhood fallback (R3).

**Rationale**:

- The feature asks one question —
  which named area at one admin rank contains this point —
  so a geocoder is more than it needs;
  a point-in-polygon query answers it exactly.
- No new service: the API already runs on Bref Lambda against Aurora Serverless v2 (ADR-0016),
  Aurora PostgreSQL 17 ships PostGIS,
  and the cluster's `bulletin` user is the generated master user
  (`infrastructure/lib/database-stack.ts`), which may `CREATE EXTENSION`.
- OSM `admin_level` is the rank the spec's clarification Q2 asks for,
  with no translation through a geocoder's address keys.
- The earlier Nominatim check (2026-10-16, public instance) confirmed the ranks:
  Huelin returns levels 4/6/7/8/10 = Andalucía / Málaga / Málaga-Costa del Sol / Málaga / Huelin,
  central Sheffield returns level 4 England and level 8 Sheffield.
  The same OSM relations feed this import.

**Alternatives considered**:

- *Self-hosted Nominatim*: a full geocoder needs an always-on server
  with its own PostgreSQL and a multi-hour import,
  which is a separate service outside the serverless model (ADR-0016).
  Rejected.
- *Photon or Pelias*: also separate services, and their output keys
  follow address tagging, not admin rank. Rejected.
- *Lookup in the browser over a shipped boundary file*:
  boundary geometry for two countries is far too large to ship to every author.
  Rejected.

## R2. Local official names

**Decision**: Store and return each OSM object's `name` tag.

**Rationale**: In OSM, `name` is the name in the local language(s),
and translations live in `name:*` keys,
so storing `name` alone meets clarification Q3 by construction
(España, Andalucía, Málaga; United Kingdom, England, Sheffield).
Bilingual areas keep their bilingual `name` (e.g. "Donostia/San Sebastián"),
which is that place's official local form.
The `accept-language=xx` workaround from the first round is no longer needed.

**Alternatives considered**: storing `name:es` / `name:en`.
Rejected: they are translations, not the local name.

## R3. Area level → admin rank mapping, in `packages/core`

**Decision**:

| Area | Source | Fallback |
|------|--------|----------|
| `national` | boundary at `admin_level` 2 | — |
| `state` | `admin_level` 4 | — |
| `province` | `admin_level` 6 | — |
| `region` | `admin_level` 7 | — |
| `municipality` | `admin_level` 8 | — |
| `neighborhood` | `admin_level` 10 | nearest neighbourhood point within 1000 m |
| `international` | rejected (422) | — |

No boundary at the rank is "no place at the chosen level" (FR-009).
The mapping lives in a domain value, `Domain/Place/PlaceLevel::forArea(Area)`,
because `Area` is already a core enum
and a query handler carries no rules (`application-queries-0003`).

**Rationale**: OSM ranks match the clarified Spanish hierarchy one to one.
`admin_level` 9 is a city district (Málaga's "Carretera de Cádiz"), not a neighbourhood.
Many cities map neighbourhoods as `place=*` points rather than level-10 areas,
so the fallback keeps `neighborhood` useful;
the nearest point within 1000 m (`ST_DistanceSphere`) replaces
the `locality` field Nominatim used to supply.
The UK's level 10 is the civil parish;
the rank rule (clarification Q2) fills `neighborhood` from it where one exists.

**Alternatives considered**: falling back to the nearest neighbouring rank.
Rejected by the spec's assumption: a missing rank fails the lookup.

## R4. Map tiles: a self-hosted PMTiles extract

**Decision**: Serve one [Protomaps](https://docs.protomaps.com) basemap extract,
`spain-uk.pmtiles`, covering Spain (with the Balearic and Canary Islands, Ceuta, and Melilla)
and the United Kingdom,
as a static file under `/tiles/` on the frontend's own origin:
nginx serves it locally from `var/map/tiles/`,
and in AWS a private map-data S3 bucket sits behind a `/tiles/*` behaviour
on the existing frontend CloudFront distribution.
The browser reads it with HTTP range requests.

The extract is cut with the `pmtiles` CLI from a dated Protomaps daily build,
`pmtiles extract https://build.protomaps.com/<YYYYMMDD>.pmtiles var/map/tiles/spain-uk.pmtiles --region=infrastructure/map/coverage.geojson --maxzoom=15`,
and `infrastructure/map/coverage.geojson` holds the coverage polygon.

**Rationale**:

- A PMTiles archive is a single static file, so it needs no tile server:
  S3 and CloudFront already serve the frontend this way (ADR-0016),
  and CloudFront passes range requests to S3 unchanged.
- The same origin as the app means no CORS, no environment variable,
  and no third-party request (FR-011):
  the URL is the relative path `/tiles/spain-uk.pmtiles` everywhere.
- `--region` fetches only the byte ranges for the coverage area,
  so cutting the extract never downloads the planet file.
- `--maxzoom=15` is street level, enough to pin a neighbourhood;
  the extract's size is measured when it is first cut (tasks) and recorded in ADR-0022.

**Known behaviour**: the frontend distribution maps every 403/404 to `/index.html`
for the SPA, and that applies to `/tiles/*` as well.
A missing archive therefore returns HTML; the map layer fails to parse it,
the map shows no basemap, and pinning still works (spec edge case: map unavailable).

**Alternatives considered**:

- *Raster tiles from a self-run tile server*: a running renderer,
  which is a separate service. Rejected.
- *Pre-rendered raster tiles in S3*: millions of objects for two countries at zoom 15,
  slow to upload and to invalidate. Rejected.
- *The public OSM tile servers*: third-party requests, ruled out by FR-011.

## R5. Map rendering: Leaflet with `protomaps-leaflet`

**Decision**: Keep Leaflet `1.9.4` and `react-leaflet` `5.0.0` behind `shared/map`
(`application-frontend-0001`),
and draw the basemap with `protomaps-leaflet`'s `leafletLayer({ url, flavor: 'light' })`,
which reads PMTiles directly.
Its exact version is the latest 5.x at install,
pinned in `apps/web/package.json` and recorded in ADR-0022.
The marker stays an `L.divIcon` holding a `<span role="img">`
named by an i18n string.

The initial view is centred on `[45, -4]` at zoom 4 in a 384 px tall map,
which shows latitudes 31.8 to 55.7,
keeping both spec points (Huelin and central Sheffield)
at least 32 px clear of the map's edges and controls,
and is exported from `shared/map` as a constant
so the Playwright journey can turn a latitude and longitude into a pixel to click.

**Rationale**: `protomaps-leaflet` draws vector tiles to a 2D canvas,
so the map needs no WebGL:
Playwright's container Chromium renders it without GPU flags,
which was R4's reason for choosing Leaflet in the first round.
Everything settled on top of Leaflet carries over unchanged:
the accessible `divIcon` marker, the `useMapEvents` click, the fly-to,
and mocking `shared/map` at its public API in Vitest.

**Alternatives considered**:

- *MapLibre GL JS*: Protomaps' primary renderer,
  but it needs WebGL, which is an E2E risk in containers,
  is heavier (~200 kB),
  and replaces the whole Leaflet adapter. Rejected.
- *OpenLayers*: large, imperative, and no maintained React binding. Rejected.

## R6. The point reaches the API in a POST body and is never logged

**Decision**: `POST /api/places/lookup` with
`{"lat": 36.7033, "lon": -4.4366, "area": "neighborhood"}`,
answered by `200 {"name": "Huelin"}` or `200 {"name": null}`,
behind the existing authenticated `^/api` firewall.

- The point is never in a URL,
  so neither nginx's access log nor any CloudFront log records it (FR-005).
- No logger call in the place slice receives the point,
  and validation failures carry translation keys, never values
  (`InvalidPlaceLookup`, 422).
- PostgreSQL logs bound parameter values only when a logging setting asks for them;
  neither the local `postgres` service
  nor the Aurora cluster (`database-stack.ts` sets no parameter group) does.
  The plan's audit task re-checks this.
- "No place" is a successful lookup with `name: null`, not a 404,
  so the client never confuses it with a missing route.

**Rationale**: This reverses the first round's R7 (browser-direct lookup).
R7 rejected an API proxy because a proxied `GET` puts `lat`/`lon` in logged URLs,
and the public service's rate limit argued against one shared origin.
A `POST` body removes the first concern, and the second no longer exists:
the lookup is the platform's own query.

**Alternatives considered**:

- *`GET /api/places?lat=…&lon=…`*: puts the point in logged URLs. Rejected.
- *404 for no place*: indistinguishable from an unknown route
  and filtered differently by the Monolog config (`excluded_http_codes`). Rejected.

## R7. Boundary import: a make target, a console command, a manual workflow

**Decision**:

1. **Extract**: `make map-data` runs in a new `map` tools container
   (`docker/map/Dockerfile`: `osmium-tool`, `jq`, a pinned `pmtiles` release),
   downloads the Geofabrik extracts for Spain, the Canary Islands, and the United Kingdom,
   merges them with `osmium merge`,
   keeps the boundary relations and `place=*` nodes with `osmium tags-filter`,
   and writes `var/map/places.geojsonl`, one GeoJSON feature per line,
   with `osmium export -f geojsonseq` and a `jq` filter
   that keeps the ranks in R3 and drops unnamed objects.
   It also cuts the tiles extract (R4).
2. **Import**: the console command `app:places:import <path-or-url>`
   dispatches `ImportPlacesCommand`.
   Its handler reads features through a `PlaceFeed` port
   (`domain-helpers-0002`; `apps/api` implements it over a local path or an HTTPS URL),
   turns each line into a domain `Boundary` or `Neighbourhood`,
   and hands them to `PlaceRepository::replaceAll()`,
   which deletes and re-inserts both tables in one transaction,
   so lookups see the old data until the commit.
   `make map-import` runs it against the development database.
3. **Publish**: a manually dispatched workflow, `.github/workflows/map-data.yml`,
   runs `make map-data`, uploads the tiles to the map-data bucket's `tiles/` prefix
   and the places file to its `places/` prefix,
   presigns the places object with `aws s3 presign`,
   and invokes the console Lambda with `app:places:import '<presigned URL>'`,
   the URL single-quoted because the console layer runs the command through a shell
   and the presigned query string contains `&`.

**Rationale**: Only the extraction needs OSM tooling,
and it runs in a container like every other tool (ADR-0002, ADR-0003).
The import is application code,
so it runs where migrations already run, the Bref console Lambda (ADR-0016),
and the presigned URL gives that Lambda read access without a new IAM grant or an AWS SDK.
Behat and Playwright load their fixture places through the same command
(`test-integration-0001`), so the import path is exercised by every suite run.
OSM data changes slowly, so publishing is on demand, not on every deploy.

**Known risk**: Geofabrik clips its extracts,
so a country relation that extends beyond the extract,
such as Spain's with its maritime boundary, may come out incomplete
and fail to assemble into a polygon.
Before merging, the published data is checked against the spec's own points
(US1 sc.1, sc.3, sc.4; quickstart);
if `admin_level` 2 is missing, the fix is to add the country relations
with `osmium getid` from a wider extract, not to change the lookup.
The import's run time against the console Lambda's 15-minute limit is measured on the first publish.

**Alternatives considered**:

- *`osm2pgsql` straight into Aurora*: needs network access to the cluster from the runner,
  and its output tables would need reshaping. Rejected.
- *Importing in a migration*: ties a data refresh to a schema version,
  and a migration cannot fetch a large file. Rejected.

## R8. Local PostGIS

**Decision**: A new `docker/postgres/Dockerfile`
`FROM public.ecr.aws/docker/library/postgres:17`
adding the Debian package `postgresql-17-postgis-3`
(the official image already carries the PGDG apt source),
built by `make images` and used by `docker-compose.yml`'s `postgres` service.
The first migration of this feature runs `CREATE EXTENSION IF NOT EXISTS postgis`.

**Rationale**: It keeps the ECR mirror the project already pulls from
and pins PostgreSQL 17 to match Aurora (`database-stack.ts`).
ADR-0009 names a plain PostgreSQL container,
so it is corrected in place to name PostGIS (Constitution III).

**Alternatives considered**: the Docker Hub `postgis/postgis:17-3.5` image.
Rejected: it leaves the ECR mirror,
which the project uses to avoid Docker Hub rate limits in CI.

## R9. Testing approach

**Decision**:

- **PHPSpec**: `GeoPoint` (range checks; the failure message is a key with no coordinates),
  `PlaceLevel::forArea` for all seven areas,
  `Boundary` / `Neighbourhood` construction from a feature (ranks kept, unnamed and unknown ranks rejected),
  and the `ImportPlacesCommand` and `LookUpPlaceQuery` handlers with their ports and providers doubled.
- **Behat**: `POST /api/places/lookup` against fixture places —
  every area level for Huelin, `national` and `municipality` for Sheffield,
  a point with no place, the neighbourhood fallback inside and beyond 1000 m,
  `422` for an out-of-range point and for `international` (a body with no coordinates),
  `401` signed out.
  This is the lowest layer that proves the SQL and the HTTP mapping.
- **Vitest**: the lookup client (POST body, URL free of coordinates, abort, 10 s timeout,
  failure mapping), `wrapLongitude`, `currentPosition`,
  and the picker and form behaviours with `shared/map` mocked, unchanged from the first round.
- **Playwright**: one happy-path journey per story,
  against the real API and the fixture places from the DSLR snapshot
  (no geocoder stub any more); only `/tiles/**` is stubbed, so no archive is needed in CI.

Fixture places are synthetic squares nested around the spec's two points,
named with the real place names (`apps/api/features/fixtures/places.geojsonl`),
loaded by `fixtures.feature` through the import command,
so Behat and Playwright share them through the snapshot.
They are wide enough (Huelin ±0.2°) for a click computed at the initial zoom to land inside.

**Rationale**: Constitution Principle II.
Range checks and the mapping are domain facts (PHPSpec);
the SQL, status codes, and payload shape are HTTP facts (Behat);
UI states stay in Vitest; each story's happy path runs end to end.
The real OSM data is checked by hand after publishing (R7),
because no suite should download it.

## R10. Architecture decision records

**Decision**: Propose `ADR-0022: Self-host map tiles and place lookup`
(Leaflet + `protomaps-leaflet`, a PMTiles extract on S3/CloudFront,
PostGIS boundaries in Aurora, the import workflow, Spain and UK coverage),
written with the `adr-skill` as the first task.
In the same change, correct ADR-0009 in place (PostGIS-enabled PostgreSQL container and extension)
and ADR-0016 in place (map-data bucket and `/tiles/*` behaviour),
as Constitution III requires.

**Rationale**: A new UI library, a database extension, a new bucket,
and a data pipeline are structural decisions, as `react-router` was (ADR-0019).
