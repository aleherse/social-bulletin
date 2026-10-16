# Contract: Location picker UI

The accessible surface Vitest and Playwright assert against
(Constitution Principle II: role, label, visible text — no CSS selectors or test IDs).
English strings are the `en` locale values;
keys live under `movements.form` in
`apps/web/src/shared/i18n/locales/en/common.json`.

## Elements

Rendered inside the movement form, only while the area is not `international`.

| Element | Role / query | Accessible name | i18n key |
|---------|--------------|-----------------|----------|
| Location field | `textbox` via label | "Location" | `locationLabel` (existing) |
| Map | `region` | "Location map" | `map.label` |
| Use-my-location action | `button` | "Use my location" | `map.useMyLocation` |
| Lookup in progress | `status` (polite live region) | "Looking up the place…" | `map.lookingUp` |
| Messages below | `alert` | see table | see table |

The map's own controls (zoom buttons, OSM attribution link)
come from Leaflet and are not part of this contract
beyond the attribution staying visible (OSM licence).

## Messages

| Situation | Text | i18n key | Requirement |
|-----------|------|----------|-------------|
| Position permission refused | "We couldn't use your location. You can still type it." | `map.positionDenied` | FR-008 |
| Position unavailable or timed out | "Your location isn't available right now. You can still type it." | `map.positionUnavailable` | FR-008 |
| No place at the chosen level | "No place name was found at that level. Type it in instead." | `map.placeNotFound` | FR-009 |
| Lookup service failed | "We couldn't look up that place. Type it in instead." | `map.lookupFailed` | FR-009 |

A message clears on the next pin or "use my location".
Messages never block saving.

## Behaviour visible to tests

| Action | Observable result |
|--------|-------------------|
| Click the map | A marker appears (asserted in Playwright via the filled field, not the SVG); field filled per [reverse-geocoding.md](reverse-geocoding.md) |
| Type in the field after pinning | Field keeps the typed text even if the lookup resolves later |
| Change area between non-international levels | Field text unchanged; no geocoder request made |
| Change area to International | Map region, button, and Location field hidden |
| Open an existing draft for editing | Location field shows the saved name; no geocoder request made |

The Leaflet adapter (`shared/map`) is mocked in Vitest at its public API,
exposing an `onPick(point)` the test calls directly.
