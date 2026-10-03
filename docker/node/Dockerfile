# Pinned to the @playwright/test version in apps/web/package.json.
FROM mcr.microsoft.com/playwright:v1.61.1-noble

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        python3-pip \
        libnss3-tools \
    && pip3 install --break-system-packages --no-cache-dir psycopg2-binary DSLR \
    && npm install -g npm@latest \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ARG UID=1000
ARG GID=1000
RUN if [ "$UID" != 0 ]; then \
        getent group "$GID" > /dev/null || groupadd -g "$GID" app; \
        existing_user="$(getent passwd "$UID" | cut -d: -f1)"; \
        if [ -n "$existing_user" ]; then \
            usermod -l app -g "$GID" -d /home/app -m "$existing_user"; \
        else \
            useradd -u "$UID" -g "$GID" -m app; \
        fi; \
    fi \
    && mkdir -p /var/cache/npm \
    && chown "$UID:$GID" /var/cache/npm

ENV npm_config_cache=/var/cache/npm

USER ${UID}:${GID}

WORKDIR /app/apps/web

ENTRYPOINT ["entrypoint.sh"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
