FROM php:8.2-apache

RUN apt-get update && apt-get install -y libsqlite3-dev && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_sqlite

WORKDIR /var/www/html

COPY . .

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod 755 /var/www/html/data

EXPOSE 80
