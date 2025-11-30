FROM php:8.2-fpm

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

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier composer.json et composer.lock en premier (pour le cache Docker)
COPY app/composer.json app/composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copier le reste de l'application
COPY app/ ./

# Optimiser Composer
RUN composer dump-autoload --optimize --no-dev

# Créer les dossiers nécessaires et définir les permissions
RUN mkdir -p var/cache var/log var/logs/crud && \
    chown -R www-data:www-data var/

# Copier la configuration nginx (sera modifiée au démarrage)
COPY nginx/nginx.conf /etc/nginx/conf.d/default.conf.template

## Create the start script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Use the port provided by Cloud Run (default: 8080)\n\
export PORT=${PORT:-8080}\n\
\n\
# Replace the port in nginx config\n\
sed "s/listen 80/listen ${PORT}/g" /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf\n\
\n\
# Clear and warmup Symfony cache if possible (don't crash the container if missing config)\n\
php bin/console cache:clear --no-warmup || true\n\
if [ -n "${APP_SECRET:-}" ]; then\n\
    echo "APP_SECRET is set — warming up cache"\n\
    php bin/console cache:warmup || true\n\
else\n\
    echo "APP_SECRET not set, skipping cache warmup"\n\
fi\n\
\n\
# Start PHP-FPM in background\n\
php-fpm -D\n\
\
# Start nginx in foreground\n\
exec nginx -g "daemon off;"\n\
' > /start.sh && chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]