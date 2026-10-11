#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/app/public/products storage/app/private/imports
chown -R www-data:www-data storage/app/public storage/app/private

php artisan package:discover --ansi >/dev/null
php artisan config:cache
php artisan route:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
    if [ "${SEED_ADMIN:-true}" = "true" ]; then
        php artisan db:seed --class='Database\Seeders\AdminUserSeeder' --force
    fi
fi

exec "$@"
