FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    zip \
    unzip 

RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite
#RUN a2enmod ssl

#RUN mkdir -p /var/www/
WORKDIR /var/www

