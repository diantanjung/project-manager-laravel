# syntax=docker/dockerfile:1

FROM php:8.5-cli-alpine AS php-base

RUN apk add --no-cache \
        bash \
        ca-certificates \
        icu-libs \
        libpq \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        postgresql-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        opcache \
        pdo_pgsql \
        zip \
    && apk del .build-deps

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
