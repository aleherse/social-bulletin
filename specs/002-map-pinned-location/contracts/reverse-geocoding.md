# Contract: Reverse geocoding (consumed)

The app **consumes** this interface; it does not expose it.
Provider: OSM Nominatim or any Nominatim-compatible host
(research R1, R6).

## Request

```http
GET {VITE_GEOCODER_URL}/reverse?format=geocodejson&lat={lat}&lon={lon}&zoom=18&accept-language=xx
```

| Parameter | Value | Why |
|-----------|-------|-----|
| `format` | `geocodejson` | Exposes `admin.levelN` keyed by OSM admin level (R1, R3) |
| `lat`, `lon` | `GeoPoint`, longitude wrapped into −180 … 180 | Spec edge case: date line |
| `zoom` | `18` | Most detailed result, so every admin parent is present |
| `accept-language` | `xx` | Forces local official names over the browser's `Accept-Language` (R2, clarification Q3) |

`VITE_GEOCODER_URL` defaults to `https://nominatim.openstreetmap.org`.
Requests are made only on an explicit pin and are abortable (R6).
No credentials, cookies, or custom headers are sent.

## Response fields read

Everything else in the body is ignored.

```text
features[0].properties.geocoding.country     string?
features[0].properties.geocoding.locality    string?
features[0].properties.geocoding.admin       { level4?, level6?, level7?, level8?, level10?: string }
```

## Outcomes

| Response | Client result | Picker state |
|----------|---------------|--------------|
| 200 with `features[0]` | `PlaceHierarchy` | name from `placeNameFor`, or `not-found` when that returns `null` |
| 200 with empty `features` (e.g. at sea) | `null` | `not-found` |
| non-2xx, network error, or body without the shape above | error | `unavailable` |
| aborted by a newer pin | ignored | unchanged |

## Recorded fixture

Recorded 2026-10-16 for the Huelin point (36.7033, -4.4366)
with `Accept-Language: en` sent alongside `accept-language=xx`;
trimmed to the fields read.
Vitest and the Playwright `page.route` stub use this body,
saved as `apps/web/e2e/fixtures/geocoder-huelin.json`.

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {
        "geocoding": {
          "country": "España",
          "locality": "Huelin",
          "admin": {
            "level10": "Huelin",
            "level9": "Carretera de Cádiz",
            "level8": "Málaga",
            "level7": "Málaga-Costa del Sol",
            "level6": "Málaga",
            "level4": "Andalucía"
          }
        }
      },
      "geometry": { "type": "Point", "coordinates": [-4.4366, 36.7033] }
    }
  ]
}
```

Expected names from this fixture (spec User Story 1, scenario 4):

| Area | Name |
|------|------|
| `national` | España |
| `state` | Andalucía |
| `province` | Málaga |
| `region` | Málaga-Costa del Sol |
| `municipality` | Málaga |
| `neighborhood` | Huelin |
