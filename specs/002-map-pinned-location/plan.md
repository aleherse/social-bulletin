# Implementation Plan: Pin a Movement's Location on a Map

**Branch**: `002-map-pinned-location` | **Date**: 2026-10-16 |
**Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/002-map-pinned-location/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command.
See `.specify/templates/plan-template.md` for the execution workflow.

## Summary

The movement form gains a map next to the Location field.
Clicking the map, or choosing "use my location",
places a single marker and looks up the place at that point;
the Location field is filled with the local official name
at the rank the area level names
(`national` › `state` › `province` › `region` › `municipality` › `neighborhood`).

Everything is self-hosted, with no separate service (spec session 2026-10-16, FR-011).
The basemap is a Protomaps PMTiles extract for Spain and the United Kingdom,
served as a static file from the frontend's own origin
(nginx locally, S3 behind the existing CloudFront in AWS)
and drawn by Leaflet with `protomaps-leaflet`.
The lookup is `POST /api/places/lookup`,
a `packages/core` query answered by PostGIS point-in-polygon
over OpenStreetMap boundaries imported into the existing PostgreSQL / Aurora database.
The point travels in the request body, is never stored, and is never logged (FR-005).
The movement itself is unchanged: only the name is saved (FR-006).

## Technical Context

**Language/Version**: PHP 8.5 (`packages/core`, `apps/api`);
TypeScript 6 + React 19 (`apps/web`);
TypeScript CDK (`infrastructure`)

**Primary Dependencies**:
New frontend: `leaflet@1.9.4`, `react-leaflet@5.0.0`, `@types/leaflet` (dev),
`protomaps-leaflet` (latest 5.x, pinned at install) — behind `shared/map` (`application-frontend-0001`).
New database: the PostGIS extension (`postgresql-17-postgis-3` locally; built into Aurora PostgreSQL 17).
New tooling, in a `map` container only: `osmium-tool`, `jq`, and a pinned `pmtiles` CLI release.
No new PHP package: DBAL already runs the PostGIS SQL.
`@tanstack/react-query` is **not** used for lookups
(one-shot, abortable, user-triggered calls; see data-model transitions).

**Storage**: Two new reference tables,
`bulletin.place_boundaries` and `bulletin.place_neighbourhoods` (data-model.md),
replaced wholesale by each import; `bulletin.movements` unchanged

**Testing**: PHPSpec for the place domain and handlers;
Behat for the lookup endpoint over fixture places;
Vitest + Testing Library for `apps/web`;
Playwright for one happy-path journey per user story against the real API

**Target Platform**: Browsers served by nginx locally and CloudFront in AWS;
API on Bref Lambda, database on Aurora Serverless v2 (ADR-0004, ADR-0016)

**Project Type**: Web application (monorepo: `packages/core`, `apps/api`, `apps/web`, `infrastructure`, `docker`)

**Performance Goals**: Place name filled within 3 s of a pin for 95% of pins (SC-001),
from one GiST-indexed query per pin;
a lookup still unanswered after 10 s is reported as failed (FR-009);
Leaflet and `protomaps-leaflet` load only in the lazy new/edit movement chunks (ADR-0019)

**Constraints**:
No third-party request at runtime (FR-011);
the point only in a POST body, never in a URL, a log line, an error, or a table (FR-005);
one lookup per explicit pin, superseded requests aborted, none on typing or area change;
a map that fails to render is contained by an error boundary,
leaving the Location field and "use my location" working (spec edge case);
OSM and Protomaps attribution always visible (ODbL);
the import fits the console Lambda's 15-minute limit (measured on first publish);
FSD import rules and `shared/*` adapters; hexagonal boundaries checked by deptrac

