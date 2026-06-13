FROM php:8.3-cli-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    gosu \
    openssl \
    python3-pip \
    python3-full \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get install -y symfony-cli \
    && rm -rf /var/lib/apt/lists/*

RUN pip3 install --break-system-packages DSLR

RUN groupadd -g ${GID} app && useradd -m -u ${UID} -g ${GID} app

COPY xdebug.ini /usr/local/etc/php/conf.d/docker-xdebug.ini

WORKDIR /var/www/app

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["symfony", "serve", "--listen-ip=0.0.0.0", "--port=8000", "--dir=/var/www/app/apps/api"]
