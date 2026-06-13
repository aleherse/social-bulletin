FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev gosu curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get install -y symfony-cli

WORKDIR /app

COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["symfony", "server:start", "--no-tls", "--port=8000", "--allow-http", "--listen-ip=0.0.0.0", "-d", "apps/api/public"]
