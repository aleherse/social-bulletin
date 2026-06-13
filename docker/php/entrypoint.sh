#!/usr/bin/env bash
set -euo pipefail

USER_ID="${UID:-1000}"
GROUP_ID="${GID:-1000}"

if ! id -u appuser >/dev/null 2>&1; then
  groupadd -g "${GROUP_ID}" appuser || true
  useradd -u "${USER_ID}" -g "${GROUP_ID}" -m appuser || true
fi

mkdir -p /app/docker/php/certs /app/xdebug /app/apps/api/var
chown -R "${USER_ID}:${GROUP_ID}" /app/docker/php/certs /app/xdebug /app/apps/api/var 2>/dev/null || true

if [ -f /app/apps/api/composer.json ] && [ ! -d /app/apps/api/vendor ]; then
  gosu "${USER_ID}:${GROUP_ID}" composer install --working-dir=/app/apps/api
fi

if [ -f /app/packages/core/composer.json ] && [ ! -d /app/packages/core/vendor ]; then
  gosu "${USER_ID}:${GROUP_ID}" composer install --working-dir=/app/packages/core
fi

if [ -f /app/apps/api/composer.json ]; then
  if [ ! -f /app/apps/api/config/jwt/private.pem ]; then
    gosu "${USER_ID}:${GROUP_ID}" php /app/apps/api/bin/console lexik:jwt:generate-keypair --skip-if-exists 2>/dev/null || true
  fi
  gosu "${USER_ID}:${GROUP_ID}" symfony server:ca:install --no-interaction 2>/dev/null || true
  if [ -d /home/appuser/.symfony5/certs ]; then
    cp -n /home/appuser/.symfony5/certs/* /app/docker/php/certs/ 2>/dev/null || true
    chown -R "${USER_ID}:${GROUP_ID}" /app/docker/php/certs 2>/dev/null || true
  fi
fi

exec gosu "${USER_ID}:${GROUP_ID}" "$@"
