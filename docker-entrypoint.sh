#!/bin/bash
set -e

PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Generate an app key only if one isn't already set via env vars
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# NOTE: this project ships its full schema as mental_health_portal.sql
# (it already includes users/cache/jobs tables), so we do NOT run
# `artisan migrate` here — that would conflict with the dump.
# Import mental_health_portal.sql once via Railway's MySQL "Data" tab
# or the Railway CLI (see deployment instructions).

# Railway can re-enable extra Apache MPM modules at container startup,
# causing "More than one MPM loaded". Force only mpm_prefork right
# before Apache actually starts, so this survives that.
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

php artisan config:cache
php artisan route:cache

exec "$@"
