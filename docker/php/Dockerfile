FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    linux-headers \
    && docker-php-ext-install \
        pdo_mysql \
        mysqli \
        intl \
        zip \
        opcache \
        mbstring \
        pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

RUN mkdir -p var/cache var/log var/Data \
    && chown -R www-data:www-data .

USER www-data
