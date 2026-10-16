# Research: Pin a Movement's Location on a Map

Phase 0 output for [plan.md](plan.md).
Each entry records the decision, why it was chosen, and what was rejected.

## R1. Place-name lookup service

**Decision**: OpenStreetMap Nominatim reverse geocoding,
`GET {VITE_GEOCODER_URL}/reverse?format=geocodejson&lat={lat}&lon={lon}&zoom=18&accept-language=xx`,
called directly from the browser.
`VITE_GEOCODER_URL` defaults to `https://nominatim.openstreetmap.org`.

**Rationale**: The `geocodejson` format returns an `admin` object
keyed by OSM `admin_level`,
which is exactly the rank-based hierarchy the spec fixes (FR-003).
Verified on 2026-10-16 against the public instance:

| Point | `country` | `level4` | `level6` | `level7` | `level8` | `level10` |
|-------|-----------|----------|----------|----------|----------|-----------|
| Huelin, Málaga (36.7033, -4.4366) | España | Andalucía | Málaga | Málaga-Costa del Sol | Málaga | Huelin |
| Central Sheffield (53.3811, -1.4701) | United Kingdom | England | — | — | Sheffield | — |

Sheffield also returns `level5: South Yorkshire`,
a rank the hierarchy deliberately skips.
Its missing `level6`, `level7`, and `level10`
are the "no place at the chosen level" edge case the spec already covers.

**Alternatives considered**:

- *Nominatim `jsonv2` with `address` keys* (`state`, `province`, `county`, `suburb` …):
  keys follow each country's tagging, not admin rank —
  Sheffield's `county` is a level-5 metropolitan county,
  Málaga's `county` is the level-7 comarca.
  Rejected: contradicts the rank-based clarification.
- *Nominatim `zoom` per area level*: zoom maps to address rank in steps
  that skip rank 14 (comarca), so `region` cannot be selected.
  Rejected.
- *Commercial geocoders* (Mapbox, Google, HERE):
  API keys in the browser, per-request cost,
  and no admin-level keyed output.
  Rejected for now; R1's URL is configurable, so a Nominatim-compatible
  host can replace the public one without code changes.

## R2. Local official names regardless of the browser's language

**Decision**: Always send the query parameter `accept-language=xx`.

**Rationale**: Browsers send an `Accept-Language` header
that Nominatim honours, translating names
(`Accept-Language: en` returned "Andalusia", "Malaga", "Spain").
The query parameter overrides the header;
`xx` matches no `name:*` translation,
so Nominatim falls back to each place's `name` tag — its local official name.
Verified: with header `en` and `accept-language=xx`,
the Huelin point returns España, Andalucía, Málaga.

**Alternatives considered**:

- *Omit the parameter*: works from `curl`, fails from every browser.
  Rejected.
- *`namedetails=1` and read `name`*: only covers the result object,
  not its admin parents. Rejected.

The trick depends on Nominatim's fallback behaviour,
so the client spec (Vitest) pins the parameter
and the contract records it ([contracts/reverse-geocoding.md](contracts/reverse-geocoding.md)).

## R3. Area level → admin rank mapping

**Decision**:

| Area level | Source field | Fallback |
|------------|--------------|----------|
| `national` | `country` | — |
| `state` | `admin.level4` | — |
| `province` | `admin.level6` | — |
| `region` | `admin.level7` | — |
| `municipality` | `admin.level8` | — |
| `neighborhood` | `admin.level10` | `locality` |
| `international` | not looked up | — |

A missing field is a failed lookup (FR-009).

