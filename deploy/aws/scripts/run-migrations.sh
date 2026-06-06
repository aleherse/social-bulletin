#!/bin/sh
set -eu

ENVIRONMENT="${1:-preview}"

echo "Running database migrations for socialbulletin-${ENVIRONMENT}"
aws lambda invoke \
  --function-name "socialbulletin-${ENVIRONMENT}-backend" \
  --payload '{"command":"doctrine:migrations:migrate","noInteraction":true}' \
  /tmp/socialbulletin-migration-result.json
cat /tmp/socialbulletin-migration-result.json
