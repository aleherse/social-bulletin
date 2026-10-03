#!/bin/sh
set -e

if [ "$1" = "php-fpm" ] && [ "$(id -u)" = "0" ]; then
    exec "$@" --allow-to-run-as-root
fi

exec "$@"
