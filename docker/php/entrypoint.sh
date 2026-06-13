#!/usr/bin/env sh
set -eu

PROJECT_DIR=/workspace
API_DIR="$PROJECT_DIR/apps/api"
CORE_DIR="$PROJECT_DIR/packages/core"
CERT_DIR="$PROJECT_DIR/docker/php/certs"
JWT_DIR="$API_DIR/config/jwt"

mkdir -p "$CERT_DIR" "$JWT_DIR" /tmp/composer-cache /tmp/xdebug
chown -R app:app "$CERT_DIR" "$JWT_DIR" /tmp/composer-cache /tmp/xdebug || true

if [ -f "$API_DIR/composer.json" ] && [ ! -d "$API_DIR/vendor" ]; then
  gosu app composer install --working-dir="$API_DIR" --no-interaction
fi

if [ -f "$CORE_DIR/composer.json" ] && [ ! -d "$CORE_DIR/vendor" ]; then
  gosu app composer install --working-dir="$CORE_DIR" --no-interaction
fi

if [ -x "$API_DIR/bin/console" ]; then
  if [ ! -f "$JWT_DIR/private.pem" ] || [ ! -f "$JWT_DIR/public.pem" ]; then
    gosu app php "$API_DIR/bin/console" lexik:jwt:generate-keypair --skip-if-exists --no-interaction || true
  fi
fi

if [ ! -f "$CERT_DIR/socialbulletin.pem" ] || [ ! -f "$CERT_DIR/socialbulletin-key.pem" ]; then
  openssl req -x509 -newkey rsa:2048 -sha256 -days 365 -nodes \
    -keyout "$CERT_DIR/socialbulletin-key.pem" \
    -out "$CERT_DIR/socialbulletin.pem" \
    -subj "/CN=*.dev.social.aleherse.com" \
    -addext "subjectAltName=DNS:api.dev.social.aleherse.com,DNS:app.dev.social.aleherse.com"
  chown app:app "$CERT_DIR/socialbulletin.pem" "$CERT_DIR/socialbulletin-key.pem"
fi

symfony server:ca:install --no-interaction || true

exec gosu app "$@"
