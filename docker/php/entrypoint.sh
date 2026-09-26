#!/bin/sh
set -e

APP_UID="${HOST_UID:-1000}"
APP_GID="${HOST_GID:-1000}"

if ! getent group app >/dev/null 2>&1; then
    groupadd -g "$APP_GID" app
fi
if ! id app >/dev/null 2>&1; then
    useradd -u "$APP_UID" -g app -m app
fi

if [ "$1" = "php-fpm" ]; then
    # php-fpm master must start as root; pool workers drop to the app user.
    sed -i "s/^user = .*/user = app/; s/^group = .*/group = app/" /usr/local/etc/php-fpm.d/www.conf
    exec "$@"
fi

exec gosu app "$@"
