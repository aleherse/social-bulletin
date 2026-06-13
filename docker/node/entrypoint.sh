#!/usr/bin/env bash
set -euo pipefail

USER_ID="${UID:-1000}"
GROUP_ID="${GID:-1000}"

if ! id -u appuser >/dev/null 2>&1; then
  groupadd -g "${GROUP_ID}" appuser || true
  useradd -u "${USER_ID}" -g "${GROUP_ID}" -m appuser || true
fi

if [ -f /app/apps/web/package.json ] && [ ! -d /app/apps/web/node_modules ]; then
  gosu "${USER_ID}:${GROUP_ID}" npm install --prefix /app/apps/web
fi

exec gosu "${USER_ID}:${GROUP_ID}" "$@"
