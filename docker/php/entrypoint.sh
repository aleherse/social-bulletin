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

# Serialise Composer installs across containers sharing the bind mount: the
# php service and `make build` may run this entrypoint concurrently.
(
    flock 9

    if [ -f /app/apps/api/composer.json ] && [ ! -d /app/apps/api/vendor ]; then
        echo "Installing API Composer dependencies..."
        gosu app composer install --working-dir=/app/apps/api --no-interaction
    fi

    if [ -f /app/packages/core/composer.json ] && [ ! -d /app/packages/core/vendor ]; then
        echo "Installing core package Composer dependencies..."
        gosu app composer install --working-dir=/app/packages/core --no-interaction
    fi
) 9>/app/.install.lock
chown "$APP_UID:$APP_GID" /app/.install.lock

if [ "$1" = "php-fpm" ]; then
    # php-fpm master must start as root; pool workers drop to the app user.
    sed -i "s/^user = .*/user = app/; s/^group = .*/group = app/" /usr/local/etc/php-fpm.d/www.conf
    exec "$@"
fi

exec gosu app "$@"