**Scale/Scope**: Coverage Spain and the UK.
`packages/core`: one new `Place` area (5 domain classes, 1 port, 1 repository, 1 provider, 1 model, 1 query, 1 command).
`apps/api`: 1 controller, 1 console command, 1 port adapter, 1 migration, the listener extended to one more error.
`apps/web`: 2 `shared` slices (`map`, `geo`), 1 `ErrorBoundary`, 1 lookup client in `entities/movement`, the picker in `features/propose-movement`, 8 i18n strings.
Ops: 2 Dockerfiles, 2 make targets, 1 manual workflow, 1 CDK bucket and behaviour

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **I. Tests first-class**: every layer this feature touches is listed below
      with the tool that will cover it; no layer is left implicit
- [x] **II. Layer/tool mapping**: `packages/core` → PHPSpec, `apps/api` → Behat,
      `apps/web` units → Vitest, user journeys → Playwright
- [x] **II. Pyramid**: every user story has a Playwright journey for its **happy
      path**; error, edge, and validation cases are planned at the lowest layer
      that can prove them and are not repeated higher up
- [x] **III. Decisions and rules written down**: relevant ADRs consulted;
      diverging from one corrects it in the same change; applicable `docs/rules/` files read
- [x] **IV. Automated gates**: work is runnable through `make` targets; test data
      comes from the DSLR `fixtures` snapshot and no test run recreates it

Architecture boundaries (hexagonal layering, deptrac, FSD imports) are not a
constitutional gate; they are governed by ADR-0005/ADR-0007 and enforced by
`make lint`. Gate III covers consulting them.

**Layers touched by this feature**:

| Layer | Touched? | Test tool | Where |
|-------|----------|-----------|-------|
| `packages/core` | yes | PHPSpec | `packages/core/spec/Domain/Place/`, `packages/core/spec/Application/Place/` |
| `apps/api` | yes | Behat | `apps/api/features/places.feature` |
| `apps/web` | yes | Vitest | next to the source in `shared/geo`, `entities/movement`, `features/propose-movement` |
| User journeys | yes | Playwright | `apps/web/e2e/movement-location.spec.ts` |

No layer is waived.
`infrastructure`, `docker`, and the workflow are not test layers under ADR-0013;
they are checked by `make infra-tsc`, `make images`, `make ci` building the PostGIS image,
and the first publish (quickstart "Checking published data").

**Gate notes**:

- **II. Pyramid**
  - PHPSpec: `GeoPoint` ranges and coordinate-free messages,
    `PlaceLevel::forArea` for all seven areas,
    feature parsing (ranks, unnamed, wrong geometry, line numbers),
    handlers with ports and providers doubled.
  - Behat: the SQL and the HTTP mapping —
    a Scenario Outline over the expected-lookups table in contracts/place-lookup-api.md,
    the neighbourhood fallback inside and beyond 1000 m,
    422 for an out-of-range point and for `international` with no coordinates echoed,
    401 signed out,
    and `app:places:import` replacing the data or rolling back on a malformed line.
    The import's HTTPS source is waived in tasks.md (no suite reaches the network)
    and exercised by the first `preview` publish.
  - Vitest: lookup client (body, coordinate-free URL, abort, timeout, failure mapping),
    longitude wrap, position errors, not-found and unavailable messages,
    typing during a pending lookup, no refill on area change,
    map hidden for International, a map that fails to render,
    edit form opening without a marker.
  - Playwright: US1 pin-by-click → "Huelin" → saved name listed;
    US2 use-my-location with granted geolocation → "Málaga" → saved.
    Both hit the real API and the snapshot's fixture places;
    only `/tiles/**` is stubbed.
