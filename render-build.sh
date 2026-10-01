#!/usr/bin/env bash
# Render build step. Fails the deploy on any error rather than shipping a half-built app.
set -o errexit

composer install --no-dev --optimize-autoloader

npm ci
npm run build

# Schema first, then the cached config/routes/views.
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
