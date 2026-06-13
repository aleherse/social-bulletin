FROM php:8.3-cli-bookworm

ARG USER_ID=1000
ARG GROUP_ID=1000

ENV COMPOSER_HOME=/tmp/composer-cache \
    SYMFONY_ALLOW_APPDEV=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        gosu \
        libicu-dev \
        libpq-dev \
        unzip \
        openssl \
    && docker-php-ext-install intl pdo_pgsql \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -fsSL https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony \
    && groupadd -g ${GROUP_ID} app \
    && useradd -m -u ${USER_ID} -g app app \
    && mkdir -p /workspace /tmp/composer-cache /tmp/xdebug \
    && chown -R app:app /workspace /tmp/composer-cache /tmp/xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY docker/php/entrypoint.sh /usr/local/bin/socialbulletin-php-entrypoint
RUN chmod +x /usr/local/bin/socialbulletin-php-entrypoint

WORKDIR /workspace/apps/api
ENTRYPOINT ["socialbulletin-php-entrypoint"]
CMD ["symfony", "server:start", "--allow-http", "--no-tls", "--listen-ip=0.0.0.0", "--port=8000"]
