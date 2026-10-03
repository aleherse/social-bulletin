#!/bin/sh
set -e

# Node trusts the mkcert root CA through NODE_EXTRA_CA_CERTS; Chromium only
# through the user's NSS store, which needs the CA nginx generates at runtime.
if [ -f /certs/rootCA.pem ]; then
    mkdir -p "$HOME/.pki/nssdb"
    certutil -d "sql:$HOME/.pki/nssdb" -N --empty-password 2>/dev/null || true
    certutil -d "sql:$HOME/.pki/nssdb" -A -t "C,," -n mkcert-root-ca -i /certs/rootCA.pem
fi

exec "$@"
