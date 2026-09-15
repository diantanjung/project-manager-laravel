#!/usr/bin/env bash

set -euo pipefail

export PORT="${PORT:-10000}"
export RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"

if [ -n "${RENDER_EXTERNAL_HOSTNAME:-}" ] && [ -z "${APP_URL:-}" ]; then
    export APP_URL="https://${RENDER_EXTERNAL_HOSTNAME}"
fi

if [ -n "${APP_URL:-}" ] && [ -z "${ASSET_URL:-}" ]; then
    export ASSET_URL="${APP_URL}"
fi

envsubst '$PORT' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force || true

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

php artisan optimize:clear
php artisan optimize

php-fpm -D
nginx -g 'daemon off;'
