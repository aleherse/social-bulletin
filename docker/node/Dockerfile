# Pinned to the @playwright/test version in apps/web/package.json.
FROM mcr.microsoft.com/playwright:v1.61.1-noble

# gosu drops from root (needed to trust the mkcert CA below) to the
# host-matching app user. ADR-0015: DSLR restores the database snapshot
# before each E2E scenario; libnss3-tools (certutil) lets the entrypoint
# trust the mkcert root CA in Chromium's NSS store.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        gosu \
        python3-pip \
        libnss3-tools \
    && pip3 install --break-system-packages --no-cache-dir psycopg2-binary DSLR \
    && npm install -g npm@latest \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /app/apps/web

ENTRYPOINT ["entrypoint.sh"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
