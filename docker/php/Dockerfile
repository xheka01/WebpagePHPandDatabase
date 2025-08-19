FROM php:8.2-fpm-alpine

RUN apk add --no-cache icu-dev oniguruma-dev libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql intl gd zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { \
  echo "expose_php=0"; \
  echo "opcache.enable=1"; \
  echo "opcache.validate_timestamps=1"; \
} > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
USER www-data
