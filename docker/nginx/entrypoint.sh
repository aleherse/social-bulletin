#!/bin/sh
set -e

CERT_DIR=/certs
CERT_FILE="$CERT_DIR/cert.pem"
KEY_FILE="$CERT_DIR/cert-key.pem"

# Persist the mkcert CA in the bind-mounted certs dir so container rebuilds
# reuse the same root CA instead of minting a new one (which would silently
# break the trust chain of already-issued certificates).
export CAROOT="$CERT_DIR"

mkdir -p "$CERT_DIR"

if [ ! -f "$CERT_DIR/rootCA.pem" ]; then
    mkcert -install
fi

# Reissue when missing OR when the cert no longer chains to the current CA.
if [ ! -f "$CERT_FILE" ] || [ ! -f "$KEY_FILE" ] \
    || ! openssl verify -CAfile "$CERT_DIR/rootCA.pem" "$CERT_FILE" > /dev/null 2>&1; then
    mkcert \
        -cert-file "$CERT_FILE" \
        -key-file "$KEY_FILE" \
        social.aleherse.com "*.social.aleherse.com" dev.api.social.aleherse.com dev.app.social.aleherse.com localhost 127.0.0.1
fi

chown -R "${HOST_UID:-1000}:${HOST_GID:-1000}" "$CERT_DIR"
chmod 644 "$CERT_FILE" "$CERT_DIR/rootCA.pem"
chmod 600 "$KEY_FILE" "$CERT_DIR/rootCA-key.pem"

exec "$@"
