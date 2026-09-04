FROM php:8.2-apache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite dir

RUN apt-get update && apt-get install -y unzip git && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_mysql

COPY composer.json ./
RUN composer install --no-dev --no-interaction --prefer-dist

COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
