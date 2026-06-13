#!/bin/bash
set -e

CERT_DIR=/var/www/app/docker/php/certs
SYMFONY_CERTS_DIR="/home/app/.symfony5/certs"

# Install Symfony CA and generate Vite domain cert
if [ ! -f "${SYMFONY_CERTS_DIR}/default.p12" ]; then
    gosu app symfony server:ca:install --no-interaction 2>/dev/null || true

    if [ -f "${SYMFONY_CERTS_DIR}/default.p12" ]; then
        mkdir -p "${CERT_DIR}"

        P12="${SYMFONY_CERTS_DIR}/default.p12"

        # Export CA cert (public)
        gosu app openssl pkcs12 \
            -in "${P12}" \
            -out "${CERT_DIR}/ca.crt" \
            -nokeys \
            -passin pass: \
            -legacy 2>/dev/null \
        || gosu app openssl pkcs12 \
            -in "${P12}" \
            -out "${CERT_DIR}/ca.crt" \
            -nokeys \
            -passin pass: 2>/dev/null \
        || true

        # Export CA key
        gosu app openssl pkcs12 \
            -in "${P12}" \
            -out "${CERT_DIR}/ca.key" \
            -nocerts \
            -nodes \
            -passin pass: \
            -legacy 2>/dev/null \
        || gosu app openssl pkcs12 \
            -in "${P12}" \
            -out "${CERT_DIR}/ca.key" \
            -nocerts \
            -nodes \
            -passin pass: 2>/dev/null \
        || true

        # Generate domain cert for Vite frontend hostname
        FRONT_DOMAIN="${DEV_FRONT_URL_DOMAIN:-app.dev.social.aleherse.com}"

        if [ -f "${CERT_DIR}/ca.crt" ] && [ -f "${CERT_DIR}/ca.key" ]; then
            gosu app openssl req -newkey rsa:2048 -nodes \
                -keyout "${CERT_DIR}/vite.key" \
                -out "${CERT_DIR}/vite.csr" \
                -subj "/CN=${FRONT_DOMAIN}" 2>/dev/null

            gosu app openssl x509 -req \
                -in "${CERT_DIR}/vite.csr" \
                -CA "${CERT_DIR}/ca.crt" \
                -CAkey "${CERT_DIR}/ca.key" \
                -CAcreateserial \
                -out "${CERT_DIR}/vite.crt" \
                -days 3650 \
                -extfile <(printf "subjectAltName=DNS:%s" "${FRONT_DOMAIN}") 2>/dev/null

            rm -f "${CERT_DIR}/vite.csr" "${CERT_DIR}/ca.srl"
            chown -R app:app "${CERT_DIR}"
        fi
    fi
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
