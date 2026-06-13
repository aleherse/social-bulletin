#!/bin/bash
set -e

WEB_DIR=/var/www/app/apps/web

if [ -f "${WEB_DIR}/package.json" ] && [ ! -d "${WEB_DIR}/node_modules" ]; then
    cd "${WEB_DIR}" && npm install
fi

exec "$@"
