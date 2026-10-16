# Research: Pin a Movement's Location on a Map

Phase 0 output for [plan.md](plan.md).
Each entry records the decision, why it was chosen, and what was rejected.

Revised 2026-10-16 for the self-hosting clarifications (spec session 2026-10-16, FR-011).
The first round chose public OSM Nominatim and OSM raster tiles called from the browser;
R1, R5, R6, and R7 of that round are superseded by R1, R4, R5, and R6 below.

Revised again 2026-10-16 (spec FR-011 revised, FR-012 added):
the tiles go back to OpenStreetMap's public tile servers (R4, R5),
while the lookup stays self-hosted;
the boundary build fetches Spain and Ceuta from Overpass and fails without them (R7).
The self-hosted PMTiles decision this replaces was implemented and measured (6.2 GB archive)
before being dropped; its code is removed (plan, Source Code).

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

## R4. Map tiles: OpenStreetMap's public raster tiles

**Decision**: Draw the basemap from `https://tile.openstreetmap.org/{z}/{x}/{y}.png`,
requested by the browser through Leaflet's `L.tileLayer`, with `maxZoom: 19`
and the attribution "© OpenStreetMap contributors" linking to the OSM copyright page.
No tiles are built, stored, or served by the platform.

**Rationale**:

- The spec now names this source (FR-011, revised 2026-10-16).
- It removes the heaviest part of the self-hosted design:
  a 6.2 GB archive per build, a bucket behaviour, an nginx location,
  and the `pmtiles` CLI and Protomaps build pin.
- Tiles cover the whole world, so the map is no longer blank outside Spain and the UK;
  place names keep their own coverage (R1).
- OSM's [tile usage policy](https://operations.osmfoundation.org/policies/tiles/)
  asks for visible attribution, a valid `Referer` (browsers send one;
  the platform sets no `Referrer-Policy` that would strip it), no bulk or offline download,
  and moderate traffic. An MVP's authors pinning drafts stays well inside it.

**Known consequence**: after a pin the map flies to the point at zoom 14,
so the tiles requested from OSM show the area around the pinned point,
to about a kilometre.
The point itself is never sent to OSM, and FR-005 still holds for the platform;
the spec's FR-011 records that OSM receives the map view.

**Known behaviour**: if OSM's servers are unreachable, the tiles fail to load,
the map shows no basemap, and pinning still works (spec edge case: basemap unavailable).

**Alternatives considered**:

- *The self-hosted PMTiles extract* (the previous R4): implemented, then dropped by the spec revision.
- *A commercial tile provider* (MapTiler, Stadia): an API key and a billing account for an MVP. Deferred:
  it is the move if traffic outgrows OSM's policy (spec Assumptions).

## R5. Map rendering: Leaflet's own tile layer

**Decision**: Keep Leaflet `1.9.4` and `react-leaflet` `5.0.0` behind `shared/map`
(`application-frontend-0001`),
and replace `protomaps-leaflet`'s layer with `react-leaflet`'s `<TileLayer url={TILES_URL} attribution={…} maxZoom={19} />`.
`TILES_URL` becomes the OSM template above.
`protomaps-leaflet` is uninstalled.
The marker stays an `L.divIcon` holding a `<span role="img">`
named by an i18n string,
and `INITIAL_VIEW` (`[45, -4]`, zoom 4) is unchanged,
so the Playwright click maths in `e2e/support/map.ts` still holds.

**Rationale**: Raster tiles are `<img>` elements, the simplest thing Leaflet draws;
no canvas, no WebGL, nothing to configure.
Everything else in `shared/map` carries over unchanged,
and Vitest keeps mocking `shared/map` at its public API.

**Alternatives considered**: keeping `protomaps-leaflet` against a vector tile host.
Rejected: OSM's public servers are raster.

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
   (`docker/map/Dockerfile`: `osmium-tool`, `jq`, `curl`),
   downloads the Geofabrik extracts for Spain, the Canary Islands, and the United Kingdom,
   fetches the complete Spain and Ceuta boundaries from Overpass (below),
   merges all four with `osmium merge`,
   keeps the boundary relations and `place=*` nodes with `osmium tags-filter`,
   and writes `var/map/places.geojsonl`, one GeoJSON feature per line,
   with `osmium export -f geojsonseq` and a `jq` filter
   that keeps the ranks in R3 and drops unnamed objects.
   It then runs the guard (below) and exits non-zero if it fails.
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
   runs `make map-data` (so a failed guard stops the publish before anything is uploaded),
   uploads the places file to the map-data bucket's `places/` prefix,
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

