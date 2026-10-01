#!/usr/bin/env bash
set -o errexit

# Render assigns the port at runtime; Apache has to be told to listen on it.
: "${PORT:=10000}"
sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/*.conf

# Skipped during the build (no APP_KEY yet), so build the package manifest here.
php artisan package:discover --ansi

# Migrations run at start rather than build time: the database isn't reachable
# during the image build. migrate --force is idempotent, so repeated starts are safe.
php artisan migrate --force

# Rebuild caches against the real runtime environment variables.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
