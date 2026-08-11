#!/bin/sh
set -eu

mkdir -p \
    storage/app/public/receipts \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

APP_PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${APP_PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${APP_PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan storage:link --force
php artisan config:cache
php artisan view:cache

exec "$@"
