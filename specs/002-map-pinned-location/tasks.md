---

description: "Task list for Pin a Movement's Location on a Map"
---

# Tasks: Pin a Movement's Location on a Map

**Input**: Design documents from `/specs/002-map-pinned-location/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/](contracts/), [quickstart.md](quickstart.md)

**Tests**: MANDATORY (Constitution Principle I).
This feature touches all four layers:
`packages/core` (PHPSpec), `apps/api` (Behat), `apps/web` (Vitest), and user journeys (Playwright).
No layer is waived.
Test tasks come before the implementation they cover
and MUST be observed failing first.
`infrastructure`, `docker`, and `.github/workflows` are not test layers under ADR-0013;
Phase 5 says how each of their tasks is verified.

**Organization**: Tasks are grouped by user story;
each story is independently implementable and testable.
The place lookup (core, API, client) is Foundational,
because both stories end in the same lookup.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story the task belongs to (US1, US2)
- All paths are relative to the repository root

## Conventions for every task

- Before writing code in an area, read its rules:
  PHP in `packages/core` → `docs/rules/domain/*.md`, `docs/rules/application/*.md`, `docs/rules/database/*.md`;
  `apps/api` → `docs/rules/application/framework.md`, `docs/rules/scaffolding/folders.md`;
  Behat → `docs/rules/test/integration.md`; PHPSpec → `docs/rules/test/unit.md`;
  TypeScript → `docs/rules/application/frontend.md` and `docs/engineering/frontend/fsd.md`.
  Cite a rule's identifier when a commit leans on it.
- PHP classes are `final readonly` unless a spec must double them
  (`application-style-0001`; providers and repositories stay plain `class`, like `CategoryProvider`).
- Third-party frontend imports (`leaflet`, `react-leaflet`, `react-i18next`, `react-router`)
  only inside their `shared/*` adapter slice (`application-frontend-0001`);
  `shared/map` is the only slice that imports `leaflet` or `react-leaflet`
  (`protomaps-leaflet` is removed by T050).
- User-visible strings come from i18n keys in
  `apps/web/src/shared/i18n/locales/en/common.json`;
  names and texts are fixed by [contracts/location-picker-ui.md](contracts/location-picker-ui.md).
- Tests query by role, label, or visible text only (Principle II).
  In Playwright, the Location field is always
  `page.getByRole('textbox', { name: 'Location', exact: true })`.
- The pinned point MUST NOT appear in a URL, a log call, an exception message, or a table (FR-005).
- Comments only where code cannot carry the meaning (Principle V).
- Run commands through `make`
  (`make php-unit`, `make api-tests`, `make web-unit`, `make web-e2e`, `make lint`, `make ci`,
  `make console cmd="…"`, `make shell service=node` for npm).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Record the decisions, add dependencies and PostGIS,
fix the shared E2E helpers, and prepare the shared test data and strings.

- [X] T001 Write `docs/decisions/ADR-0024-self-host-map-tiles-and-place-lookup.md`
      with the `adr-skill`, status Proposed, following ADR-0019's section layout,
      from research.md R1–R10:
      Leaflet 1.9.4 + react-leaflet 5.0.0 + `protomaps-leaflet` behind `shared/map`;
      a Protomaps PMTiles extract for Spain and the UK at `/tiles/spain-uk.pmtiles` on the frontend origin;
      OSM boundaries in PostGIS answered by `POST /api/places/lookup`;
      the point only in the body, never stored or logged;
      the `map-data` pipeline and its manual workflow;
      non-goals: storing coordinates, forward search, coverage beyond Spain and the UK.
      In the same change, correct in place
      `docs/decisions/ADR-0009-adopt-postgresql-and-doctrine-dbal.md`
      (the container is PostgreSQL 17 with PostGIS; the `postgis` extension is enabled by migration)
      and `docs/decisions/ADR-0016-aws-serverless-deployment.md`
      (a private map-data bucket behind a `/tiles/*` behaviour on the frontend distribution)
      (Constitution III)
- [X] T002 Add `leaflet@1.9.4`, `react-leaflet@5.0.0`, and the latest 5.x `protomaps-leaflet`
      (exact version, no range) to dependencies
      and `@types/leaflet` to devDependencies in `apps/web/package.json`
      by running `npm install` inside `make shell service=node`,
      so `apps/web/package-lock.json` is updated too;
      record the `protomaps-leaflet` version in ADR-0024
- [X] T003 Create `docker/postgres/Dockerfile`
      (`FROM public.ecr.aws/docker/library/postgres:17`,
      `RUN apt-get update && apt-get install -y --no-install-recommends postgresql-17-postgis-3 && rm -rf /var/lib/apt/lists/*`)
      and change the `postgres` service in `docker-compose.yml` from `image:` to `build: docker/postgres`;
      if `docker-compose.ci.yml` lists built services for its layer cache, add `postgres` there;
      verify with `make images`, `make up`, and
      `make console cmd="dbal:run-sql 'CREATE EXTENSION IF NOT EXISTS postgis'"` succeeding
- [X] T004 [P] Add a `DomainPort` layer to `deptrac.yaml`:
      a `bool` collector for classes under `packages/core/src/Domain/Helper/`
      that must be an interface and must not be the `DomainError` layer,
      excluded from the `Domain` layer's collector;
      allow `Framework`, `Application`, and `Domain` to depend on it
      and let it depend on what `Domain` may;
      add a header comment line naming `domain-helpers-0002` as the reason;
      `make php-deptrac` stays green
- [X] T005 [P] Move `signIn` out of `apps/web/e2e/movements.spec.ts`
      into `apps/web/e2e/support/session.ts` and import it from there;
      in the same file, replace every `page.getByLabel('Location')`
      with `page.getByRole('textbox', { name: 'Location', exact: true })`,
      because the map region ("Location map") and the marker ("Pinned location")
      would otherwise also match Playwright's substring, case-insensitive label lookup
      and fail the journeys with a strict-mode violation;
      a pure refactor, confirmed by the existing journeys passing in `make web-e2e`
- [X] T006 [P] Add the keys
      `movements.form.map.label` ("Location map"),
      `movements.form.map.marker` ("Pinned location"),
      `movements.form.map.useMyLocation` ("Use my location"),
      `movements.form.map.lookingUp` ("Looking up the place…"),
      `movements.form.map.positionDenied`,
      `movements.form.map.positionUnavailable`,
      `movements.form.map.placeNotFound`,
      `movements.form.map.lookupFailed`
      with the English texts from the Messages table of
      `specs/002-map-pinned-location/contracts/location-picker-ui.md`
      to `apps/web/src/shared/i18n/locales/en/common.json`
- [X] T007 [P] Create `apps/api/features/fixtures/places.geojsonl`:
      one GeoJSON `Feature` per line, no trailing blank line,
      for each row of the "Fixture places" table in
      `specs/002-map-pinned-location/contracts/place-lookup-api.md`.
      Boundaries: `properties` `{ "osm_id": <id>, "kind": "boundary", "admin_level": <level>, "name": "<name>" }`
      and a `Polygon` geometry whose single closed ring is the box's five corners
      `[west,south] [east,south] [east,north] [west,north] [west,south]`.
      The neighbourhood: `{ "osm_id": 21, "kind": "neighbourhood", "name": "Kelham Island" }`
      and a `Point` geometry `[-1.4701, 53.3895]` (GeoJSON order is longitude, latitude).
      Also create `apps/api/features/fixtures/places-reimport.geojsonl`
      for T013(f), in the same shape, with one line only:
      a `boundary` with `osm_id` 31, `admin_level` 8, name "Sheffield City",
      and the box `[-1.9701, 52.8811, -0.9701, 53.8811]`;
      and `apps/api/features/fixtures/places-malformed.geojsonl` for T013(g):
      that same line, then a second line reading `{not json`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The place lookup end to end below the UI —
core domain and use cases, schema, import, endpoint, fixture data in the snapshot,
the frontend lookup client — plus the movement-agnostic `shared/geo` values
and the E2E map helpers. Both stories need all of them.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

### Tests (write first, must fail) ⚠️

- [X] T008 [P] Write `packages/core/spec/Domain/Place/GeoPointSpec.php`
      for `GeoPoint::fromDegrees(float $latitude, float $longitude)`:
      `(36.7033, -4.4366)` exposes `latitude` 36.7033 and `longitude` -4.4366;
      the bounds `(-90, -180)` and `(90, 180)` are accepted;
      `(90.0001, 0)` throws `InvalidPlaceLookup` with `errors` `['lat' => 'place.point.out_of_range']`;
      `(0, -180.5)` throws it with `['lon' => 'place.point.out_of_range']`;
      in both, `getMessage()` is `place.lookup.invalid` and contains no digit (FR-005)
- [X] T009 [P] Write `packages/core/spec/Domain/Place/PlaceLevelSpec.php`
      for `PlaceLevel::forArea(Area $area)`:
      `adminLevel()` is 2 / 4 / 6 / 7 / 8 / 10 for
      `National` / `State` / `Province` / `Region` / `Municipality` / `Neighborhood`;
      `usesNeighbourhoodFallback()` is true for `Neighborhood` only;
      `International` throws `InvalidPlaceLookup` with `['area' => 'place.area.unsupported']`
- [X] T010 [P] Write `packages/core/spec/Domain/Place/PlaceFeatureSpec.php`
      for `PlaceFeature::parse(int $line, array $feature): Boundary|Neighbourhood`:
      a `boundary` feature with `admin_level` 8 and a `Polygon`
      yields a `Boundary` with `osmId`, `adminLevel`, `name`,
      and `geometryJson` equal to `json_encode($feature['geometry'])`;
      a `MultiPolygon` boundary is accepted;
      a `neighbourhood` feature with a `Point` yields a `Neighbourhood`;
      each of these on line 3 throws `InvalidPlaceFeed` whose message contains "line 3":
      `admin_level` 9, a missing or empty `name`, a `boundary` with a `Point`,
      a `neighbourhood` with a `Polygon`, an unknown `kind`, a missing `osm_id`
- [X] T011 [P] Write `packages/core/spec/Application/Place/Command/ImportPlacesHandlerSpec.php`
      with `PlaceFeed` and `PlaceRepository` doubled:
      for `ImportPlacesCommand::fromSource('places.geojsonl')`
      the feed is asked for `features('places.geojsonl')`,
      and `replaceAll()` receives an iterable that yields
      the parsed `Boundary` then `Neighbourhood`, in feed order
      (assert with `Argument::that` consuming it via `iterator_to_array`);
      a malformed feature on line 2 makes consuming that iterable throw `InvalidPlaceFeed` naming line 2,
      so the repository's transaction rolls back
- [X] T012 [P] Write `packages/core/spec/Application/Place/Query/LookUpPlaceQuerySpec.php`
      and `packages/core/spec/Application/Place/Query/LookUpPlaceHandlerSpec.php`:
      `LookUpPlaceQuery::fromPayload(['lat' => 36.7033, 'lon' => -4.4366, 'area' => 'neighborhood'])`
      exposes the matching `GeoPoint` and `PlaceLevel`, and accepts integer degrees;
      a missing or non-numeric `lat` or `lon` throws `InvalidPlaceLookup` keyed by that field;
      a missing, unknown, or `international` `area` throws it keyed `area`;
      the handler, with `PlaceProvider` doubled,
      returns what `nameAt($point, $level)` returns: `new Place('Huelin')`, or `null`
- [X] T013 Write `apps/api/features/places.feature`
      ("Look up a place name", as a signed-in author),
      signing in with the existing `POST /api/session` step in each scenario:
      (a) a Scenario Outline over every row of the "Expected lookups" table in
      `specs/002-map-pinned-location/contracts/place-lookup-api.md`:
      `When I send a POST request to "/api/places/lookup" with body:`
      `{"lat": <lat>, "lon": <lon>, "area": "<area>"}`,
      `Then the response status code should be 200`,
      and `the JSON at "name" should equal "<name>"`,
      with the `null` rows in a second outline using `the JSON at "name" should be null`;
      (b) `{"lat": 91.123456, "lon": -4.4366, "area": "national"}` → 422,
      `the JSON at "errors[?source.pointer=='/lat'].detail | [0]" should not be empty`,
      and the new step `the response body should not contain "91.123456"`;
      (c) the same for `lon` 181.654321 and pointer `/lon`;
      (d) `area` `international` and `area` missing → 422 with pointer `/area`;
      (e) a request without signing in → 401;
      (f) "Importing places from the console replaces the lookup data":
      `When I run the console command "app:places:import" with the file "fixtures/places-reimport.geojsonl"`,
      `Then the console command should succeed`,
      then, signed in, a `municipality` lookup at central Sheffield (53.3811, -1.4701) returns "Sheffield City"
      and a `national` lookup at Huelin (36.7033, -4.4366) returns `null`,
      proving the command replaces both tables rather than appending;
      (g) the same command with "fixtures/places-malformed.geojsonl"
      → `Then the console command should fail mentioning "line 2"` (T020),
      and the `municipality` lookup at central Sheffield still returns "Sheffield"
      (the failed import rolled back).
      The fixture places otherwise come from the snapshot (T021), not from these scenarios
- [X] T014 [P] Write Vitest cases in `apps/web/src/shared/geo/lib/wrap-longitude.test.ts`
      for `wrapLongitude(lon)` returning a value in [-180, 180):
      `-4.4366` unchanged, `190` → `-170`, `-190` → `170`,
      `180` → `-180`, `535.5634` → `175.5634`
- [X] T015 [P] Write Vitest cases in `apps/web/src/entities/movement/api/look-up-place-name.test.ts`
      for `lookUpPlaceName(point, area, signal)`, stubbing `globalThis.fetch` with `vi.stubGlobal`:
      (a) one `POST` to a URL ending in `/api/places/lookup`,
      with `credentials: 'include'`, `Content-Type: application/json`,
      and a body that parses to exactly `{ lat: 36.7033, lon: -4.4366, area: 'neighborhood' }`;
      the URL contains neither `36.7033` nor `-4.4366` (FR-005);
      (b) an out-of-range `lon` (355.5634) is sent as -4.4366;
      (c) `200 {"name":"Huelin"}` resolves `'Huelin'`;
      (d) `200 {"name":null}` resolves `null`;
      (e) `503`, `422`, a network `TypeError`, and `200 {}` reject with `PlaceLookupUnavailable`;
      (f) an `AbortError` caused by the caller's `signal` propagates unchanged;
      (g) with `vi.useFakeTimers()`, a `fetch` that never settles
      rejects with `PlaceLookupUnavailable` once 10000 ms pass and aborts the request (FR-009)

### Implementation

- [X] T016 Create in `packages/core/src/Domain/Place/`:
      `GeoPoint.php`, `PlaceLevel.php`, `InvalidPlaceLookup.php`
      (`\DomainException implements DomainError`, `STATUS_CODE = 422`,
      public `errors` like `InvalidMovement`, message `place.lookup.invalid`),
      `Boundary.php`, `Neighbourhood.php`, `PlaceFeature.php`,
      and `InvalidPlaceFeed.php` (not a `DomainError`: it only reaches the console),
      per `specs/002-map-pinned-location/data-model.md`,
      so T008–T010 pass
- [X] T017 Generate a migration with `make console cmd="doctrine:migrations:generate"` (ADR-0009)
      and fill it in `apps/api/migrations/`:
      `up()` runs `CREATE EXTENSION IF NOT EXISTS postgis`
      and creates `bulletin.place_boundaries` and `bulletin.place_neighbourhoods`
      with the columns, `CHECK (admin_level IN (2, 4, 6, 7, 8, 10))`, `CHECK (name <> '')`,
      and GiST / b-tree indexes from `specs/002-map-pinned-location/data-model.md`;
      `down()` drops both tables and the extension.
      Document both tables in `db/schema.dbml` with the `dbml-database-docs` skill,
      noting they hold OSM reference data replaced by each import.
      Verify with `make db`
- [X] T018 Create `packages/core/src/Domain/Helper/PlaceFeed.php`
      (interface, `features(string $source): iterable<int, array<string, mixed>>`, keyed by line number),
      `packages/core/src/Domain/Place/PlaceRepository.php`
      (`replaceAll(iterable<Boundary|Neighbourhood> $places): void`:
      inside `Connection::transactional()`,
      `DELETE FROM` both tables,
      then one `INSERT` per place through one prepared statement per table, reused for every row —
      boundaries with `ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(:geometry), 4326))`,
      neighbourhoods with `ST_SetSRID(ST_GeomFromGeoJSON(:geometry), 4326)`),
      and `packages/core/src/Application/Place/Command/ImportPlacesCommand.php`
      (`fromSource(string $source)`, extends `BaseCommand`)
      with `ImportPlacesHandler.php`
      (a generator mapping each fed line through `PlaceFeature::parse()` into `replaceAll()`),
      so T011 passes (depends on T004, T016, T017)
- [X] T019 Create `packages/core/src/Application/Place/Model/Place.php`,
      `packages/core/src/Application/Place/Provider/PlaceProvider.php`
      (`nameAt(GeoPoint $point, PlaceLevel $level): ?Place`:
      the covering boundary at the level, smallest `ST_Area` first;
      for `usesNeighbourhoodFallback()` only and when none covers,
      the nearest neighbourhood by `geom <-> point` if `ST_DistanceSphere` ≤ 1000;
      each table read from its own private query builder,
      since the two finders share no table — note this in the PR as a reading of `database-persistence-0002`),
      and `packages/core/src/Application/Place/Query/LookUpPlaceQuery.php`
      (`@extends BaseQuery<Place|null>`, `fromPayload` over a private constructor, `application-framework-0002`)
      with `LookUpPlaceHandler.php`,
      so T012 passes (depends on T016, T017)
- [X] T020 Create `apps/api/src/Place/GeoJsonSeqPlaceFeed.php` implementing `PlaceFeed`
      (opens the source with `fopen` in read mode, which covers a local path and an HTTPS URL;
      yields `json_decode($line, true, flags: JSON_THROW_ON_ERROR)` keyed by 1-based line number,
      rethrowing a `\JsonException` as a `\RuntimeException` whose message names the line
      (`InvalidPlaceFeed` is in the `Domain` layer, which deptrac does not let `Framework` use);
      skips blank lines; closes the handle in `finally`).
      Covered by T013(f, g) through the console command, over a local path.
      Waiver: the HTTPS branch is not exercised by any suite,
      because no suite may reach the network;
      it is the same `fopen` call with a URL,
      and T043's first `preview` publish exercises it before any `live` run
      and alias `SocialBulletin\Core\Domain\Helper\PlaceFeed` to it in `apps/api/config/services.yaml`;
      create `apps/api/src/Console/ImportPlacesConsoleCommand.php`
      (`#[AsCommand('app:places:import')]`, argument `source`,
      dispatches `ImportPlacesCommand::fromSource()` on `CommandBus`,
      prints the number of seconds taken and nothing about the places)
      (depends on T018)
- [X] T021 Create `apps/api/tests/Behat/PlaceContext.php`
      (`test-integration-0001`: arranges through `CommandBus` only)
      with `#[Given('the places in :file are imported')]`
      dispatching `ImportPlacesCommand::fromSource()` with the path resolved against `apps/api/features/`,
      `#[Then('the response body should not contain :text')]` reading `ApiClient`'s last response,
      `#[When('I run the console command :name with the file :file')]`
      running the command through `Symfony\Bundle\FrameworkBundle\Console\Application`
      on the injected `KernelInterface` with an `ApplicationTester`
      (the console command is the behaviour under test here, not arrangement, so `test-integration-0001` still holds),
      `#[Then('the console command should succeed')]`,
      and `#[Then('the console command should fail mentioning :text')]`
      asserting a non-zero exit code and the text in the output;
      register it in `apps/api/behat.yml.dist`;
      add `And the places in "fixtures/places.geojsonl" are imported`
      to the scenario in `apps/api/features/fixtures.feature`,
      and correct the comment in `ApiContext::theBaselineDatasetIsLoaded()`,
      which says the baseline holds no shared data;
      run `make db` to re-snapshot (depends on T007, T020)
- [X] T022 Create `apps/api/src/Controller/Place/LookUpPlaceController.php`
      (`#[Route('/api/places/lookup', name: 'api_places_lookup', methods: ['POST'])]`,
      decodes the JSON body, dispatches `LookUpPlaceQuery::fromPayload()` on `QueryBus`,
      returns `{"name": <string|null>}`; no `try/catch`, `application-framework-0004`)
      and extend `apps/api/src/EventListener/DomainErrorListener.php`
      so `InvalidPlaceLookup` passes its `errors` like `InvalidMovement`,
      so T013 passes (depends on T019, T021)
- [X] T023 [P] Create `apps/web/src/shared/geo/model/types.ts` (`GeoPoint { lat: number; lon: number }`),
      `apps/web/src/shared/geo/lib/wrap-longitude.ts`,
      and `apps/web/src/shared/geo/index.ts` exporting `GeoPoint` and `wrapLongitude`,
      so T014 passes
- [X] T024 Create `apps/web/src/entities/movement/api/look-up-place-name.ts`
      exporting `lookUpPlaceName(point: GeoPoint, area: Area, signal: AbortSignal): Promise<string | null>`
      and `PlaceLookupUnavailable`,
      built on `apiRequest` from `@/shared/api`,
      with a 10000 ms `setTimeout` that aborts the request and rejects with `PlaceLookupUnavailable`,
      per `specs/002-map-pinned-location/contracts/place-lookup-api.md`;
      export both from `apps/web/src/entities/movement/index.ts`,
      so T015 passes (depends on T023)
- [X] T025 Create `apps/web/src/shared/map/model/view.ts`
      exporting `INITIAL_VIEW = { center: { lat: 45, lon: -4 }, zoom: 4 } as const`
      and `TILES_URL = '/tiles/spain-uk.pmtiles'` (no third-party import).
      With T029's 384 px map, this view spans latitudes 31.8 to 55.7,
      putting Huelin 67 px above the bottom edge
      and central Sheffield 45 px below the top edge,
      both clear of Leaflet's zoom and attribution controls;
      create `apps/web/e2e/support/map.ts` exporting
      `clickMapAt(page, point: { lat: number; lon: number })`:
      it locates `page.getByRole('region', { name: 'Location map' })`,
      reads its bounding box,
      projects `point` and `INITIAL_VIEW.center` to Web Mercator pixels at `INITIAL_VIEW.zoom`
      (`x = 256·2^z·(lon + 180)/360`, `y = 256·2^z·(1 − ln(tan φ + sec φ)/π)/2`),
      throws naming the point if the target lies within 32 px of any edge of the box,
      and clicks with `region.click({ position: { x: width / 2 + dx, y: height / 2 + dy } })`,
      never `page.mouse.click`,
      so Playwright scrolls the map into view
      and fails if a map control would receive the click instead of the map;
      extend `apps/web/e2e/fixtures.ts` with a second auto fixture `stubMapTiles`
      that routes `**/tiles/**` to `route.abort()` before each test,
      so no journey needs the archive (research R9)

**Checkpoint**: `make php-unit`, `make api-tests`, and `make web-unit` pass T008–T015;
`POST /api/places/lookup` answers from the snapshot's fixture places.

---

## Phase 3: User Story 1 - Pin the location by clicking the map (Priority: P1) 🎯 MVP

**Goal**: The author clicks the map, a marker appears,
the Location field fills with the local name at the chosen rank,
and only the name is saved.

**Independent Test**: Create a Neighborhood draft, click the fixture Huelin point on the map,
see "Huelin" from the real API, save,
and find the draft listed as "Neighborhood · Huelin · Cooperative".

### Tests for User Story 1 (write first, must fail) ⚠️

- [X] T026 [P] [US1] Create `apps/web/e2e/movement-location.spec.ts`
      (import `test`, `expect` from `./fixtures.ts`,
      `signIn` from `./support/session.ts`, `clickMapAt` from `./support/map.ts`)
      with the journey "an author pins a neighbourhood on the map and saves its name":
      sign in as `author@example.com`; open New movement;
      fill Title "Huelin Community Kitchen", Category "Cooperative", Area "Neighborhood";
      `clickMapAt(page, { lat: 36.7033, lon: -4.4366 })`;
      expect `page.getByRole('img', { name: 'Pinned location' })` toBeVisible;
      expect `page.getByRole('textbox', { name: 'Location', exact: true })` toHaveValue "Huelin";
      click "Save draft";
      expect the list item for "Huelin Community Kitchen"
      toContainText "Neighborhood · Huelin · Cooperative".
      No geocoder stub: the name comes from the API and the snapshot's fixture places
- [X] T027 [P] [US1] Create `apps/web/src/features/propose-movement/ui/location-picker.test.tsx`
      rendering `LocationPicker` inside `I18nProvider`,
      with `vi.mock('@/shared/map', …)` replacing `LocationMap` by a stub
      that records its latest `point` and `onPick` props
      and renders a `region` named by `label`,
      and with `lookUpPlaceName` from `@/entities/movement` mocked
      (keep the real `wrapLongitude`).
      Cases:
      (a) after `onPick({ lat: 36.7033, lon: -4.4366 })` with `area="neighborhood"`,
      the stub receives that `point`, `lookUpPlaceName` is called with it and `'neighborhood'`,
      a `status` reads "Looking up the place…",
      then `onPlaceFound` is called with "Huelin" and the status clears;
      (b) a resolved `null` shows the alert
      "No place name was found at that level. Type it in instead.",
      does not call `onPlaceFound`, and the stub still has the point;
      (c) a `PlaceLookupUnavailable` rejection shows
      "We couldn't look up that place. Type it in instead.";
      (d) a second pin aborts the first call's `AbortSignal`
      and a late resolution of the first call never reaches `onPlaceFound`;
      (e) a new pin clears a previous alert;
      (f) `onPin` is called on every pin before the lookup resolves;
      (g) the lookup uses the `area` prop current at pin time
      even if the prop changes before it resolves;
      (h) a second pin after the first lookup resolved
      calls `onPlaceFound` again with the new point's name;
      (i) when the `LocationMap` stub throws on render,
      the picker renders without the map region
      and no error escapes it to the surrounding tree (spec edge case: map unavailable)
- [X] T028 [US1] Extend `apps/web/src/features/propose-movement/ui/movement-form.test.tsx`:
      add `vi.mock('@/shared/map', …)` with the same recording stub as T027
      (required for the existing cases, since the form now renders the map)
      and mock `lookUpPlaceName` from `@/entities/movement`.
      New cases:
      (a) the "Location map" region is absent until an area is chosen,
      present for Municipality,
      and absent — together with the Location field — for International;
      (b) choosing Neighborhood and pinning fills Location with "Huelin"
      and submitting calls `onSubmit` with exactly the existing `MovementDraftInput` keys
      and `location: 'Huelin'` (no coordinate keys, FR-005);
      (c) typing "Huelin, Málaga" into Location while a lookup is still pending
      keeps "Huelin, Málaga" after it resolves,
      and the stub's `point` is still the pinned point (FR-004);
      submitting before the lookup resolves
      calls `onSubmit` with `location: 'Huelin, Málaga'`
      and the lookup's `AbortSignal` is aborted once the form unmounts;
      (d) after a fill, changing Area from Municipality to Province
      leaves the Location value unchanged and `lookUpPlaceName` is not called again;
      (e) rendering with `initial` set to a draft with location "Sheffield"
      shows "Sheffield", the stub's `point` is `null`,
      and `lookUpPlaceName` is never called (FR-010);
      (f) after a fill of "Huelin", typing over it with "Barrio de Huelin"
      and submitting calls `onSubmit` with `location: 'Barrio de Huelin'`;
      (g) pinning with Municipality while the lookup is pending,
      switching Area to International, resolving the lookup,
      then switching back to Municipality
      shows the map with the stub's `point` `null`,
      the lookup's `AbortSignal` aborted,
      and the Location field not filled by the late result (spec edge case: international);
      (h) with the lookup resolving `null` (not-found alert shown),
      typing "Sheffield" into Location and submitting
      calls `onSubmit` with `location: 'Sheffield'`;
      the same holds after a `PlaceLookupUnavailable` rejection
      (spec US2 sc.3, sc.4, FR-009, SC-005)

### Implementation for User Story 1

- [X] T029 [US1] Create `apps/web/src/shared/map/ui/location-map.tsx` and `apps/web/src/shared/map/index.ts`
      exporting `LocationMap({ label, markerLabel, point, onPick })` and the T025 constants:
      a `<section aria-label={label}>` with no padding or border,
      containing a react-leaflet `MapContainer` that fills it
      (centre and zoom from `INITIAL_VIEW`, fixed height `h-96` (384 px), full width;
      T025's edge margins depend on this height),
      a child that adds `protomaps-leaflet`'s `leafletLayer({ url: TILES_URL, flavor: 'light' })`
      to the map on mount and removes it on unmount,
      the attribution
      `&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="https://protomaps.com">Protomaps</a>`,
      a `Marker` at `point` when not null,
      with `interactive: false`, `keyboard: false`,
      and an `L.divIcon` whose html is a styled
      `<span role="img" aria-label="…">` carrying `markerLabel`,
      a `useMapEvents` click handler calling `onPick({ lat, lon: wrapLongitude(lng) })`,
      and a child that flies the map to `point` at zoom 14 when `point` changes;
      import `leaflet/dist/leaflet.css` here.
      No Vitest for this adapter: Leaflet cannot be driven in jsdom.
      The click handler and the marker are asserted by the T026 journey,
      written before this task and observed failing first,
      and later by T035.
      Waiver: the fly-to animation and the basemap drawing are not asserted;
      neither has an accessible outcome, and a broken one leaves the pin, the name, and the save intact
      (depends on T002, T023, T025, T026)
- [X] T030 [US1] Create `apps/web/src/features/propose-movement/model/use-place-lookup.ts`:
      a hook `usePlaceLookup({ onFound })` returning
      `{ status: 'idle' | 'pending' | 'not-found' | 'unavailable', lookUp(point, area) }`
      that aborts the previous `AbortController` on each call and on unmount,
      calls `lookUpPlaceName`, ignores `AbortError`,
      and calls `onFound(name)` when a name is found,
      following the transitions in `specs/002-map-pinned-location/data-model.md`
      (depends on T024)
- [X] T031 [US1] Create `apps/web/src/shared/ui/error-boundary.tsx`
      (a class component `ErrorBoundary({ fallback, children })`
      rendering `fallback` once a child throws)
      and export it from `apps/web/src/shared/ui/index.ts`;
      create `apps/web/src/features/propose-movement/ui/location-picker.tsx`:
      `LocationPicker({ area, onPin, onPlaceFound })` holding `point` state,
      rendering `LocationMap` with `label={t('movements.form.map.label')}`
      and `markerLabel={t('movements.form.map.marker')}`
      inside `<ErrorBoundary fallback={null}>`,
      a `role="status"` live region showing `movements.form.map.lookingUp` while pending,
      and `role="alert"` messages for `placeNotFound` / `lookupFailed`
      per `contracts/location-picker-ui.md`;
      on pick: set `point`, call `onPin()`, then `lookUp(point, area)`
      so T027 passes (depends on T029, T030)
- [X] T032 [US1] Update `apps/web/src/features/propose-movement/ui/movement-form.tsx`:
      render `LocationPicker` below the Location input
      only when `area` is set and not `international`
      (unmounting it clears the point and aborts the lookup, satisfying FR-007);
      keep an `editedSincePin` ref set to `false` in `onPin`
      and `true` in the Location input's `onChange`;
      in `onPlaceFound` set the location only while `editedSincePin` is `false`;
      leave the submitted `MovementDraftInput` unchanged
      so T028 and the T026 journey pass (depends on T031)

**Checkpoint**: `make web-unit` and the T026 journey in `make web-e2e` pass;
US1 is shippable on its own once Phase 5's map data is published.

---

## Phase 4: User Story 2 - Pin the author's current position (Priority: P2)

**Goal**: "Use my location" pins the author's position and fills the name as a click would;
refusal or an unavailable position is explained and the field stays editable.

**Independent Test**: With geolocation granted at Huelin and area Municipality,
"Use my location" fills "Málaga" and the draft saves;
with permission refused, the explanation appears and a typed location still saves.

### Tests for User Story 2 (write first, must fail) ⚠️

- [X] T033 [P] [US2] Write Vitest cases in `apps/web/src/shared/geo/api/current-position.test.ts`
      for `currentPosition(): Promise<GeoPoint>` with `navigator.geolocation` stubbed:
      success at 36.7033, -4.4366 resolves `{ lat: 36.7033, lon: -4.4366 }`;
      error code 1 rejects with `PositionDenied`;
      codes 2 and 3 reject with `PositionUnavailable`;
      a missing `navigator.geolocation` rejects with `PositionUnavailable`;
      `getCurrentPosition` is called with a `timeout` of 10000 ms
      and is never called before `currentPosition()` is (FR-002)
- [X] T034 [P] [US2] Extend `apps/web/src/features/propose-movement/ui/location-picker.test.tsx`
      mocking `currentPosition` from `@/shared/geo`:
      (a) clicking the "Use my location" button pins the resolved position
      (stub receives it, `onPin` called, `lookUpPlaceName` called with it)
      and `onPlaceFound` receives "Málaga" for `area="municipality"`;
      (b) a `PositionDenied` rejection shows
      "We couldn't use your location. You can still type it.",
      calls neither `lookUpPlaceName` nor `onPlaceFound`,
      leaves the stub's `point` unchanged,
      and disables or clears nothing outside the picker;
      (c) a `PositionUnavailable` rejection shows
      "Your location isn't available right now. You can still type it.";
      (d) the button is disabled while the position request is pending;
      (e) `currentPosition` is not called on render;
      (f) with the `LocationMap` stub throwing as in T027(i),
      the "Use my location" button is still rendered and usable.
      Also add to `movement-form.test.tsx` (T028):
      (i) after a `PositionDenied` rejection,
      typing "Sheffield" and submitting calls `onSubmit` with `location: 'Sheffield'` (US2 sc.2)
- [X] T035 [P] [US2] Add the journey "an author pins their current position and saves its name"
      to `apps/web/e2e/movement-location.spec.ts`:
      `context.grantPermissions(['geolocation'])`
      and `context.setGeolocation({ latitude: 36.7033, longitude: -4.4366 })`;
      sign in; New movement;
      Title "Málaga Tenants Union", Category "Cooperative", Area "Municipality";
      click "Use my location";
      expect `page.getByRole('img', { name: 'Pinned location' })` toBeVisible;
      expect `page.getByRole('textbox', { name: 'Location', exact: true })` toHaveValue "Málaga";
      save;
      expect the list item toContainText "Municipality · Málaga · Cooperative"

### Implementation for User Story 2

- [X] T036 [US2] Create `apps/web/src/shared/geo/api/current-position.ts`
      exporting `currentPosition()`, `PositionDenied`, and `PositionUnavailable`,
      wrapping `navigator.geolocation.getCurrentPosition` with `{ timeout: 10000 }`,
      and export them from `apps/web/src/shared/geo/index.ts` so T033 passes
- [X] T037 [US2] Update `apps/web/src/features/propose-movement/ui/location-picker.tsx`:
      add the `Button` (from `@/shared/ui`) labelled `movements.form.map.useMyLocation`
      outside the `ErrorBoundary`,
      a position state `'idle' | 'pending' | 'denied' | 'unavailable'`,
      the `positionDenied` / `positionUnavailable` alerts,
      and on success the same pick path as a map click,
      so T034 and the T035 journey pass (depends on T031, T036)

**Checkpoint**: Both stories pass `make web-unit` and `make web-e2e` independently.

---

## Phase 5: Map data and delivery (Cross-cutting)

**Purpose**: Produce, serve, and publish the real tiles and places.
Not a test layer under ADR-0013; each task names its own check.
Required before the feature is usable outside the fixture points.
The self-hosted tiles built here (T038, T041, and the tile parts of T039, T040, T042)
are removed by Phase 6 (spec FR-011, revised 2026-10-16).

- [X] T038 [P] Create `infrastructure/map/coverage.geojson`:
      one `FeatureCollection` with a `MultiPolygon` covering
      mainland Spain with the Balearic Islands, the Canary Islands, Ceuta and Melilla,
      and Great Britain with Northern Ireland,
      each part a simple box or hand-drawn ring with a small margin;
      check it renders as intended on geojson.io before committing
- [X] T039 [P] Create `docker/map/Dockerfile`
      (Debian slim with `osmium-tool`, `jq`, `curl`, `ca-certificates`,
      and the `pmtiles` CLI from a pinned `protomaps/go-pmtiles` GitHub release, checksum verified)
      and `docker/map/build-map-data.sh`
      (`set -eu`; downloads `https://download.geofabrik.de/europe/spain-latest.osm.pbf`,
      `https://download.geofabrik.de/africa/canary-islands-latest.osm.pbf`,
      and `https://download.geofabrik.de/europe/united-kingdom-latest.osm.pbf` into `var/map/src/`;
      `osmium merge`; `osmium tags-filter` keeping `r/boundary=administrative` and `n/place=neighbourhood,quarter,suburb`;
      `osmium export -f geojsonseq --add-unique-id=type_id`;
      a `jq` filter emitting the `places.geojsonl` shape of T007
      for boundaries with `admin_level` in 2, 4, 6, 7, 8, 10 and every named place node;
      then `pmtiles extract https://build.protomaps.com/<YYYYMMDD>.pmtiles var/map/tiles/spain-uk.pmtiles --region=infrastructure/map/coverage.geojson --maxzoom=15`,
      with the build date as a script variable;
      prints the line count and both file sizes);
      add a `map` service to `docker-compose.yml` under `profiles: [tools]`
      building `docker/map` and mounting the repository at `/app`
- [X] T040 Add to `Makefile`, documented in its help:
      `map-data` (runs `docker/map/build-map-data.sh` in the `map` service)
      and `map-import` (runs `app:places:import /app/var/map/places.geojsonl` through `make console`);
      run both once, record in ADR-0024 the tiles file size and the import's run time;
      if the import takes more than 7.5 minutes (half the console Lambda's 15-minute limit),
      switch `PlaceRepository::replaceAll()` to multi-row inserts
      capped by both row count and bind parameters (PostgreSQL allows 65,535),
      and record the change and the new run time in ADR-0024;
      and check the quickstart's Huelin and Sheffield lookups against the real data
      (research R7, known risk); update `docs/engineering/scaffolding/make.md` if it lists targets
      (depends on T020, T038, T039)
- [X] T041 [P] Add to `docker/nginx/conf.d/web.conf`, before `location /`:
      `location /tiles/ { alias /app/var/map/tiles/; }`
      so a missing archive is a 404 rather than `index.html`;
      verify with `curl -I -H 'Range: bytes=0-15' https://dev.app.social.aleherse.com/tiles/spain-uk.pmtiles`
      returning `206` after T040
- [X] T042 [P] Update `infrastructure/lib/frontend-stack.ts`:
      a `MapDataBucket` with the same settings as `AssetsBucket`
      (block public access, S3-managed encryption, SSL enforced,
      retained on `live`, destroyed with objects elsewhere);
      `additionalBehaviors['/tiles/*']` on the distribution
      with `S3BucketOrigin.withOriginAccessControl(mapDataBucket)`,
      `REDIRECT_TO_HTTPS`, `CachePolicy.CACHING_OPTIMIZED`, and `AllowedMethods.ALLOW_GET_HEAD`;
      a `MapDataBucketName` output.
      Leave `DeployAssets` targeting `AssetsBucket` only.
      Verify with `make infra-tsc`.
      No make target synthesises the app, and one cannot run without AWS credentials,
      because `lookupHostedZone()` calls `HostedZone.fromLookup` and no `cdk.context.json` is committed;
      the behaviour itself is proven on the first `preview` deploy by T043's range request
- [ ] T043 Update `.github/workflows/map-data.yml` (created with T040–T042) to publish the places only:
      remove the tiles upload, the `/tiles/*` invalidation, the range-request check,
      and the `DISTRIBUTION_ID` and `FRONT_HOST` lookups they alone use;
      keep `make env images map-data` (so a failed T057 guard stops the job before any upload),
      the places upload to `places/places.geojsonl`, the 3600 s presign,
      and the console invoke with the URL single-quoted
      (the Bref console layer runs the command through a shell,
      and an unquoted `&` in the presigned query string would background a truncated import that still exits 0),
      failing on a `FunctionError` or a missing seconds-taken line (T020).
      First run against `preview`, then follow quickstart "Checking published data",
      including the FR-012 points (Palma, Las Palmas, Ceuta, Melilla at `national` and `state`);
      record in ADR-0024 the console function's import duration on `preview`
      (from the job log or the function's `REPORT` line),
      and apply T040's 7.5-minute rule to it before any `live` run
      (depends on T053, T056, T057)

---

## Phase 6: Revision — OSM tiles and Spanish coverage (spec session 2026-10-16)

**Purpose**: Apply the revised FR-011 (tiles from `tile.openstreetmap.org`, lookup still self-hosted)
and the new FR-012 (Spanish land named at `national` and `state`;
the map-data build fetches Spain and Ceuta from Overpass and fails without them)
to the code Phases 1–5 already delivered.
Research R4, R5, R7, R9, R10; contracts/place-lookup-api.md "Fixture places".

### Decisions

- [X] T049 Correct `docs/decisions/ADR-0024-self-host-map-tiles-and-place-lookup.md` in place
      and rename it with `git mv` to `docs/decisions/ADR-0024-use-osm-tiles-and-self-hosted-place-lookup.md`,
      title "Use OpenStreetMap tiles and a self-hosted place lookup", status still Proposed (research R10):
      "Map rendering" names `leaflet` and `react-leaflet` only, with `<TileLayer>`,
      `maxZoom` 19, and the attribution "© OpenStreetMap contributors";
      "Map tiles" becomes OSM's public servers per research R4,
      including the usage-policy points and the known consequence that OSM sees the zoomed-in view after a pin;
      the context paragraph says only the lookup is self-hosted;
      "Map data pipeline" drops the tiles, adds the Overpass fetch and the T057 guard;
      "Testing" aborts `https://tile.openstreetmap.org/**` instead of `/tiles/**`;
      "Patterns to avoid" narrows the absolute-URL entry to geocoder hosts;
      "Verification" replaces the `/tiles/` range request with the guard and the FR-012 points;
      "Consequences" drops the tiles bucket cost and adds the OSM dependency;
      "Revisit Triggers" replaces the Geofabrik `osmium getid` entry with
      "the guard fails although Overpass answered: check the relations' tags";
      keep the 2026-10-16 measurements and add a line that the 6.2 GB archive was dropped.
      Update the links in `docs/decisions/ADR-0009-adopt-postgresql-and-doctrine-dbal.md`
      and `docs/decisions/ADR-0016-aws-serverless-deployment.md`,
      and correct ADR-0016 in place: the map-data bucket holds the places file only,
      with no `/tiles/*` behaviour, and the line saying the browser makes no cross-origin map request
      becomes "map tiles come from OpenStreetMap's tile servers (ADR-0024)".
      Check with `git grep -n "self-host-map-tiles"` finding nothing outside `specs/`

### Tests (write first, must fail) ⚠️

- [X] T050 [US1] In `apps/web/e2e/fixtures.ts`, change the `stubMapTiles` auto fixture
      to `page.route('https://tile.openstreetmap.org/**', (route) => route.abort())`
      and its comment to say no journey reaches OSM;
      in `apps/web/e2e/movement-location.spec.ts`, in the US1 journey (T026),
      start `page.waitForRequest(/^https:\/\/tile\.openstreetmap\.org\/\d+\/\d+\/\d+\.png$/)`
      before opening New movement and await it after the map region is visible,
      proving the map asks OSM for tiles;
      also expect `page.getByRole('link', { name: 'OpenStreetMap' })` toBeVisible (attribution, FR-011);
      update the header comment that mentions `/tiles/**`.
      Observe the journey failing in `make web-e2e` (the map still requests `/tiles/spain-uk.pmtiles`)
- [X] T051 [P] [US1] Add to the first Scenario Outline's Examples in `apps/api/features/places.feature`
      the six FR-012 rows of the "Expected lookups" table in
      `specs/002-map-pinned-location/contracts/place-lookup-api.md`:
      Palma (39.5696, 2.6502) `national` España and `state` Illes Balears,
      Ceuta (35.8894, -5.3213) `national` España and `state` Ceuta,
      Melilla (35.2923, -2.9381) `national` España and `state` Melilla (spec US1 sc.10).
      Observe them failing in `make api-tests` before T054
      (Palma and Melilla's `state` answer `null`, Ceuta's answers Andalucía)

### Implementation

- [X] T052 [US1] Switch the basemap to OSM raster tiles (research R4, R5):
      in `apps/web/src/shared/map/model/view.ts`,
      `TILES_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'`
      with its doc comment saying the tiles come from OSM's public servers (FR-011);
      in `apps/web/src/shared/map/ui/location-map.tsx`,
      replace the `Basemap` child and the `protomaps-leaflet` import with react-leaflet's
      `<TileLayer url={TILES_URL} attribution={ATTRIBUTION} maxZoom={19} />`,
      the attribution becoming
      `&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors`,
      and the adapter's doc comment naming Leaflet only;
      run `npm uninstall protomaps-leaflet` inside `make shell service=node`
      so `apps/web/package.json` and `apps/web/package-lock.json` drop it;
      if `TILES_URL` is no longer imported outside `shared/map`, drop it from `apps/web/src/shared/map/index.ts`
      (knip, T060).
      T050's journey passes (depends on T050)
- [X] T053 [P] Remove the self-hosted tiles infrastructure:
      delete the `location /tiles/` block from `docker/nginx/conf.d/web.conf`;
      delete `infrastructure/map/coverage.geojson`;
      in `infrastructure/lib/frontend-stack.ts`, remove `additionalBehaviors['/tiles/*']`
      and say in the class comment that the map-data bucket holds the places file for the console import,
      keeping `MapDataBucket` and its `MapDataBucketName` output;
      in `docker/map/Dockerfile`, remove the `pmtiles` download and its `ARG`s, keeping `curl`, `jq`, `osmium-tool`;
      in `docker/map/build-map-data.sh`, remove `PROTOMAPS_BUILD`, the `tiles` directory, the `pmtiles extract` call,
      and the tiles file from the final `du`;
      update the `map-data` help text in `Makefile`
      ("Build var/map/places.geojsonl from OpenStreetMap; fails without Spain's outlying boundaries")
      and its row in `docs/engineering/scaffolding/make.md`.
      Verify with `make images`, `make infra-tsc`, `make up`,
      and `curl -s -o /dev/null -w '%{http_code}' https://dev.app.social.aleherse.com/tiles/x` now answering the SPA (200)
- [X] T054 [US1] Update `apps/api/features/fixtures/places.geojsonl`
      per the "Fixture places" table in `specs/002-map-pinned-location/contracts/place-lookup-api.md`:
      `osm_id` 1 España becomes a `MultiPolygon` of four box rings
      (mainland `[-9.5, 36.0, 3.4, 43.8]`, Balearic Islands `[1.1, 38.6, 4.4, 40.1]`,
      Ceuta `[-5.4, 35.85, -5.25, 35.93]`, Melilla `[-3.0, 35.26, -2.9, 35.32]`);
      `osm_id` 2 Andalucía's south edge moves to 36.0;
      add level-4 boundaries `osm_id` 7 Illes Balears, 8 Ceuta, and 9 Melilla with the same boxes as their España parts,
      each a `Polygon` ring in the T007 shape.
      Run `make db` to re-snapshot,
      then `make api-tests` (T051 passes, every earlier row still passes)
      and `make web-e2e` (the Huelin click still lands inside the narrower Andalucía) (depends on T051)
- [X] T055 [P] Create `docker/map/overpass-boundaries.ql` with the query in research R7
      (Spain by `admin_level` 2 and `ISO3166-1=ES`, Ceuta by `ISO3166-2=ES-CE`,
      recursed to member ways and nodes, `out meta`, `[timeout:600]`)
- [X] T056 In `docker/map/build-map-data.sh`, after the Geofabrik downloads,
      POST `docker/map/overpass-boundaries.ql` to `https://overpass-api.de/api/interpreter`
      with `curl --fail --location --silent --show-error --retry 3 --retry-all-errors`,
      `--user-agent 'social-bulletin map-data (https://github.com/aleherse/social-bulletin)'`,
      and `--data-urlencode data@/app/docker/map/overpass-boundaries.ql`,
      into `"$SRC/overpass.osm"`;
      `osmium sort --overwrite --output "$SRC/overpass.osm.pbf" "$SRC/overpass.osm"`;
      add `"$SRC/overpass.osm.pbf"` to the `osmium merge` inputs.
      An Overpass failure ends the script under `set -eu` (FR-012) (depends on T053, T055)
- [X] T057 Create `docker/map/check-places.sh <places.geojsonl>` (`set -eu`, executable):
      for each of `2 España`, `4 Ceuta`, `4 Melilla`, `4 Illes Balears`,
      `jq -n -e --argjson level <level> --arg name <name> 'any(inputs; .properties.kind == "boundary" and .properties.admin_level == $level and .properties.name == $name)'`
      over the file (`-n` with `inputs` reads every line; `-e` exits non-zero when none matches),
      collecting every missing one,
      printing `Missing boundary: <name> (admin_level <level>)` for each to stderr,
      and exiting 1 if any is missing (research R7);
      call it on `"$OUT/places.geojsonl"` at the end of `docker/map/build-map-data.sh`, before the summary.
      Verify inside `docker compose run --rm map`:
      it exits 0 on `apps/api/features/fixtures/places.geojsonl` after T054
      and exits 1 naming all four on `apps/api/features/fixtures/places-reimport.geojsonl` (depends on T054, T056)
- [X] T058 Run `make map-data` and `make map-import`;
      confirm the build passes the guard,
      then post the quickstart's real-data points to `/api/places/lookup` on the development stack:
      Huelin (all six levels), central Sheffield (`national`, `municipality`),
      and Palma, Las Palmas de Gran Canaria, Ceuta, and Melilla at `national` and `state`
      (spec US1 sc.10);
      record the answers, the Overpass fetch time, the places count, and the import time in ADR-0024
      under a new dated measurements entry; if Ceuta's relation is not `admin_level` 4,
      correct the guard, the research R7 entry, and ADR-0024 to the level OSM uses (depends on T057)

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T044 FR-005 audit:
      grep `packages/core/src/**/Place*`, `apps/api/src/Controller/Place/`, `apps/api/src/Place/`,
      and `apps/api/src/Console/` for logger use and for exception messages built from input,
      and confirm none carries a coordinate;
      confirm `infrastructure/lib/database-stack.ts` sets no parameter group enabling
      `log_statement`, `log_min_duration_statement`, or `log_parameter_max_length*`;
      confirm `apps/api/config/packages/monolog.yaml` and nginx log no request bodies;
      record the result in the pull request
- [X] T045 Coverage audit:
      for each requirement FR-001–FR-011 and edge case in `specs/002-map-pinned-location/spec.md`,
      name the test that fails without it (see Notes);
      temporarily revert each implementation file and confirm its tests go red
- [X] T046 Run `make lint` and fix every finding,
      including `php-deptrac` (the `DomainPort` layer from T004)
      and any `web-knip` unused-export report for
      `apps/web/src/shared/geo/index.ts`, `apps/web/src/shared/map/index.ts`,
      `apps/web/src/shared/ui/index.ts`, and `apps/web/src/entities/movement/index.ts`
- [ ] T047 Run `make ci`, the merge gate
      (lint, `php-unit`, `web-unit`, frontend build, `db`, `api-tests`, and the Playwright journeys
      in an isolated Compose project, now on the PostGIS image),
      and walk `specs/002-map-pinned-location/quickstart.md` steps 1–10 against `make up`;
      on `preview` after T043, time ten pins in DevTools → Network
      and record whether the place name filled within 3 s for each (SC-001);
      the pull request description names `make ci` as the gate that ran
- [X] T048 [P] Use the `extract-rule` skill on the final diff
      to record the conventions this feature sets, each in its matching `docs/rules/` file:
      `Domain/Helper` ports are their own deptrac layer that `Framework` may implement;
      large static assets are served from the app's own origin, never a third party;
      shared Behat/Playwright reference data is loaded by `fixtures.feature` through the same command production uses;
      a provider reading two tables gives each its own query builder (if review agrees with T019)

- [X] T059 Coverage audit for Phase 6:
      FR-011's tile source → T050; FR-012 lookup over a multi-part country → T051;
      FR-012 real data → T057, T058, T043;
      revert T052 and T054 in turn and confirm T050 and T051 go red; record the result in the pull request
- [X] T060 Run `make lint` and fix every finding,
      including any `web-knip` report for `apps/web/src/shared/map/index.ts`
      and a stale `protomaps-leaflet` entry anywhere (`git grep -n -i "protomaps\|pmtiles" -- ':!specs'` finds nothing);
      then T047 (depends on T049–T059)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: no dependencies;
  T003 before T017; T004 before T018; T002 before T029; T007 before T021
- **Foundational (Phase 2)**: depends on Setup;
  tests T008–T015 first;
  core: T016 → T017 → T018 and T019 in parallel;
  api: T020 (after T018) → T021 → T022 (after T019);
  web: T023 → T024; T025 any time after T005
- **US1 (Phase 3)**: depends on Foundational;
  T029 after T002, T023, T025, and T026
- **US2 (Phase 4)**: depends on Foundational, and on T031 (it extends `location-picker.tsx`)
- **Map data (Phase 5)**: T038, T039, T041, T042 can start after Setup;
  T040 needs T020; T043 needs T040 and T042
- **Revision (Phase 6)**: depends on Phases 1–5 as delivered;
  T049 first; tests T050 and T051 before T052 and T054;
  T053 and T055 in parallel; T056 after both; T057 after T054 and T056; T058 after T057;
  T043 after T053, T056, and T057
- **Polish (Phase 7)**: depends on all of the above; T059 and T060 before T047

### User Story Dependencies

- **US1 (P1)**: independent once Foundational is done
- **US2 (P2)**: its position client (T033, T036) is independent;
  its picker work (T034, T037) builds on US1's `LocationPicker`,
  because both stories end in the same pin path.
  US2 stays independently testable through its own Vitest cases and journey.

### Within Each Story

- Tests written and observed failing before implementation
- Map adapter (T029) → hook (T030) → picker (T031) → form (T032)
- Position client (T036) → picker button (T037)

### Parallel Opportunities

- Setup: T004, T005, T006, T007 together, alongside T001
- Foundational tests: T008–T012, T014, T015 together; T013 alongside them
- Foundational implementation: the core chain, T023, and T025 in parallel
- US1 tests: T026 and T027 together
- US2 tests: T033, T034, T035 together
- Map data: T038, T039, T041, T042 together, any time after Setup
- Revision: T050 and T051 together; T053 and T055 together, alongside T052 and T054
- Polish: T048 alongside T044 and T045

---

## Parallel Example: Foundational

```bash
# Tests first, in parallel:
Task: "GeoPointSpec (T008)"
Task: "PlaceLevelSpec (T009)"
Task: "PlaceFeatureSpec (T010)"
Task: "ImportPlacesHandlerSpec (T011)"
Task: "LookUpPlaceQuerySpec and LookUpPlaceHandlerSpec (T012)"
Task: "places.feature (T013)"
Task: "wrap-longitude.test.ts (T014)"
Task: "look-up-place-name.test.ts (T015)"
```

## Parallel Example: User Story 1

```bash
# Tests first, in parallel:
Task: "Playwright journey in apps/web/e2e/movement-location.spec.ts (T026)"
Task: "Vitest LocationPicker cases in apps/web/src/features/propose-movement/ui/location-picker.test.tsx (T027)"

