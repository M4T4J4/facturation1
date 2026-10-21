# syntax=docker/dockerfile:1

# Build frontend assets with Node.js
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json ./
RUN npm install
COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
RUN npm run build

# PHP + Apache runtime for Laravel 13
FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev libicu-dev unzip git \
    && docker-php-ext-install pdo_pgsql intl zip opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Point Apache to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Install Composer dependencies without development packages
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 80

# Migrations are applied at container start. APP_KEY and DB_* must be configured.
CMD ["sh", "-c", "php artisan config:clear && php artisan migrate --force && php artisan config:cache && apache2-foreground"]
