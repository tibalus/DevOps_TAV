FROM php:8.2-fpm

# 1. Installation des paquets
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    libzip-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql zip

# 2. Configuration PHP-FPM explicite pour écouter sur 127.0.0.1:9000
# (Par défaut, certaines images écoutent sur [::]:9000 ce qui peut poser souci avec 127.0.0.1)
RUN echo '[www]\nlisten = 127.0.0.1:9000\npm = dynamic\npm.max_children = 5\npm.start_servers = 2\npm.min_spare_servers = 1\npm.max_spare_servers = 3' > /usr/local/etc/php-fpm.d/zz-custom.conf

# 3. Installation Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# 4. Copie et installation des dépendances (Cache Docker optimisé)
COPY app/composer.json app/composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# 5. Copie du code
COPY app/ ./

# 6. Permissions
# Sur Cloud Run, le conteneur tourne souvent en root mais Nginx drop les privilèges vers www-data
RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data var/ && \
    chmod -R 775 var/

# 7. Configuration Nginx
COPY nginx/nginx.conf /etc/nginx/conf.d/default.conf.template

# 8. Script de démarrage robuste
# Nous utilisons single quotes '...' pour le echo afin de ne pas interpréter les variables $PORT lors du build
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Si PORT n est pas défini, utiliser 8080\n\
export PORT=${PORT:-8080}\n\
\n\
echo "Démarrage sur le port $PORT..."\n\
\n\
# Remplacement robuste du port dans la config Nginx\n\
# On cherche "listen [n'importe quel nombre]" et on remplace par "listen $PORT"\n\
sed -i "s/listen [0-9]*;/listen ${PORT};/g" /etc/nginx/conf.d/default.conf.template\n\
cp /etc/nginx/conf.d/default.conf.template /etc/nginx/conf.d/default.conf\n\
\n\
# Cache Symfony (Gestion des erreurs avec || true pour ne pas bloquer le boot)\n\
php bin/console cache:clear --no-warmup || echo "Echec cache:clear"\n\
php bin/console cache:warmup || echo "Echec cache:warmup"\n\
\n\
# Démarrer PHP-FPM en background (-D)\n\
php-fpm -D\n\
\n\
# Démarrer Nginx\n\
exec nginx -g "daemon off;"\n\
' > /start.sh && chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]