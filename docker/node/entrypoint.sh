#!/usr/bin/env sh
set -eu

WEB_DIR=/workspace/apps/web
mkdir -p /home/node/.npm /home/node/.cache/ms-playwright
chown -R node:node /home/node/.npm /home/node/.cache || true

if [ -f "$WEB_DIR/package.json" ] && [ ! -d "$WEB_DIR/node_modules" ]; then
  gosu node npm install --prefix "$WEB_DIR"
fi

exec gosu node "$@"
