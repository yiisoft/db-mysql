FROM php:7.3-cli

RUN export DEBIAN_FRONTEND=noninteractive

RUN apt update
RUN apt install -y curl gnupg2 git zip
RUN docker-php-ext-install pdo_mysql mysqli pcntl
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/