- **III. Decisions** — Consulted ADR-0000 (`bulletin` schema), ADR-0002 (tools in containers),
  ADR-0003 (make entrypoints), ADR-0004 (nginx serves the bundle and now `/tiles/`),
  ADR-0005 (core/api split), ADR-0007 (FSD), ADR-0008 (i18n), ADR-0009 (PostgreSQL/DBAL),
  ADR-0010 (shadcn `Button`), ADR-0012 (deptrac owns the layer rules), ADR-0013 (tools),
  ADR-0015 (`make ci`), ADR-0016 (AWS),
  ADR-0019 (lazy routes), ADR-0021 (CQRS).
  New: propose **ADR-0022** (self-hosted tiles and place lookup) as the first task.
  Corrected in place in the same change: **ADR-0009** (PostGIS-enabled container and extension)
  and **ADR-0016** (map-data bucket, `/tiles/*` behaviour).
  Rules read: `application-frontend-*`, `application-framework-*`, `application-queries-*`,
  `application-commands-*`, `application-helpers-*`, `application-style-0001`,
  `database-persistence-*`, `database-dbal-*`, `domain-helpers-*`, `domain-common-0001`,
  `test-integration-0001`, `test-unit-0001`, `scaffolding-folders-0001`.
  Notable applications: the import port lives in `Domain/Helper/` (`domain-helpers-0002`);
  `LookUpPlaceQuery::fromPayload` follows `application-framework-0002`'s shape for a query;
  Behat loads places through `CommandBus` (`test-integration-0001`).
- **IV. Gates** — New make targets `map-data` and `map-import` (ADR-0003);
  `make images` builds the PostGIS and `map` images;
  `make db` loads the fixture places through `fixtures.feature`,
  so the DSLR snapshot carries them and no suite recreates them;
  `make ci` (ADR-0015) is the merge gate.

**Post-design re-check (after Phase 1)**: PASS.
The new tables hold only OSM reference data, no author data (data-model.md);
the contracts name every request field, status, and accessible element the tests use;
every layer has test tasks planned, and Complexity Tracking stays empty.

## Project Structure

### Documentation (this feature)

```text
specs/002-map-pinned-location/
├── plan.md              # This file
├── research.md          # Phase 0: lookup, names, ranks, tiles, renderer, logging, import, PostGIS, testing
├── data-model.md        # Phase 1: place tables, core values, client values, picker state
├── quickstart.md        # Phase 1: map data, try it, check published data, run the suites
├── contracts/
│   ├── place-lookup-api.md    # POST /api/places/lookup + fixture places
│   └── location-picker-ui.md  # Accessible names and messages tests assert on
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 (/speckit-tasks — not created here)
```

### Source Code (repository root)

