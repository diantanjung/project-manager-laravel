# syntax=docker/dockerfile:1

FROM php:8.4-cli-bookworm AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        autoconf \
        bash \
        ca-certificates \
        dpkg-dev \
        file \
        g++ \
        gcc \
        libc6-dev \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        make \
        pkg-config \
        re2c \
        unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j"$(nproc)" \
    bcmath \
    intl \
    opcache \
    pdo_pgsql \
    zip

WORKDIR /var/www/html

FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

FROM php-base AS production

COPY --from=vendor /var/www/html /var/www/html
COPY docker/render/php.ini /usr/local/etc/php/conf.d/zz-render.ini
COPY docker/render/start.sh /usr/local/bin/render-start

RUN chmod +x /usr/local/bin/render-start \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["render-start"]
