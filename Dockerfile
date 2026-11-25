FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates libpq-dev libsqlite3-dev unzip \
    && docker-php-ext-install pdo_mysql pdo_pgsql pdo_sqlite \
    && a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' \
       > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY . .
COPY --from=frontend /app/public/build ./public/build
RUN composer dump-autoload --no-dev --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000
CMD ["apache2-foreground"]