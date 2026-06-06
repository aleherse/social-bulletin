FROM composer:2.8 AS composer

FROM php:8.4-fpm-alpine

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libpq-dev \
        linux-headers \
        make \
        postgresql-client \
        py3-pip \
        unzip \
    && docker-php-ext-install intl pdo_pgsql \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && pip install --break-system-packages DSLR

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /workspace/apps/api
