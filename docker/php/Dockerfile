FROM php:8.5-apache

RUN apt-get update && apt-get install -y unzip \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html