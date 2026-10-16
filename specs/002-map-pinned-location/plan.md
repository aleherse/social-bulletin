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
The point is never stored: it lives in form state only,
and the lookup goes straight from the browser to an OSM Nominatim-compatible geocoder,
so it never reaches the platform's servers or logs.
The change is frontend-only:
`packages/core`, `apps/api`, the database, and the API contract are untouched.
Rank-based names come from Nominatim's `geocodejson` `admin.levelN` fields
and local names from `accept-language=xx`,
both verified against the public service (research R1–R3).

## Technical Context

**Language/Version**: TypeScript 6 + React 19 in `apps/web`;
PHP 8.5 code is not changed

**Primary Dependencies**: New: `leaflet@1.9.4`, `react-leaflet@5.0.0`,
`@types/leaflet` (dev) — behind a `shared/map` adapter (`application-frontend-0001`).
Existing: `@tanstack/react-query` is **not** used for lookups
(one-shot, abortable, user-triggered calls; see data-model transitions).
External services: OSM Nominatim reverse geocoding and OSM raster tiles,
URLs configurable via `VITE_GEOCODER_URL` and `VITE_MAP_TILE_URL`

**Storage**: N/A — no persisted change (data-model.md)

**Testing**: Vitest + Testing Library for `apps/web` units and components;
Playwright for one happy-path journey per user story.
PHPSpec and Behat waived (layers untouched; see Constitution Check)

**Target Platform**: Browsers served by nginx locally and CloudFront in AWS (ADR-0004, ADR-0016)

**Project Type**: Web application (monorepo; only `apps/web` changes)

**Performance Goals**: Place name filled within 3 s of a pin for 95% of pins (SC-001);
a lookup still unanswered after 10 s is reported as failed (FR-009);
Leaflet loaded only in the lazy new/edit movement chunks (ADR-0019 code splitting)

**Constraints**: Nominatim fair use — one request per explicit pin,
superseded requests aborted, none on typing or area change (research R6);
a map that fails to render is contained by an error boundary,
leaving the Location field and "use my location" working (spec edge case);
OSM attribution always visible;
the point must not be sent to the platform's API (FR-005);
FSD import rules and `shared/*` adapters for third-party libraries

**Scale/Scope**: One new form section, two new `shared` slices (`map`, `geo`),
one `ErrorBoundary` in `shared/ui`,
one pure mapping function in `entities/movement`,
two new Playwright journeys, eight i18n strings

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
      divergence proposes a new ADR; applicable `docs/rules/` files read
- [x] **IV. Automated gates**: work is runnable through `make` targets; test data
      comes from the DSLR `fixtures` snapshot and no test run recreates it

Architecture boundaries (hexagonal layering, deptrac, FSD imports) are not a
constitutional gate; they are governed by ADR-0005/ADR-0007 and enforced by
`make lint`. Gate III covers consulting them.

**Layers touched by this feature**:

| Layer | Touched? | Test tool | Where |
|-------|----------|-----------|-------|
| `packages/core` | no | PHPSpec | waived — no domain change; `location` stays a string (FR-006) |
| `apps/api` | no | Behat | waived — no endpoint or payload change; lookups bypass the API (research R7) |
| `apps/web` | yes | Vitest | next to the source in `shared/geo`, `shared/map`, `entities/movement`, `features/propose-movement` |
| User journeys | yes | Playwright | `apps/web/e2e/movement-location.spec.ts` |

The two waivers MUST be restated in `tasks.md`, naming the layer and reason
(Principle I).

**Gate notes**:

- **II. Pyramid** — Playwright: US1 pin-by-click → field filled → saved name listed,
  plus a check that the pinned coordinates reach no API request (FR-005);
  US2 use-my-location with granted geolocation → field filled → saved.
  Vitest only: area→rank mapping for all seven areas, missing ranks,
  `locality` fallback, longitude wrap, `accept-language=xx` in the request,
  failure/abort/timeout mapping, permission denied, position unavailable,
  not-found and unavailable messages, typing during a pending lookup,
  no refill on area change, map hidden for International,
  a map that fails to render, edit form opening without a marker.