**The clipped boundaries (FR-012)**: Geofabrik clips its extracts,
and the first real build (ADR-0022, measured 2026-10-16) lost two relations
whose members reach outside the Spain extract:
Spain's country boundary (`admin_level` 2, its maritime line)
and Ceuta's (`admin_level` 4, on the African coast).
Without them Huelin answered `null` at `national`, and Ceuta had no `state`.

- **Fetch**: `docker/map/overpass-boundaries.ql` asks Overpass for both relations by their codes,
  with every member way and node, in OSM XML with versions (`out meta`):

  ```text
  [out:xml][timeout:600];
  (
    relation["boundary"="administrative"]["admin_level"="2"]["ISO3166-1"="ES"];
    relation["boundary"="administrative"]["ISO3166-2"="ES-CE"];
  );
  (._; >;);
  out meta;
  ```

  The script POSTs it to `https://overpass-api.de/api/interpreter`
  with `curl --fail --retry 3 --retry-all-errors`
  and a `User-Agent` naming the project, as Overpass asks,
  and converts the reply with `osmium cat` to `overpass.osm.pbf`.
  `osmium merge` keeps one copy of any object with the same id and version,
  so the complete relations and their members simply fill in what the extracts clipped.
  Codes rather than relation ids keep the query readable and survive a relation being remapped.
- **Guard**: after the `jq` filter, the script requires
  a boundary named "España" at `admin_level` 2
  and boundaries named "Ceuta", "Melilla", and "Illes Balears" at `admin_level` 4
  in `places.geojsonl`
  (`jq -e` over the boundaries, one check each),
  printing which is missing and exiting `1` otherwise.
  The `jq` filter already drops boundaries that did not assemble into a polygon,
  so a present row is a usable one.
  An Overpass outage ends the build the same way: `curl --fail` exits non-zero under `set -eu`.
  Only Spain and Ceuta are fetched from Overpass;
  Melilla and the Balearic Islands are guarded too, because they were complete in the first build
  and a future Geofabrik change should fail the build rather than ship without them.
- **Not guarded**: the Canary Islands, covered by the by-hand check on `preview`
  (quickstart, US1 sc.10).
  Offshore points and the *plazas de soberanía* are not guaranteed (spec FR-012).

**Alternatives considered for the clipped boundaries**:

- *`osmium getid` from a wider extract (Europe, Africa)*:
  tens of gigabytes downloaded to recover two relations. Rejected.
- *Pinning relation ids*: unreadable, and silently wrong if a relation is replaced. Rejected.
- *Committing the two boundaries to the repository*: a large file that goes stale
  and hides the problem instead of failing on it. Rejected.
- *A sample of probe points (Palma, Las Palmas, Ceuta, Melilla) resolved by the build*:
  needs PostGIS in the build. Rejected for the MVP; the by-hand check covers it.

The import's run time against the console Lambda's 15-minute limit was measured locally (29 s, ADR-0022).

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
  (no geocoder stub any more); only `https://tile.openstreetmap.org/**` is aborted,
  so no suite reaches OSM.
- **FR-012 in Behat**: the fixture España is a MultiPolygon with mainland, Balearic, Ceuta, and Melilla parts,
  and Illes Balears, Ceuta, and Melilla are level-4 boxes,
  so `places.feature` proves `national` and `state` for Palma, Ceuta, and Melilla
  (contracts/place-lookup-api.md).
  That proves the lookup over a multi-part country;
  that the real data contains those boundaries is proven by the build guard (R7)
  and the by-hand check on `preview`.

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

**Decision**: Correct `ADR-0022` in place (it is still Proposed)
and rename it `ADR-0022-use-osm-tiles-and-self-hosted-place-lookup.md`,
updating its links from ADR-0009, ADR-0016, and this feature's documents:
OSM raster tiles through `L.tileLayer` replace the PMTiles archive and `protomaps-leaflet`;
the map data pipeline gains the Overpass fetch and the guard;
the Verification list swaps the tiles range-request check for the guard
and the FR-012 points;
the "Patterns to avoid" entry on absolute tile URLs is narrowed to geocoder hosts.
In the same change, correct ADR-0016 in place (the map-data bucket holds places only, with no `/tiles/*` behaviour);
ADR-0009 (PostGIS) is unchanged.

**Rationale**: Constitution III corrects an ADR in place rather than superseding it,
and a Proposed ADR whose title names a dropped decision would mislead its next reader.
