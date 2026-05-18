#!/bin/sh
set -e

APP_UID="${HOST_UID:-1000}"
APP_GID="${HOST_GID:-1000}"

# The Ubuntu-based Playwright image ships a preexisting `ubuntu` user/group
# at 1000:1000, so a same-GID/UID group/user may already exist under another
# name; reuse it (renamed to `app`) instead of failing on groupadd/useradd.
existing_group="$(getent group "$APP_GID" | cut -d: -f1)"
if [ -n "$existing_group" ]; then
    [ "$existing_group" = "app" ] || groupmod -n app "$existing_group"
else
    groupadd -g "$APP_GID" app
fi

existing_user="$(getent passwd "$APP_UID" | cut -d: -f1)"
if [ -n "$existing_user" ]; then
    [ "$existing_user" = "app" ] || usermod -l app -g app -d /home/app -m "$existing_user"
else
    useradd -u "$APP_UID" -g app -m app
fi

# Trust the mkcert root CA: system store for Node, NSS store for Chromium.
if [ -f /certs/rootCA.pem ]; then
    cp /certs/rootCA.pem /usr/local/share/ca-certificates/mkcert-root-ca.crt
    update-ca-certificates > /dev/null 2>&1

    gosu app mkdir -p /home/app/.pki/nssdb
    gosu app certutil -d sql:/home/app/.pki/nssdb -N --empty-password 2>/dev/null || true
    gosu app certutil -d sql:/home/app/.pki/nssdb -A -t "C,," -n mkcert-root-ca -i /certs/rootCA.pem
fi

# Serialise installs/builds across containers sharing the bind mount: the
# node service and `make build` may run this entrypoint concurrently.
(
    flock 9

    if [ -f /app/apps/web/package.json ] && [ ! -d /app/apps/web/node_modules ]; then
        echo "Installing web npm dependencies..."
        gosu app npm install --prefix /app/apps/web
    fi

    if [ -f /app/apps/web/package.json ] && [ ! -f /app/apps/web/dist/index.html ]; then
        echo "Building web frontend..."
        gosu app npm run build --prefix /app/apps/web
    fi
) 9>/app/apps/web/.install.lock
chown "$APP_UID:$APP_GID" /app/apps/web/.install.lock

exec gosu app "$@"