# Then implementation in order:
Task: "LocationMap adapter in shared/map (T029)"
Task: "use-place-lookup hook (T030)"
Task: "ErrorBoundary and LocationPicker component (T031)"
Task: "MovementForm integration (T032)"
```

## Parallel Example: User Story 2

```bash
Task: "Vitest currentPosition cases in apps/web/src/shared/geo/api/current-position.test.ts (T033)"
Task: "Vitest use-my-location cases in location-picker.test.tsx (T034)"
Task: "Playwright use-my-location journey in apps/web/e2e/movement-location.spec.ts (T035)"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 Setup → Phase 2 Foundational
2. Phase 3 (US1)
3. Phase 5 map data, published to `preview`
4. **Stop and validate**: all four suites, quickstart steps 1–5, 7–9, the published-data check
5. Ship: authors can pin by clicking; typed-only entry is unaffected

### Incremental Delivery

1. Setup + Foundational → PostGIS, place lookup API, lookup client, E2E helpers
2. US1 → map adapter, picker, form → validate
3. Map data → real tiles and places on `preview` → validate → ship (MVP)
4. US2 → validate → ship
5. Revision → OSM tiles, Overpass fetch and guard, FR-012 lookups → `make map-data` → T043 on `preview`
6. Polish → FR-005 audit, coverage audit, lint, `make ci`, rule extraction

