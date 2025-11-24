FROM php:8.2-fpm as build

# Installer dépendances, nginx et outils utiles
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    curl \
    libonig-dev \
    libzip-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Installer extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql zip

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Configurer Nginx
COPY nginx/nginx.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html
# Démarrage : PHP-FPM + Nginx dans le même conteneur oui
CMD mkdir -p /var/www/html/var/logs/crud && \
    composer update && \
    service nginx start && \
    php-fpm