```text
docs/decisions/
├── ADR-0009-adopt-postgresql-and-doctrine-dbal.md   # corrected: PostGIS
├── ADR-0016-aws-serverless-deployment.md            # corrected: map-data bucket, /tiles/*
└── ADR-0022-self-host-map-tiles-and-place-lookup.md # new, status Proposed

db/schema.dbml                                       # + place_boundaries, place_neighbourhoods
deptrac.yaml                                         # + DomainPort layer; Framework may implement it

docker/
├── postgres/Dockerfile                              # new: postgres:17 + postgresql-17-postgis-3
├── map/Dockerfile                                   # new: osmium-tool, jq, pmtiles
└── nginx/conf.d/web.conf                            # + location /tiles/ → /app/var/map/tiles/
docker-compose.yml                                   # postgres builds docker/postgres; map service (tools profile)
Makefile                                             # + map-data, map-import

infrastructure/
├── map/coverage.geojson                             # new: Spain + UK coverage polygon
└── lib/frontend-stack.ts                            # + MapDataBucket, /tiles/* behaviour, bucket output

.github/workflows/map-data.yml                       # new: manual build → upload → presign → import

packages/core/
├── src/Domain/Helper/PlaceFeed.php                  # port
├── src/Domain/Place/
│   ├── GeoPoint.php
│   ├── PlaceLevel.php
│   ├── Boundary.php
│   ├── Neighbourhood.php
│   ├── InvalidPlaceLookup.php                       # DomainError, 422
│   ├── InvalidPlaceFeed.php
│   └── PlaceRepository.php                          # replaceAll()
├── src/Application/Place/
│   ├── Model/Place.php
│   ├── Provider/PlaceProvider.php                   # nameAt()
│   ├── Query/LookUpPlaceQuery.php, LookUpPlaceHandler.php
│   └── Command/ImportPlacesCommand.php, ImportPlacesHandler.php
└── spec/                                            # mirrors src (test-unit-0001)

apps/api/
├── migrations/Version<timestamp>.php                # postgis extension + both tables
├── config/services.yaml                             # alias PlaceFeed → GeoJsonSeqPlaceFeed
├── src/Controller/Place/LookUpPlaceController.php   # POST /api/places/lookup
├── src/Console/ImportPlacesConsoleCommand.php       # app:places:import <path-or-url>
├── src/Place/GeoJsonSeqPlaceFeed.php                # PlaceFeed adapter
├── src/EventListener/DomainErrorListener.php        # + InvalidPlaceLookup errors
├── features/places.feature                          # new
├── features/fixtures.feature                        # + import the fixture places
├── features/fixtures/places.geojsonl                # contracts/place-lookup-api.md
└── tests/Behat/PlaceContext.php                     # imports through CommandBus

apps/web/
├── package.json, package-lock.json                  # + leaflet, react-leaflet, protomaps-leaflet, @types/leaflet
├── src/
│   ├── shared/
│   │   ├── ui/error-boundary.tsx, index.ts          # + ErrorBoundary
│   │   ├── geo/                                     # no third-party imports
│   │   │   ├── index.ts
│   │   │   ├── model/types.ts                       # GeoPoint
│   │   │   ├── lib/wrap-longitude.ts (+ .test.ts)
│   │   │   └── api/current-position.ts (+ .test.ts)
│   │   ├── map/                                     # the only Leaflet / protomaps-leaflet importer
│   │   │   ├── index.ts
│   │   │   ├── model/view.ts                        # INITIAL_VIEW, TILES_URL
│   │   │   └── ui/location-map.tsx
│   │   └── i18n/locales/en/common.json              # + movements.form.map.*
│   ├── entities/movement/
│   │   ├── index.ts                                 # + lookUpPlaceName, PlaceLookupUnavailable
│   │   └── api/look-up-place-name.ts (+ .test.ts)
│   └── features/propose-movement/
│       ├── ui/movement-form.tsx (+ .test.tsx)
│       ├── ui/location-picker.tsx (+ .test.tsx)
│       └── model/use-place-lookup.ts
└── e2e/
    ├── fixtures.ts                                  # + stubMapTiles auto fixture
    ├── movements.spec.ts                            # signIn moved; exact Location locator
    ├── movement-location.spec.ts                    # US1 + US2 journeys
    └── support/session.ts, support/map.ts           # signIn; lat/lon → pixel at INITIAL_VIEW
```

**Structure Decision**:
`packages/core` gains a `Place` area beside `Movement` and `User`:
the domain values and the import port in `Domain`,
the query, command, provider, and model in `Application` (ADR-0021).
`PlaceLevel` depends on `Domain\Movement\Area`,
a same-layer domain dependency deptrac already allows.
`PlaceFeed` is the first `Domain/Helper` port (`domain-helpers-0002`),
and today's `deptrac.yaml` lets `Framework` depend only on `Application` and `DomainError`,
so the `apps/api` adapter would fail `make lint`.
`deptrac.yaml` therefore gains a `DomainPort` layer
(interfaces under `packages/core/src/Domain/Helper/` other than `DomainError`)
that `Framework`, `Application`, and `Domain` may depend on —
the layer the rule already assumes, not a new exception (ADR-0012 owns the ruleset).
Validation messages are keys (`place.point.out_of_range`, `place.area.unsupported`),
rendered the same way as the existing `movement.*` keys.
`apps/api` adds one controller in a per-aggregate folder (`scaffolding-folders-0001`),
the console command, and the `PlaceFeed` adapter.
In `apps/web`, the lookup client takes an `Area`,
so it belongs to `entities/movement` rather than a new entity
(a sibling entity could not import `Area` without `@x`);
`shared/geo` keeps the movement-agnostic point, wrap, and position;
`shared/map` stays the only Leaflet importer;
the picker stays in `features/propose-movement`.
`pages/movements` needs no change.

## Complexity Tracking

No constitution violations to justify — table intentionally empty.
