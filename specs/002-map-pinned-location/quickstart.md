# Quickstart: Pin a Movement's Location on a Map

How to see the feature working and run its tests once implemented.

## Configuration

Two optional build-time variables for `apps/web`
(defaults point at the public OSM services, fine for development):

| Variable | Default |
|----------|---------|
| `VITE_GEOCODER_URL` | `https://nominatim.openstreetmap.org` |
| `VITE_MAP_TILE_URL` | `https://tile.openstreetmap.org/{z}/{x}/{y}.png` |

The public services allow light use only (research R6);
do not script or load-test against them.

Live builds never use the defaults:
`.github/workflows/deploy.yml` passes the repository variables
`GEOCODER_URL` and `MAP_TILE_URL` as these two variables,
and the deploy fails if either is unset.

## Try it

1. `make up`
2. Open `https://dev.app.social.aleherse.com/en`, sign in with any email,
   go to **My movements → New movement**.
3. Choose area **Neighborhood**, then click Málaga's Huelin district
   on the map (zoom in on the southern coast of Spain).
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

No coordinates appear anywhere in the network calls to the API
(DevTools → Network → the `movements` POST/PUT bodies).

## Run the tests

All four suites, per the constitution:

| Command | Covers for this feature |
|---------|-------------------------|
| `make php-unit` | Unchanged (waived: core not touched) |
| `make api-tests` | Unchanged (waived: API not touched) |
| `make web-unit` | Area→place mapping, longitude wrap, geocoder client, picker behaviours and messages |
| `make web-e2e` | Pin-by-click journey (US1) and use-my-location journey (US2), geocoder and tiles stubbed |

`make tests` runs all four against the development stack.
Before merging, `make ci` (ADR-0015) must pass:
it runs the linters and all four suites in an isolated Compose project.
