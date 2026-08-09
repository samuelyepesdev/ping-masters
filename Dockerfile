# syntax=docker/dockerfile:1

# ---- Frontend assets (Vite) ----
FROM node:20-alpine AS assets

# VITE_* values are baked into the built JS at build time, so they must be
# available here as a build arg (Railway injects service variables into
# `ARG`s declared with a matching name). Reverb's VITE_REVERB_* aren't needed
# since this deploy doesn't run a Reverb service (BROADCAST_CONNECTION=log).
ARG VITE_APP_NAME
ENV VITE_APP_NAME=$VITE_APP_NAME

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- PHP application ----
FROM php:8.3-cli-bookworm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

# Runs migrations on boot (safe for a single-instance deploy — move this to a
# separate release step first if you ever scale the web service beyond 1
# replica, to avoid concurrent migration races).
CMD php artisan migrate --force \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
