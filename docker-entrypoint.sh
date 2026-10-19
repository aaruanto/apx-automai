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

# The three PMS packages and the service intervals that recommend them. Both
# seeders key on name with updateOrCreate, so repeated boots change nothing;
# the package seeder also refuses to create a bundle whose services are missing
# or whose price is not actually a saving, so a half-seeded catalogue is safe.
#
# Run here rather than by hand because Render's free tier has no shell: without
# this there is no way to get reference data onto production at all.
php artisan db:seed --class=MaintenancePackageSeeder --force
php artisan db:seed --class=MaintenanceRuleSeeder --force

# Registration hardcodes role=customer, so there is no public path to an admin
# account and a fresh database has none. This provisions the first one from the
# environment, and no-ops when ADMIN_EMAIL/ADMIN_PASSWORD are unset.
php artisan apx:ensure-admin

# Rebuild caches against the real runtime environment variables.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
