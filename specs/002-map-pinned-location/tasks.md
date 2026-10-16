---

description: "Task list for Pin a Movement's Location on a Map"
---

# Tasks: Pin a Movement's Location on a Map

**Input**: Design documents from `/specs/002-map-pinned-location/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/](contracts/), [quickstart.md](quickstart.md)

**Tests**: MANDATORY (Constitution Principle I).
This feature touches `apps/web` (Vitest) and user journeys (Playwright).
Test tasks come before the implementation they cover
and MUST be observed failing first.

**Organization**: Tasks are grouped by user story;
each story is independently implementable and testable.

## Layer waivers (Principle I)

- **`packages/core` — PHPSpec: waived.**
  No domain change: `Movement.location` stays a nullable string
  and no coordinate is modelled (spec FR-005, FR-006; data-model.md).
- **`apps/api` — Behat: waived.**
  No endpoint, payload, or persistence change;
  place-name lookups go from the browser straight to the geocoder
  and never reach the API (research R7).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story the task belongs to (US1, US2)
- All paths are relative to the repository root

## Conventions for every task

- Read `docs/rules/application/frontend.md` before writing code.
- Third-party imports (`leaflet`, `react-leaflet`, `react-i18next`, `react-router`)
  only inside their `shared/*` adapter slice (`application-frontend-0001`);
  `shared/map` is the only slice that imports `leaflet` or `react-leaflet`.
- User-visible strings come from i18n keys in
  `apps/web/src/shared/i18n/locales/en/common.json`;
  names and texts are fixed by [contracts/location-picker-ui.md](contracts/location-picker-ui.md).
- Tests query by role, label, or visible text only (Principle II).
- Comments only where code cannot carry the meaning (Principle V).
- Run commands through `make`
  (`make web-unit`, `make web-e2e`, `make lint`, `make ci`,
  `make shell service=node` for npm).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Record the decision, add dependencies,
add shared test data and strings, and prepare the E2E helpers.

- [ ] T001 Write `docs/decisions/ADR-0022-adopt-leaflet-and-nominatim.md`
      with the `adr-skill`, status Proposed,
      following ADR-0019's section layout.
      Decision content from research.md R1, R2, R4, R5, R6, R7:
      Leaflet 1.9.4 + react-leaflet 5.0.0 behind `shared/map`;
      OSM raster tiles and Nominatim reverse geocoding called browser-direct;
      `format=geocodejson`, `zoom=18`, `accept-language=xx`;
      endpoints configurable through `VITE_MAP_TILE_URL` and `VITE_GEOCODER_URL`;
      the public OSM services are for development and light use only;
      live builds read both endpoints from the repository variables
      `GEOCODER_URL` and `MAP_TILE_URL`
      and the deploy fails when either is unset (T029),
      so production never falls back to the public services;
      a provider's own attribution, where it requires one,
      is shown alongside the OSM attribution;
      non-goals: storing coordinates, an API proxy, forward search
- [ ] T002 Add `leaflet@1.9.4` and `react-leaflet@5.0.0` to dependencies
      and `@types/leaflet` to devDependencies in `apps/web/package.json`
      by running `npm install` inside `make shell service=node`,
      so `apps/web/package-lock.json` is updated too
- [ ] T003 [P] Create `apps/web/e2e/fixtures/geocoder-huelin.json`
      with exactly the JSON body under "Recorded fixture"
      in `specs/002-map-pinned-location/contracts/reverse-geocoding.md`
- [ ] T004 [P] Add the keys
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
- [ ] T005 [P] Move `signIn` out of `apps/web/e2e/movements.spec.ts`
      into `apps/web/e2e/support/session.ts` and import it from there;
      in the same file, replace every `page.getByLabel('Location')`
      with `page.getByRole('textbox', { name: 'Location', exact: true })`,
      because the map region ("Location map") and the marker ("Pinned location")
      would otherwise also match Playwright's substring, case-insensitive label lookup
      and fail the journeys with a strict-mode violation;
      a pure refactor, confirmed by the existing journeys passing in `make web-e2e`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Movement-agnostic geo values, the geocoder client, the area→rank mapping,
and E2E isolation from OSM — both stories need all of them.
The Leaflet adapter (T012) is built in US1,
after the journey that covers it.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

### Tests (write first, must fail) ⚠️

- [ ] T006 [P] Write Vitest cases in `apps/web/src/shared/geo/lib/wrap-longitude.test.ts`
      for `wrapLongitude(lon)` returning a value in [-180, 180):
      `-4.4366` unchanged, `190` → `-170`, `-190` → `170`,
      `180` → `-180`, `535.5634` → `175.5634`
- [ ] T007 [P] Write Vitest cases in `apps/web/src/shared/geo/api/reverse-geocode.test.ts`
      for `reverseGeocode(point, signal)`,
      stubbing `globalThis.fetch` with `vi.stubGlobal`:
      (a) requests `https://nominatim.openstreetmap.org/reverse`
      with search params `format=geocodejson`, `lat`, `lon`, `zoom=18`, `accept-language=xx`
      and passes an `AbortSignal` that aborts when the caller's `signal` does;
      (b) wraps an out-of-range `lon` (e.g. 355.5634 → -4.4366) before sending;
      (c) maps the body of `apps/web/e2e/fixtures/geocoder-huelin.json` to
      `{ country: 'España', locality: 'Huelin', admin: { level4: 'Andalucía', level6: 'Málaga', level7: 'Málaga-Costa del Sol', level8: 'Málaga', level10: 'Huelin' } }`
      (level9 and any unknown keys dropped);
      (d) resolves `null` for `{ "type": "FeatureCollection", "features": [] }`;
      (e) rejects with `GeocoderUnavailable` for a 503 response,
      a network `TypeError`, and a 200 body missing `features`;
      (f) lets an `AbortError` caused by the caller's `signal` propagate unchanged
      so callers can ignore it;
      (g) with `vi.useFakeTimers()`, a `fetch` that never settles
      rejects with `GeocoderUnavailable` once 10000 ms pass
      and aborts the request (spec slow-lookup edge case, FR-009)
- [ ] T008 [P] Write Vitest cases in `apps/web/src/entities/movement/model/place-name.test.ts`
      for `placeNameFor(area, place)`:
      with the Huelin hierarchy from T007(c)
      return España / Andalucía / Málaga / Málaga-Costa del Sol / Málaga / Huelin
      for `national` / `state` / `province` / `region` / `municipality` / `neighborhood`;
      with the Sheffield hierarchy
      `{ country: 'United Kingdom', locality: null, admin: { level4: 'England', level8: 'Sheffield' } }`
      return "United Kingdom" for `national`, "England" for `state`,
      "Sheffield" for `municipality` (spec US1 sc.1, sc.3),
      and `null` for `province`, `region`, and `neighborhood`;
      `neighborhood` returns `locality` when `admin.level10` is absent;
      `international` returns `null`

### Implementation

- [ ] T009 Create `apps/web/src/shared/geo/model/types.ts`
      (`GeoPoint { lat: number; lon: number }`,
      `PlaceHierarchy { country: string | null; locality: string | null; admin: Partial<Record<'level4' | 'level6' | 'level7' | 'level8' | 'level10', string>> }`)
      and `apps/web/src/shared/geo/lib/wrap-longitude.ts` so T006 passes
- [ ] T010 Create `apps/web/src/shared/geo/api/reverse-geocode.ts`
      exporting `reverseGeocode(point: GeoPoint, signal: AbortSignal): Promise<PlaceHierarchy | null>`
      and the `GeocoderUnavailable` error class,
      with the base URL read as
      `(import.meta.env.VITE_GEOCODER_URL as string | undefined) ?? 'https://nominatim.openstreetmap.org'`
      (same pattern as `apps/web/src/shared/api/http.ts`),
      and a 10000 ms timeout driven by `setTimeout`
      that aborts the request and rejects with `GeocoderUnavailable`,
      per `contracts/reverse-geocoding.md`;
      create `apps/web/src/shared/geo/index.ts` exporting
      `GeoPoint`, `PlaceHierarchy`, `wrapLongitude`, `reverseGeocode`, `GeocoderUnavailable`
      so T007 passes (depends on T009)
- [ ] T011 [P] Create `apps/web/src/entities/movement/model/place-name.ts`
      implementing the research.md R3 table
      and export `placeNameFor` from `apps/web/src/entities/movement/index.ts`
      so T008 passes
      (depends on T009 for the `PlaceHierarchy` type, imported from `@/shared/geo`;
      parallel with T010)
- [ ] T013 Extend `apps/web/e2e/fixtures.ts` with a second auto fixture `isolateMapServices`
      that, before each test,
      routes `https://tile.openstreetmap.org/**` to a 200 `image/png` response
      with a 1×1 transparent PNG body
      and routes `https://nominatim.openstreetmap.org/**` to `route.abort()`,
      so no journey (including the existing ones in `apps/web/e2e/movements.spec.ts`)
      ever reaches OSM;
      tests that need the geocoder override it with their own `page.route`

**Checkpoint**: `make web-unit` passes T006–T008; `shared/geo` and the mapping are ready.

---

## Phase 3: User Story 1 - Pin the location by clicking the map (Priority: P1) 🎯 MVP

**Goal**: The author clicks the map, a marker appears,
the Location field fills with the local name at the chosen rank,
and only the name is saved.

**Independent Test**: Create a Neighborhood draft, click the map, see "Huelin"
(geocoder stubbed with the Huelin fixture), save,
and find the draft listed as "Neighborhood · Huelin · Cooperative".

### Tests for User Story 1 (write first, must fail) ⚠️

- [ ] T014 [P] [US1] Create `apps/web/e2e/movement-location.spec.ts`
      (import `test`, `expect` from `./fixtures.ts`
      and `signIn` from `./support/session.ts`)
      with the journey "an author pins a neighbourhood on the map and saves its name":
      `page.route('https://nominatim.openstreetmap.org/reverse**', …)`
      fulfilling with `apps/web/e2e/fixtures/geocoder-huelin.json`
      and keeping the intercepted request's `lat` and `lon` search params;
      record every request to the API host (`page.on('request', …)`);
      sign in as `author@example.com`; open New movement;
      fill Title "Huelin Community Kitchen", Category "Cooperative", Area "Neighborhood";
      click the centre of `page.getByRole('region', { name: 'Location map' })`;
      expect `page.getByRole('img', { name: 'Pinned location' })` toBeVisible;
      expect `page.getByRole('textbox', { name: 'Location', exact: true })` toHaveValue "Huelin";
      click "Save draft";
      expect the list item for "Huelin Community Kitchen"
      toContainText "Neighborhood · Huelin · Cooperative";
      finally assert that the recorded `lat` and `lon` values
      appear in no API request URL or body (FR-005, SC-003)
- [ ] T015 [P] [US1] Create `apps/web/src/features/propose-movement/ui/location-picker.test.tsx`
      rendering `LocationPicker` inside `I18nProvider`,
      with `vi.mock('@/shared/map', …)` replacing `LocationMap` by a stub
      that records its latest `point` and `onPick` props
      and renders a `region` named by `label`,
      and with `reverseGeocode` from `@/shared/geo` mocked
      (keep the real `wrapLongitude`).
      Cases:
      (a) after `onPick({ lat: 36.7033, lon: -4.4366 })` with `area="neighborhood"`,
      the stub receives that `point`, a `status` reads "Looking up the place…",
      then `onPlaceFound` is called with "Huelin" and the status clears;
      (b) with `area="province"` the same pin yields "Málaga";
      (c) a hierarchy without `level7` and `area="region"` shows the alert
      "No place name was found at that level. Type it in instead."
      and does not call `onPlaceFound`, and the stub still has the point;
      (d) a resolved `null` shows the same not-found alert;
      (e) a `GeocoderUnavailable` rejection shows
      "We couldn't look up that place. Type it in instead.";
      (f) a second pin aborts the first call's `AbortSignal`
      and a late resolution of the first call never reaches `onPlaceFound`;
      (g) a new pin clears a previous alert;
      (h) `onPin` is called on every pin before the lookup resolves;
      (i) the lookup uses the `area` prop current at pin time
      even if the prop changes before it resolves;
      (j) a second pin after the first lookup resolved
      calls `onPlaceFound` again with the new point's name;
      (k) when the `LocationMap` stub throws on render,
      the picker renders without the map region
      and no error escapes it to the surrounding tree (spec edge case: map unavailable)
- [ ] T016 [US1] Extend `apps/web/src/features/propose-movement/ui/movement-form.test.tsx`:
      add `vi.mock('@/shared/map', …)` with the same recording stub as T015
      (required for the existing cases, since the form now renders the map)
      and mock `reverseGeocode` from `@/shared/geo`.
      New cases:
      (a) the "Location map" region is absent until an area is chosen,
      present for Municipality,
      and absent — together with the Location field — for International;
      (b) choosing Neighborhood and pinning fills Location with "Huelin"
      and submitting calls `onSubmit` with exactly the existing `MovementDraftInput` keys
      and `location: 'Huelin'` (no coordinate keys);
      (c) typing "Huelin, Málaga" into Location while a lookup is still pending
      keeps "Huelin, Málaga" after it resolves,
      and the stub's `point` is still the pinned point (FR-004);
      submitting before the lookup resolves
      calls `onSubmit` with `location: 'Huelin, Málaga'`
      and the lookup's `AbortSignal` is aborted once the form unmounts;
      (d) after a fill, changing Area from Municipality to Province
      leaves the Location value unchanged and `reverseGeocode` is not called again;
      (e) rendering with `initial` set to a draft with location "Sheffield"
      shows "Sheffield", the stub's `point` is `null`,
      and `reverseGeocode` is never called;
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
      the same holds after a `GeocoderUnavailable` rejection
      (spec US2 sc.3, sc.4, FR-009, SC-005)

### Implementation for User Story 1

- [ ] T012 [US1] Create `apps/web/src/shared/map/ui/location-map.tsx` and `apps/web/src/shared/map/index.ts`
      exporting `LocationMap({ label, markerLabel, point, onPick })`:
      a `<section aria-label={label}>` containing a react-leaflet `MapContainer`
      (initial centre `[20, 0]`, zoom 2, fixed height e.g. `h-72`, full width),
      a `TileLayer` with URL
      `(import.meta.env.VITE_MAP_TILE_URL as string | undefined) ?? 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'`
      and the OSM attribution
      `&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors`,
      a `Marker` at `point` when not null,
      with `interactive: false`, `keyboard: false`,
      and an `L.divIcon` whose html is a styled
      `<span role="img" aria-label={markerLabel}>`,
      a `useMapEvents` click handler calling `onPick({ lat, lon: wrapLongitude(lng) })`,
      and a child that flies the map to `point` at zoom 14 when `point` changes;
      import `leaflet/dist/leaflet.css` here.
      No Vitest for this adapter: Leaflet cannot be driven in jsdom.
      The click handler and the marker are asserted by the T014 journey,
      written before this task and observed failing first,
      and later by T022.
      Waiver: the fly-to animation is not asserted;
      it has no accessible outcome, and a broken fly-to leaves the pin, the name, and the save intact
      (depends on T002, T009, T014)
- [ ] T017 [US1] Create `apps/web/src/features/propose-movement/model/use-place-lookup.ts`:
      a hook `usePlaceLookup({ onFound })` returning
      `{ status: 'idle' | 'pending' | 'not-found' | 'unavailable', lookUp(point, area) }`
      that aborts the previous `AbortController` on each call and on unmount,
      calls `reverseGeocode`, maps with `placeNameFor`, ignores `AbortError`,
      and calls `onFound(name)` when a name is found,
      following the transitions in `specs/002-map-pinned-location/data-model.md`
      (depends on T010, T011)
- [ ] T018 [US1] Create `apps/web/src/shared/ui/error-boundary.tsx`
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
      so T015 passes (depends on T012, T017)
- [ ] T019 [US1] Update `apps/web/src/features/propose-movement/ui/movement-form.tsx`:
      render `LocationPicker` below the Location input
      only when `area` is set and not `international`
      (unmounting it clears the point and aborts the lookup, satisfying FR-007);
      keep an `editedSincePin` ref set to `false` in `onPin`
      and `true` in the Location input's `onChange`;
      in `onPlaceFound` set the location only while `editedSincePin` is `false`;
      leave the submitted `MovementDraftInput` unchanged
      so T016 passes (depends on T018)

**Checkpoint**: `make web-unit` and the T014 journey in `make web-e2e` pass;
US1 is shippable on its own.

---

## Phase 4: User Story 2 - Pin the author's current position (Priority: P2)

**Goal**: "Use my location" pins the author's position and fills the name as a click would;
refusal or an unavailable position is explained and the field stays editable.

**Independent Test**: With geolocation granted at Huelin and area Municipality,
"Use my location" fills "Málaga" and the draft saves;
with permission refused, the explanation appears and a typed location still saves.

### Tests for User Story 2 (write first, must fail) ⚠️

- [ ] T020 [P] [US2] Write Vitest cases in `apps/web/src/shared/geo/api/current-position.test.ts`
      for `currentPosition(): Promise<GeoPoint>` with `navigator.geolocation` stubbed:
      success at 36.7033, -4.4366 resolves `{ lat: 36.7033, lon: -4.4366 }`;
      error code 1 rejects with `PositionDenied`;
      codes 2 and 3 reject with `PositionUnavailable`;
      a missing `navigator.geolocation` rejects with `PositionUnavailable`;
      `getCurrentPosition` is called with a `timeout` of 10000 ms
      and is never called before `currentPosition()` is (FR-002)
- [ ] T021 [P] [US2] Extend `apps/web/src/features/propose-movement/ui/location-picker.test.tsx`
      mocking `currentPosition` from `@/shared/geo`:
      (a) clicking the "Use my location" button pins the resolved position
      (stub receives it, `onPin` called, `reverseGeocode` called with it)
      and `onPlaceFound` receives "Málaga" for `area="municipality"`;
      (b) a `PositionDenied` rejection shows
      "We couldn't use your location. You can still type it.",
      calls neither `reverseGeocode` nor `onPlaceFound`,
      and leaves the stub's `point` unchanged,
      and the alert does not disable or clear anything outside the picker
      (form-level saving is proven by T016(h), since the path past an alert is the same);
      (c) a `PositionUnavailable` rejection shows
      "Your location isn't available right now. You can still type it.";
      (d) the button is disabled while the position request is pending;
      (e) `currentPosition` is not called on render;
      (f) with the `LocationMap` stub throwing as in T015(k),
      the "Use my location" button is still rendered and usable
- [ ] T022 [P] [US2] Add the journey "an author pins their current position and saves its name"
      to `apps/web/e2e/movement-location.spec.ts`:
      `context.grantPermissions(['geolocation'])`
      and `context.setGeolocation({ latitude: 36.7033, longitude: -4.4366 })`;
      stub the geocoder with the Huelin fixture as in T014;
      sign in; New movement;
      Title "Málaga Tenants Union", Category "Cooperative", Area "Municipality";
      click "Use my location";
      expect `page.getByRole('img', { name: 'Pinned location' })` toBeVisible;
      expect `page.getByRole('textbox', { name: 'Location', exact: true })` toHaveValue "Málaga";
      save;
      expect the list item toContainText "Municipality · Málaga · Cooperative"

### Implementation for User Story 2

- [ ] T023 [US2] Create `apps/web/src/shared/geo/api/current-position.ts`
      exporting `currentPosition()`, `PositionDenied`, and `PositionUnavailable`,
      wrapping `navigator.geolocation.getCurrentPosition` with `{ timeout: 10000 }`,
      and export them from `apps/web/src/shared/geo/index.ts` so T020 passes
- [ ] T024 [US2] Update `apps/web/src/features/propose-movement/ui/location-picker.tsx`:
      add the `Button` (from `@/shared/ui`) labelled `movements.form.map.useMyLocation`
      outside the `ErrorBoundary`,
      a position state `'idle' | 'pending' | 'denied' | 'unavailable'`,
      the `positionDenied` / `positionUnavailable` alerts,
      and on success the same pick path as a map click,
      so T021 and the T022 journey pass (depends on T018, T023)

**Checkpoint**: Both stories pass `make web-unit` and `make web-e2e` independently.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [ ] T025 Coverage audit:
      for each requirement FR-001–FR-010 and edge case in `specs/002-map-pinned-location/spec.md`,
      name the test that fails without it (T006–T008, T014–T016, T020–T022);
      temporarily revert each implementation file and confirm its tests go red;
      confirm the two layer waivers above still hold
      (no change under `packages/core` or `apps/api`)
- [ ] T026 Run `make lint` and fix every finding,
      including any unused-export report for
      `apps/web/src/shared/geo/index.ts`, `apps/web/src/shared/map/index.ts`,
      and `apps/web/src/shared/ui/index.ts`
- [ ] T027 Run `make ci`, the merge gate
      (lint, `php-unit`, `web-unit`, frontend build, `api-tests`, and the Playwright journeys
      in an isolated Compose project),
      and walk `specs/002-map-pinned-location/quickstart.md` steps 1–8 against `make up`;
      while walking step 3, time ten pins in DevTools → Network
      and record in the pull request whether the place name filled within 3 s for each (SC-001);
      the pull request description names `make ci` as the gate that ran
- [ ] T028 [P] Use the `extract-rule` skill on the final diff
      to record the conventions this feature sets —
      external browser services read their base URL from a `VITE_*` variable with a dev default,
      and every external host is stubbed in the Playwright auto fixture —
      in `docs/rules/application/frontend.md`
- [ ] T029 Update the "Build frontend assets" step of `.github/workflows/deploy.yml`:
      add `VITE_GEOCODER_URL: ${{ vars.GEOCODER_URL }}`
      and `VITE_MAP_TILE_URL: ${{ vars.MAP_TILE_URL }}` to its `env`,
      next to `VITE_API_URL`,
      and add a step before it that fails the job with an `::error::` annotation
      naming each of `GEOCODER_URL` and `MAP_TILE_URL` that is empty.
      An unset repository variable expands to an empty string,
      which the `?? default` in T010 and T012 does not catch,
      so the guard is what keeps a live build off both a broken URL
      and the public OSM services (research R6).
      Prerequisite outside the repository:
      a maintainer provisions a hosted or self-run Nominatim-compatible geocoder and tile endpoint
      and sets both repository variables before the first deploy of this feature;
      record the chosen provider in ADR-0022 (T001).
      No make target lints workflows,
      so verify by running the guard step's shell locally
      with both variables empty (exits non-zero, names both)
      and with both set (exits zero)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: no dependencies;
  T001, T003, T004, T005 can start at once, T002 before T012
- **Foundational (Phase 2)**: depends on Setup;
  tests T006–T008 first, then T009 → T010 and T011 in parallel;
  T013 any time after T003
- **US1 (Phase 3)**: depends on Foundational and on T005 (T014 imports `signIn`);
  T012 after T002, T009, and T014
- **US2 (Phase 4)**: depends on Foundational, and on T018 (it extends `location-picker.tsx`)
- **Polish (Phase 5)**: depends on the stories being shipped

### User Story Dependencies

- **US1 (P1)**: independent once Foundational is done
- **US2 (P2)**: its geolocation client (T020, T023) is independent;
  its picker work (T021, T024) builds on US1's `LocationPicker`,
  because both stories end in the same pin path.
  US2 stays independently testable through its own Vitest cases and journey.

### Within Each Story

- Tests written and observed failing before implementation
- Map adapter (T012) → hook (T017) → picker (T018) → form (T019)
- Geolocation client (T023) → picker button (T024)

### Parallel Opportunities

- Setup: T001, T003, T004, T005 together
- Foundational tests: T006, T007, T008 together;
  then T010 and T011 in parallel once T009's types exist
- US1 tests: T014 and T015 together
  (T016 shares a mock pattern with T015 but a different file)
- US2 tests: T020, T021, T022 together
- Polish: T028 and T029 alongside T025

---

## Parallel Example: User Story 1

```bash
# Tests first, in parallel:
Task: "Playwright journey in apps/web/e2e/movement-location.spec.ts (T014)"
Task: "Vitest LocationPicker cases in apps/web/src/features/propose-movement/ui/location-picker.test.tsx (T015)"

# Then implementation in order:
Task: "LocationMap adapter in shared/map (T012)"
Task: "use-place-lookup hook (T017)"
Task: "ErrorBoundary and LocationPicker component (T018)"
Task: "MovementForm integration (T019)"
```

## Parallel Example: User Story 2

```bash
Task: "Vitest currentPosition cases in apps/web/src/shared/geo/api/current-position.test.ts (T020)"
Task: "Vitest use-my-location cases in location-picker.test.tsx (T021)"
Task: "Playwright use-my-location journey in apps/web/e2e/movement-location.spec.ts (T022)"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 Setup → Phase 2 Foundational
2. Phase 3 (US1)
3. **Stop and validate**: `make web-unit`, the T014 journey, quickstart steps 1–5, 7, 8
4. Ship: authors can pin by clicking; typed-only entry is unaffected

### Incremental Delivery

1. Setup + Foundational → shared geo, mapping, isolated E2E
2. US1 → map adapter, picker, form → validate → ship (MVP)
3. US2 → validate → ship
4. Polish → coverage audit, lint, `make ci`, rule extraction, live endpoints

---

## Notes

- [P] tasks touch different files and have no dependency on incomplete tasks
- Commit after each task or logical group (Conventional Commits)
- Validation, error, and edge cases live in Vitest only;
  Playwright holds one happy-path journey per story (Principle II).
  The FR-005 network check rides on the T014 journey
  because only the compiled bundle against the real API can prove
  that no request carries the point.
- Spec scenario coverage:
  US1 sc.1 → T008 (Sheffield `municipality`), T015(a,b), T014;
  sc.2 → T016(b), T014;
  sc.3 → T008 (Sheffield `national`);
  sc.4 → T008;
  sc.5 → T016(f);
  sc.6 → existing typed-only case in `movement-form.test.tsx`;
  sc.7 → T015(f,j);
  sc.8 → T016(d);
  sc.9 → T016(e);
  US2 sc.1 → T021(a), T022;
  sc.2 → T021(b), T016(h);
  sc.3 → T015(c,d), T016(h);
  sc.4 → T007(e,g), T015(e), T016(h)
- Edge case coverage:
  international → T016(a,g);
  area level changed → T016(d);
  no place at the level → T015(c,d);
  date line → T006, T007(b);
  map unavailable → T015(k), T021(f);
  slow lookup (typing and saving while pending) → T016(c), T007(g);
  typing does not move the marker → T016(c)
