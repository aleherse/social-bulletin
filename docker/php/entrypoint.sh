#!/bin/bash
set -e

CERT_DIR=/var/www/app/docker/certs
FRONT_DOMAIN="${DEV_FRONT_URL_DOMAIN:-app.dev.social.aleherse.com}"

# Generate TLS certificates for both API and frontend with mkcert
if [ ! -f "${CERT_DIR}/rootCA.pem" ]; then
    mkdir -p "${CERT_DIR}"
    mkcert -install

    if [ ! -f "${CERT_DIR}/web.pem" ]; then
        mkcert -cert-file "${CERT_DIR}/web.pem" -key-file "${CERT_DIR}/web-key.pem" "${FRONT_DOMAIN}"
    fi

    cp "$(mkcert -CAROOT)/rootCA*" "${CERT_DIR}/"
    cp "$(mkcert -CAROOT)/rootCA*" "/root/.config/symfony-cli/certs/"
    symfony server:ca:install
    chown -R app:app "${CERT_DIR}"
fi

# Generate JWT keypair
JWT_DIR=/var/www/app/apps/api/config/jwt
if [ -f /var/www/app/apps/api/bin/console ] && [ ! -f "${JWT_DIR}/private.pem" ]; then
    mkdir -p "${JWT_DIR}"
    gosu app php /var/www/app/apps/api/bin/console \
        lexik:jwt:generate-keypair \
        --skip-if-exists \
        --env=dev 2>/dev/null || true
fi

# Install Composer dependencies
if [ -f /var/www/app/apps/api/composer.json ] && [ ! -d /var/www/app/apps/api/vendor ]; then
    cd /var/www/app/apps/api && gosu app composer install --no-interaction
fi

exec gosu app "$@"
