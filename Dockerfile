FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
  libfreetype-dev \
  libjpeg62-turbo-dev \
  libpng-dev \
  libzip-dev \
  && docker-php-ext-install pdo_mysql \
  && docker-php-ext-install gd \
  && docker-php-ext-install zip \
  && a2enmod rewrite

COPY src/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
  && chmod -R 755 /var/www/html