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

# mod_php requires prefork. Some Debian extension packages can re-enable a
# threaded MPM after the base image has configured Apache.
for mpm_module in /etc/apache2/mods-enabled/mpm_*.load; do
    [ "${mpm_module}" = "/etc/apache2/mods-enabled/mpm_prefork.load" ] && continue
    rm -f "${mpm_module}" "${mpm_module%.load}.conf"
done
a2enmod mpm_prefork >/dev/null

php artisan storage:link --force
php artisan config:cache
php artisan view:cache

exec "$@"
