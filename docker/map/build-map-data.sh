#!/bin/sh
# ADR-0024: builds var/map/places.geojsonl (the shape of
# apps/api/features/fixtures/places.geojsonl).
set -eu

OUT=/app/var/map
SRC="$OUT/src"
mkdir -p "$SRC"

for extract in europe/spain africa/canary-islands europe/united-kingdom; do
    curl --fail --location --silent --show-error \
        --output "$SRC/$(basename "$extract").osm.pbf" \
        "https://download.geofabrik.de/$extract-latest.osm.pbf"
done

# Geofabrik clips Spain's country boundary and Ceuta's, whose members leave
# its extract; Overpass returns them whole, and the merge fills in the gaps.
curl --fail --location --silent --show-error --retry 3 --retry-all-errors \
    --user-agent 'social-bulletin map-data (https://github.com/aleherse/social-bulletin)' \
    --data-urlencode data@/app/docker/map/overpass-boundaries.ql \
    --output "$SRC/overpass.osm" \
    https://overpass-api.de/api/interpreter
osmium sort --overwrite --output "$SRC/overpass.osm.pbf" "$SRC/overpass.osm"

osmium merge --overwrite --output "$SRC/merged.osm.pbf" \
    "$SRC/spain.osm.pbf" "$SRC/canary-islands.osm.pbf" "$SRC/united-kingdom.osm.pbf" \
    "$SRC/overpass.osm.pbf"

# Relation members are kept, so the boundary polygons can be assembled.
osmium tags-filter --overwrite --output "$SRC/places.osm.pbf" "$SRC/merged.osm.pbf" \
    r/boundary=administrative n/place=neighbourhood,quarter,suburb

osmium export --overwrite --output-format geojsonseq --format-option print_record_separator=false \
    --add-unique-id=type_id --output "$SRC/places.geojsonseq" "$SRC/places.osm.pbf"

# Boundaries are relations at the ranks PlaceLevel maps; neighbourhoods are named place nodes.
# osmium names an assembled area "a<id>", where id is 2 * the relation id + 1 (even for closed ways).
jq --compact-output '
    select((.properties.name // "") != "")
    | if (.id | startswith("a")) and ((.id[1:] | tonumber) % 2 == 1)
        and .properties.boundary == "administrative"
        and (.geometry.type == "Polygon" or .geometry.type == "MultiPolygon")
        and ((.properties.admin_level // "") | IN("2", "4", "6", "7", "8", "10"))
      then {
        type: "Feature",
        properties: {
          osm_id: (((.id[1:] | tonumber) - 1) / 2),
          kind: "boundary",
          admin_level: (.properties.admin_level | tonumber),
          name: .properties.name
        },
        geometry: .geometry
      }
      elif (.id | startswith("n"))
        and (.properties.place | IN("neighbourhood", "quarter", "suburb"))
        and .geometry.type == "Point"
      then {
        type: "Feature",
        properties: { osm_id: (.id[1:] | tonumber), kind: "neighbourhood", name: .properties.name },
        geometry: .geometry
      }
      else empty
      end
' "$SRC/places.geojsonseq" > "$OUT/places.geojsonl"

/app/docker/map/check-places.sh "$OUT/places.geojsonl"

echo "Places: $(wc -l < "$OUT/places.geojsonl") features"
du -h "$OUT/places.geojsonl"
