#!/bin/sh
set -e

if [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan optimize:clear

exec "$@"