FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions required for Laravel 13
RUN apk add --no-cache     curl     libpng-dev     libxml2-dev     zip     unzip

RUN docker-php-ext-install pdo_mysql bcmath gd

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
