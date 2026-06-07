FROM php:8.4-fpm-alpine

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache $PHPIZE_DEPS \
        bash \
        git \
        icu-dev \
        libpq-dev \
        linux-headers \
        make \
        postgresql-client \
        py3-pip \
        unzip \
        autoconf \
    && docker-php-ext-install intl pdo_pgsql \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && pip3 install --break-system-packages DSLR psycopg2-binary

COPY docker/api/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /workspace/apps/api