---

## Notes

- [P] tasks touch different files and have no dependency on incomplete tasks
- Commit after each task or logical group (Conventional Commits)
- Pyramid (Principle II):
  domain facts in PHPSpec (ranges, area → rank, feature parsing);
  the SQL and HTTP mapping in Behat (every level, no place, fallback, 422, 401);
  UI states in Vitest;
  one happy-path journey per story in Playwright, against the real API.
  The coordinate-free URL is proven by T015(a), the lowest layer that can;
  the journeys do not repeat it.
- Requirement coverage:
  FR-001 → T026, T028(a);
  FR-002 → T033, T034(e);
  FR-003 → T009, T013(a), T027(a,g), T028(d);
  FR-004 → T028(c,f);
  FR-005 → T008, T013(b,c), T015(a), T028(b), T044;
  FR-006 → T028(b,h);
  FR-007 → T028(a,g);
  FR-008 → T033, T034(b,c);
  FR-009 → T013(a, null rows), T015(d,e,g), T027(b,c), T028(h);
  FR-010 → T028(e);
  FR-011 → T050 (tiles requested from `tile.openstreetmap.org`, attribution shown), T013 (lookup served by the API), T058 and T043 (real data);
  FR-012 → T051, T054 (lookup over a multi-part country), T057 (build guard), T058 and T043 (real data).
  Waiver: whether the real OSM data names Spanish land at both levels is not tested by any suite,
  because no suite downloads OSM data; the T057 guard and the T058/T043 checks cover it
