# ─── Stage 1: build front-end assets ──────────────────────────────────────────
FROM node:20-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

# tailwind/postcss configs are required: resources/css/app.css is built from
# @tailwind directives, and Tailwind's content globs point at resources/views.
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources

RUN npm run build          # outputs to public/build

# ─── Stage 2: PHP runtime ─────────────────────────────────────────────────────
# 8.4 because composer.lock pins Symfony 8.x, which requires php >= 8.4.
FROM php:8.4-apache

# Render provides no PHP runtime, so the environment is built here.
# libpq-dev is what pdo_pgsql compiles against; the rest are Laravel's needs.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libonig-dev zip unzip git \
    && docker-php-ext-install pdo pdo_pgsql mbstring bcmath zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Laravel serves from public/, not the project root.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

# Laravel routes through public/.htaccess, which Apache ignores unless overrides
# are allowed. Without this every route 404s while static files still serve fine.
RUN { \
      echo '<Directory /var/www/html/public>'; \
      echo '    Options -Indexes +FollowSymLinks'; \
      echo '    AllowOverride All'; \
      echo '    Require all granted'; \
      echo '</Directory>'; \
    } > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy dependency manifests first so Docker caches the install layer.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=assets /app/public/build ./public/build

# Laravel needs these to exist and be writable; .dockerignore strips their
# contents, so recreate them rather than relying on the build context.
RUN mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && composer dump-autoload --optimize --no-scripts \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
