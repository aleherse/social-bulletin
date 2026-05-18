FROM php:8.3-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        gosu \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        python3 \
        python3-pip \
    && docker-php-ext-install pdo_pgsql intl \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    # ADR-0015: DSLR provides database snapshot/restore for the test suites.
    && pip3 install --break-system-packages --no-cache-dir psycopg2-binary DSLR \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /app

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
