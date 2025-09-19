FROM php:8.2-fpm

# Installer Nginx, Composer et dépendances utiles
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Configurer Nginx
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

# Démarrage : PHP-FPM + Nginx dans le même conteneur
CMD composer update && service nginx start && php-fpm