- Spec scenario coverage:
  US1 sc.1 → T013(a) Sheffield `municipality`, T027(a), T026;
  sc.2 → T028(b), T026;
  sc.3 → T013(a) Sheffield `national`;
  sc.4 → T009, T013(a) Huelin rows;
  sc.5 → T028(f);
  sc.6 → existing typed-only case in `movement-form.test.tsx`;
  sc.7 → T027(d,h);
  sc.8 → T028(d);
  sc.9 → T028(e);
  sc.10 → T051 (Palma, Ceuta, Melilla), T058 and T043 (all four points, real data);
  US2 sc.1 → T034(a), T035;
  sc.2 → T034(b), T028(i);
  sc.3 → T027(b), T028(h);
  sc.4 → T015(e,g), T027(c), T028(h)
- Edge case coverage:
  international → T028(a,g), T009, T013(d);
  area level changed → T028(d);
  no place at the level, including outside Spain and the UK → T013(a) Madrid and Atlantic rows, T027(b);
  date line → T014, T015(b);
  map unavailable → T027(i), T034(f);
  basemap unavailable → T026, T035 (every journey runs with OSM tile requests aborted by `stubMapTiles`, T050);
  Spanish waters and the *plazas de soberanía* → not guaranteed by FR-012, so no test;
  slow lookup (typing and saving while pending) → T028(c), T015(g);
  typing does not move the marker → T028(c)
