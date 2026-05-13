FROM php:8.2-fpm

ARG LOCAL_UID=1000
ARG LOCAL_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && groupmod -o -g "${LOCAL_GID}" www-data \
    && usermod -o -u "${LOCAL_UID}" -g www-data www-data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /srv/app

USER www-data