- **III. Decisions** — Consulted ADR-0004 (no proxy needed; nginx untouched),
  ADR-0007 (FSD placement), ADR-0008 (i18n keys), ADR-0010 (shadcn `Button`),
  ADR-0013 (tools), ADR-0019 (lazy routes keep Leaflet out of other chunks).
  New structural decision → propose **ADR-0022**
  (Leaflet + Nominatim, browser-direct, configurable endpoints) as the first task.
  Rules read: `docs/rules/application/frontend.md` (0001 drives `shared/map`
  and `shared/geo` re-exports).
- **IV. Gates** — No new make target; `make web-unit` and `make web-e2e` cover it,
  and `make ci` (ADR-0015) is the merge gate.
  Playwright still restores the DSLR `fixtures` snapshot per scenario;
  geocoder and tile hosts are stubbed with `page.route`, never called.

**Post-design re-check (after Phase 1)**: PASS.
The data model adds no persisted fields, the contracts name every
accessible element the tests use, and Complexity Tracking stays empty.

## Project Structure

### Documentation (this feature)

```text
specs/002-map-pinned-location/
├── plan.md              # This file
├── research.md          # Phase 0: geocoder, names, mapping, map library, testing
├── data-model.md        # Phase 1: client-side values and picker state
├── quickstart.md        # Phase 1: try it, run the suites
├── contracts/
│   ├── reverse-geocoding.md   # Consumed Nominatim request/response + Huelin fixture
│   └── location-picker-ui.md  # Accessible names and messages tests assert on
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 (/speckit-tasks — not created here)
```

### Source Code (repository root)

```text
docs/decisions/
└── ADR-0022-adopt-leaflet-and-nominatim.md   # new, status Proposed

apps/web/
├── package.json                               # + leaflet, react-leaflet, @types/leaflet
├── package-lock.json                          # updated by npm install
├── src/
│   ├── shared/
│   │   ├── ui/
│   │   │   ├── index.ts                       # + ErrorBoundary
│   │   │   └── error-boundary.tsx             # new: contains a map that fails to render
│   │   ├── geo/                               # new slice: no third-party imports
│   │   │   ├── index.ts
│   │   │   ├── model/types.ts                 # GeoPoint, PlaceHierarchy
│   │   │   ├── lib/wrap-longitude.ts (+ .test.ts)
│   │   │   ├── api/reverse-geocode.ts (+ .test.ts)   # fetch, abort, shape check
│   │   │   └── api/current-position.ts (+ .test.ts)  # navigator.geolocation → typed errors
│   │   ├── map/                               # new slice: the only Leaflet importer
│   │   │   ├── index.ts
│   │   │   └── ui/location-map.tsx            # region, tiles, labelled divIcon Marker, onPick, leaflet.css
│   │   └── i18n/locales/en/common.json        # + movements.form.map.*
│   ├── entities/movement/
│   │   ├── index.ts                           # + placeNameFor
│   │   └── model/place-name.ts (+ .test.ts)   # research R3 mapping
│   └── features/propose-movement/
│       ├── ui/movement-form.tsx               # renders LocationPicker, tracks editedSincePin
│       ├── ui/movement-form.test.tsx          # + picker integration cases
│       ├── ui/location-picker.tsx (+ .test.tsx)   # map, button, status, alerts
│       └── model/use-place-lookup.ts          # pin → abort → lookup → name | not-found | unavailable
└── e2e/
    ├── fixtures.ts                            # + isolateMapServices auto fixture
    ├── movements.spec.ts                      # imports signIn from support/session.ts
    ├── movement-location.spec.ts              # US1 + US2 journeys
    ├── support/session.ts                     # new: signIn, moved from movements.spec.ts
    └── fixtures/geocoder-huelin.json          # from contracts/reverse-geocoding.md
```

**Structure Decision**: Only `apps/web` changes, following ADR-0007's FSD layers.
Generic, movement-agnostic capabilities go to `shared`:
`shared/geo` (types, longitude wrap, geocoder and geolocation clients)
and `shared/map` (the Leaflet adapter, the only slice importing `leaflet`/`react-leaflet`).
The area→rank mapping depends on the `Area` type,
so it belongs to `entities/movement`.
The picker's behaviour exists only inside the movement form,
so it stays in `features/propose-movement` rather than becoming a new feature slice
(a sibling feature could not be imported by the form under FSD rules).
`pages/movements` needs no change: new and edit pages already render `MovementForm`.

## Complexity Tracking

No constitution violations to justify — table intentionally empty.
