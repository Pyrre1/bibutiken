FROM php:8.5-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite

COPY vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html