# syntax=docker/dockerfile:1

FROM php:8.5-fpm-alpine AS php-base

RUN apk add --no-cache \
        bash \
        ca-certificates \
        gettext \
        icu-libs \
        libpq \
        libzip \
        nginx \
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

FROM node:24-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

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
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/render/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/render/php.ini /usr/local/etc/php/conf.d/zz-render.ini
COPY docker/render/start.sh /usr/local/bin/render-start

RUN chmod +x /usr/local/bin/render-start \
    && chown -R www-data:www-data storage bootstrap/cache \
    && rm -f /etc/nginx/http.d/default.conf

EXPOSE 10000

CMD ["render-start"]
