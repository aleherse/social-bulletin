#!/usr/bin/env sh
set -eu

mkdir -p apps/api/config/jwt docker/php/certs

if [ ! -f docker/php/certs/socialbulletin.pem ] || [ ! -f docker/php/certs/socialbulletin-key.pem ]; then
  openssl req -x509 -newkey rsa:2048 -sha256 -days 365 -nodes \
    -keyout docker/php/certs/socialbulletin-key.pem \
    -out docker/php/certs/socialbulletin.pem \
    -subj "/CN=*.dev.social.aleherse.com" \
    -addext "subjectAltName=DNS:api.dev.social.aleherse.com,DNS:app.dev.social.aleherse.com"
fi

if [ ! -f apps/api/config/jwt/private.pem ] || [ ! -f apps/api/config/jwt/public.pem ]; then
  docker compose run --rm api php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
fi
