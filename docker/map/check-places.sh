#!/bin/sh
# ADR-0024, spec FR-012: Geofabrik clips boundaries whose members leave its
# extract, so a build missing Spain's outlying land fails here, before upload.
set -eu

places="$1"
missing=0

for boundary in "2 España" "4 Ceuta" "4 Melilla" "4 Illes Balears"; do
    level=${boundary%% *}
    name=${boundary#* }
    if ! jq -n -e --argjson level "$level" --arg name "$name" \
        'any(inputs; .properties.kind == "boundary" and .properties.admin_level == $level and .properties.name == $name)' \
        "$places" > /dev/null; then
        echo "Missing boundary: $name (admin_level $level)" >&2
        missing=1
    fi
done

exit "$missing"
