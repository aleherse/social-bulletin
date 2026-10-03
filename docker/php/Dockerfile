FROM public.ecr.aws/docker/library/php:8.3-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        python3 \
        python3-pip \
    && docker-php-ext-install pdo_pgsql intl \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    # ADR-0013: DSLR provides database snapshot/restore for the test suites.
    && pip3 install --break-system-packages --no-cache-dir psycopg2-binary DSLR \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=public.ecr.aws/docker/library/composer:2 /usr/bin/composer /usr/local/bin/composer

COPY xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ARG UID=1000
ARG GID=1000
RUN if [ "$UID" = 0 ]; then \
        sed -i 's/^user = .*/user = root/; s/^group = .*/group = root/' /usr/local/etc/php-fpm.d/www.conf; \
    else \
        getent group "$GID" > /dev/null || groupadd -g "$GID" app; \
        existing_user="$(getent passwd "$UID" | cut -d: -f1)"; \
        if [ -n "$existing_user" ]; then \
            usermod -l app -g "$GID" -d /home/app -m "$existing_user"; \
        else \
            useradd -u "$UID" -g "$GID" -m app; \
        fi; \
    fi \
    && mkdir -p /var/cache/composer \
    && chown "$UID:$GID" /var/cache/composer

ENV COMPOSER_CACHE_DIR=/var/cache/composer

USER ${UID}:${GID}

WORKDIR /app

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
