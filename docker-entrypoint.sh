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

# Reference data, not sample data: the service catalogue drives the landing page,
# the booking flow and the admin settings screen, so an unseeded database looks
# broken. ServiceSeeder uses updateOrInsert keyed on name, so re-running it on
# every start adds no duplicates.
php artisan db:seed --class=ServiceSeeder --force

# Registration hardcodes role=customer, so there is no public path to an admin
# account and a fresh database has none. This provisions the first one from the
# environment, and no-ops when ADMIN_EMAIL/ADMIN_PASSWORD are unset.
php artisan apx:ensure-admin

# Rebuild caches against the real runtime environment variables.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
