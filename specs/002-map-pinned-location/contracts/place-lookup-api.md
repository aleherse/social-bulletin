# Contract: Place lookup API

The platform **exposes** this endpoint to its own frontend (research R1, R6).
It replaces the Nominatim interface the first round consumed.

## Request

```http
POST /api/places/lookup
Content-Type: application/json
Cookie: <session JWT>

{"lat": 36.7033, "lon": -4.4366, "area": "neighborhood"}
```

| Field | Type | Rule |
|-------|------|------|
| `lat` | number | −90 … 90 |
| `lon` | number | −180 … 180; the client wraps it first (`wrapLongitude`) |
| `area` | string | one of `national`, `state`, `province`, `region`, `municipality`, `neighborhood` |

The point travels only in the body, never in the URL (FR-005).
The endpoint sits behind the existing `^/api` firewall (`IS_AUTHENTICATED_FULLY`).
The client sends credentials, aborts superseded requests,
and gives up after 10 s (FR-009).

## Responses

| Case | Status | Body |
|------|--------|------|
| A place at the area's rank | 200 | `{"name": "Huelin"}` |
| No place at that rank (sea, outside the loaded place data, rank absent) | 200 | `{"name": null}` |
| `lat` or `lon` missing, not a number, or out of range | 422 | JSON:API error; pointer `/lat` or `/lon`, detail from `place.point.out_of_range` |
| `area` missing, unknown, or `international` | 422 | JSON:API error; pointer `/area`, detail from `place.area.unsupported` |
| Not signed in | 401 | existing firewall response |

No response body, error, or log line contains the submitted coordinates.

## Client outcomes

| Response | `lookUpPlaceName` result | Picker state |
|----------|--------------------------|--------------|
| 200 with a string `name` | the name | field filled |
| 200 with `name: null` | `null` | `not-found` |
| any other status, a network error, or a body without a `name` key | `PlaceLookupUnavailable` | `unavailable` |
| no response within 10 s | request aborted, `PlaceLookupUnavailable` | `unavailable` |
| aborted by a newer pin or by unmounting | `AbortError`, ignored | unchanged |

## Fixture places

Synthetic boxes with real names, nested around the spec's two points,
plus the Spanish territories outside the mainland that FR-012 names,
saved one GeoJSON feature per line as `apps/api/features/fixtures/places.geojsonl`
and loaded by `apps/api/features/fixtures.feature` through `ImportPlacesCommand`,
so the DSLR `fixtures` snapshot carries them into Behat and Playwright.
Boxes are `[west, south, east, north]` in degrees.
The Huelin-centred boxes are at least ±0.5° wide
so a click computed at the map's initial zoom 4 (about 0.09° per pixel) lands inside.
España is a MultiPolygon of separate parts, as the real country boundary is,
so the lookup is proven on a point in a part other than the mainland;
Andalucía stops at 36.0° N so it does not overlap Ceuta.

| `osm_id` | Kind | Level | Name | Box / point |
|----------|------|-------|------|-------------|
| 1 | boundary | 2 | España | MultiPolygon: mainland `[-9.5, 36.0, 3.4, 43.8]`, Balearic Islands `[1.1, 38.6, 4.4, 40.1]`, Ceuta `[-5.4, 35.85, -5.25, 35.93]`, Melilla `[-3.0, 35.26, -2.9, 35.32]` |
| 2 | boundary | 4 | Andalucía | `[-7.5, 36.0, -1.6, 38.7]` |
| 3 | boundary | 6 | Málaga | `[-5.3366, 35.8033, -3.5366, 37.6033]` |
| 4 | boundary | 7 | Málaga-Costa del Sol | `[-5.1366, 36.0033, -3.7366, 37.4033]` |
| 5 | boundary | 8 | Málaga | `[-5.0366, 36.1033, -3.8366, 37.3033]` |
| 6 | boundary | 10 | Huelin | `[-4.9366, 36.2033, -3.9366, 37.2033]` |
| 7 | boundary | 4 | Illes Balears | `[1.1, 38.6, 4.4, 40.1]` |
| 8 | boundary | 4 | Ceuta | `[-5.4, 35.85, -5.25, 35.93]` |
| 9 | boundary | 4 | Melilla | `[-3.0, 35.26, -2.9, 35.32]` |
| 11 | boundary | 2 | United Kingdom | `[-8.7, 49.8, 1.8, 60.9]` |
| 12 | boundary | 4 | England | `[-5.8, 49.9, 1.8, 55.8]` |
| 13 | boundary | 8 | Sheffield | `[-1.9701, 52.8811, -0.9701, 53.8811]` |
| 21 | neighbourhood | — | Kelham Island | point `(53.3895, -1.4701)`, about 930 m north of central Sheffield |

Expected lookups (spec User Story 1, scenarios 1, 3, 4, 10):

| Point | Area | `name` |
|-------|------|--------|
| Huelin (36.7033, -4.4366) | `national` | España |
| Huelin | `state` | Andalucía |
| Huelin | `province` | Málaga |
| Huelin | `region` | Málaga-Costa del Sol |
| Huelin | `municipality` | Málaga |
| Huelin | `neighborhood` | Huelin |
| Palma (39.5696, 2.6502) | `national` | España |
| Palma | `state` | Illes Balears |
| Ceuta (35.8894, -5.3213) | `national` | España |
| Ceuta | `state` | Ceuta |
| Melilla (35.2923, -2.9381) | `national` | España |
| Melilla | `state` | Melilla |
| Central Sheffield (53.3811, -1.4701) | `national` | United Kingdom |
| Central Sheffield | `municipality` | Sheffield |
| Central Sheffield | `province` | `null` |
| Central Sheffield | `neighborhood` | Kelham Island (fallback within 1000 m) |
| South Sheffield (53.3600, -1.4701), about 3.3 km from Kelham Island | `neighborhood` | `null` |
| Madrid (40.4168, -3.7038) | `municipality` | `null` |
| North Atlantic (45.0, -20.0) | `national` | `null` |