**Rationale**: OSM admin levels match the clarified Spanish hierarchy one to one.
`level2` is absent from `admin` (country comes as `country`).
`level9` is a city district (Málaga's "Carretera de Cádiz")
and is not a neighbourhood, so it is not used.
Many cities map neighbourhoods as `place=neighbourhood` points
rather than level-10 boundaries;
Nominatim surfaces those as `locality`,
so it is the only fallback.

**Alternatives considered**: falling back to the nearest neighbouring rank
(e.g. `level5` when `level6` is missing).
Rejected: the spec's assumption says a missing rank fails the lookup
and the author types the name.

## R4. Map rendering library

**Decision**: Leaflet `1.9.4` with `react-leaflet` `5.0.0`
(React 19 peer), plus `@types/leaflet`,
wrapped in a new `shared/map` slice (`application-frontend-0001`).
The marker is a `CircleMarker` so no image assets are needed.

**Rationale**: The feature needs one raster map, a click handler,
and one marker.
Leaflet is ~40 kB gzipped, has no WebGL requirement
(Playwright's container Chromium renders it without GPU flags),
and `react-leaflet` 5 targets React 19.
`CircleMarker` sidesteps Leaflet's default-icon URL breakage under Vite.

**Alternatives considered**:

- *MapLibre GL JS*: vector tiles and WebGL; heavier (~200 kB),
  needs a style/tile provider, and adds GPU concerns to E2E.
  Rejected as more than the feature needs.
- *OpenLayers*: capable but large and imperative;
  no maintained React binding the project would want.
  Rejected.

## R5. Map tiles

**Decision**: Raster tiles from
`VITE_MAP_TILE_URL`, default `https://tile.openstreetmap.org/{z}/{x}/{y}.png`,
with the OSM attribution Leaflet renders in the map corner.

**Rationale**: Matches R1's data source (names and tiles agree),
needs no key, and the URL is configurable for production.

**Alternatives considered**: a keyed tile provider.
Deferred: chosen when production traffic warrants it,
by changing the environment variable only.

## R6. Fair use of the public OSM services

**Decision**: Lookups are made only on an explicit pin
(click or "use my location"), one request per pin.
A new pin aborts the in-flight request (`AbortController`).
No lookup on area-level change (clarification Q4) or on typing.
The browser identifies the app through the `Referer` header it sends;
no custom `User-Agent` can be set from a browser.

**Rationale**: The Nominatim usage policy allows at most 1 request per second
per application and requires identification and attribution.
User-driven, one-at-a-time requests stay well under that.

**Open operational item**: before production traffic grows,
point `VITE_GEOCODER_URL` and `VITE_MAP_TILE_URL` at a self-hosted
or commercial Nominatim-compatible endpoint.
Recorded in the proposed ADR (R9), not a blocker for this feature.

## R7. Browser-direct lookup instead of an API proxy

**Decision**: The browser calls the geocoder directly;
`apps/api` and `packages/core` are not involved.

**Rationale**:

- FR-005 says the platform never records the pinned point.
  A proxy would put `lat`/`lon` in query strings
  that nginx and CloudFront access logs record.
- The movement's stored data is unchanged (FR-006),
  so the API has nothing new to validate or persist.
- Each author's browser is its own client,
  so one proxy IP does not concentrate every author's traffic
  against the 1 request/second policy.

**Alternatives considered**: a Symfony endpoint wrapping Nominatim
(port in core, DBAL-free adapter in api).
Rejected: adds two layers and a Behat suite for no behaviour the spec asks for,
and works against FR-005.

## R8. Testing approach

**Decision**:

- **Vitest**: pure functions (`placeNameFor`, `wrapLongitude`),
  the geocoder client (URL, `accept-language=xx`, failure mapping, abort),
  and the location picker / form behaviours with `shared/map` mocked
  at its public API.
- **Playwright**: one happy-path journey per user story,
  with `page.route` stubbing the geocoder (recorded Huelin response)
  and the tile host (empty 1×1 PNG),
  and `context.grantPermissions(['geolocation'])` +
  `setGeolocation` for "use my location".
- **PHPSpec / Behat**: not touched; explicit waiver (see plan).

**Rationale**: Constitution Principle II.
Errors and edges (permission denied, failed lookup, level change,
stale response, international, date-line wrap)
are provable in Vitest, so they stay there.
Leaflet cannot be driven meaningfully in jsdom,
so the real map is exercised only in Playwright,
which is also the only layer that proves the compiled bundle and Leaflet CSS work.
Stubbing the public services keeps E2E deterministic
and off OSM's infrastructure.

## R9. Architecture decision record

**Decision**: Propose `ADR-0021: Adopt Leaflet and OSM Nominatim for maps and place lookup`,
written with the `adr-skill` as the first implementation task.

**Rationale**: Constitution Principle III —
a new third-party UI library and an external runtime dependency
are structural decisions, as `react-router` was (ADR-0018).
