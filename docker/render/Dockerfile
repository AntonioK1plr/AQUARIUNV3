FROM node:24-alpine AS frontend
WORKDIR /src
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx gettext icu-libs libjpeg-turbo libpng libzip postgresql-libs freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS freetype-dev icu-dev libjpeg-turbo-dev libpng-dev libzip-dev postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl opcache pdo_pgsql zip \
    && apk del .build-deps \
    && mkdir -p /run/nginx /var/lib/nginx/tmp /var/log/nginx

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY . .
COPY --from=frontend /src/public/build ./public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-aquarium.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-aquarium.conf
COPY docker/render/nginx.conf.template /etc/nginx/http.d/default.conf.template
COPY docker/render/start.sh /usr/local/bin/aquarium-start

RUN composer dump-autoload --no-dev --no-scripts --optimize \
    && chmod +x /usr/local/bin/aquarium-start \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000
CMD ["/usr/local/bin/aquarium-start"]
