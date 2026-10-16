# Quickstart: Pin a Movement's Location on a Map

How to see the feature working and run its tests once implemented.

## Map data

The place lookup is self-hosted (FR-011, research R7);
the map's tiles come from OpenStreetMap's public servers in the browser (research R4).

| Command | What it does |
|---------|--------------|
| `make map-data` | Downloads the Geofabrik extracts and the Spain and Ceuta boundaries from Overpass, then writes `var/map/places.geojsonl`; fails if Spain, Ceuta, Melilla, or the Balearic Islands is missing (FR-012) |
| `make map-import` | Loads `var/map/places.geojsonl` into the development database |

`make db` alone loads only the synthetic fixture places
([contracts/place-lookup-api.md](contracts/place-lookup-api.md)),
which is all the suites need.
Without `make map-data` the map still shows OSM's tiles,
and pins at the fixture points fill names.

For `live` and `preview`, run the **Map data** workflow (`map-data.yml`) by hand:
it uploads the places file to the map-data bucket
and imports the places through the console Lambda.

## Try it

1. `make up`, then `make map-data` and `make map-import` for real data.
2. Open `https://dev.app.social.aleherse.com/en`, sign in with any email,
   go to **My movements → New movement**.
3. Choose area **Neighborhood**, zoom in on Málaga's southern coast,
   and click the Huelin district.
   The Location field fills with **Huelin**.
4. Change the area to **Province**: the field still reads **Huelin**
   (no refill, clarification Q4).
   Click the marker's spot again: the field becomes **Málaga**.
5. Type over the name and save: the list shows your typed name.
6. Choose **Use my location** and deny the browser prompt:
   a message says your location could not be used;
   the field stays editable.
7. Choose area **International**: the map and the Location field disappear.
8. Open the saved draft's **Edit** page:
   the field shows the saved name and the map has no marker.
9. Click a point in France with area **Municipality**:
   "No place name was found at that level" (place names cover Spain and the UK;
   the tiles show France anyway).
10. With area **State**, click Ceuta: the field fills with **Ceuta**.

In DevTools → Network, the `places/lookup` request carries the point in its POST body only,
tile requests go to `tile.openstreetmap.org` and carry no point,
and the `movements` POST/PUT bodies carry no coordinates at all (FR-005).

## Checking published data

After the **Map data** workflow runs, sign in on `preview`
and post the spec's points to `/api/places/lookup` for every area.
The answers must match the table in [contracts/place-lookup-api.md](contracts/place-lookup-api.md)
for Huelin (all six levels) and Sheffield (`national`, `municipality`),
and the spec's FR-012 points (US1 sc.10) must answer "España" at `national`
and "Illes Balears", "Canarias", "Ceuta", and "Melilla" at `state`
for Palma (39.5696, 2.6502), Las Palmas de Gran Canaria (28.1235, -15.4363),
Ceuta (35.8894, -5.3213), and Melilla (35.2923, -2.9381).
These come from real OSM data, which no suite loads (research R7).
Record the answers and the import's run time in the pull request.

## Run the tests

All four suites, per the constitution:

| Command | Covers for this feature |
|---------|-------------------------|
| `make php-unit` | `GeoPoint`, `PlaceLevel`, boundary and neighbourhood parsing, the import and lookup handlers |
| `make api-tests` | `POST /api/places/lookup` against the fixture places: every level, no place, the neighbourhood fallback, 422s, 401 |
| `make web-unit` | Lookup client, longitude wrap, position client, picker behaviours and messages |
| `make web-e2e` | Pin-by-click journey (US1) and use-my-location journey (US2) against the real API; OSM tile requests aborted |

`make tests` runs all four against the development stack.
Before merging, `make ci` (ADR-0015) must pass:
it runs the linters and all four suites in an isolated Compose project.
