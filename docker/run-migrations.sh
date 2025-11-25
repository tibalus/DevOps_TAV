#!/bin/bash
set -e

echo "Starting migration process..."

# Décoder et sauvegarder la clé de service GCP
echo "$AG_GCP_SA_KEY" | base64 -d > /tmp/gcp-key.json
export GOOGLE_APPLICATION_CREDENTIALS=/tmp/gcp-key.json

# Démarrer Cloud SQL Proxy en arrière-plan
echo "Starting Cloud SQL Proxy..."
cloud_sql_proxy -instances=${INSTANCE_CONNECTION_NAME}=tcp:3306 &
PROXY_PID=$!

# Attendre que le proxy soit prêt
echo "Waiting for Cloud SQL Proxy to be ready..."
sleep 5

# Vérifier la connexion à la base de données
echo "Testing database connection..."
until mysql -h 127.0.0.1 -P 3306 -u ${DB_USER} -p${DB_PASSWORD} -e "SELECT 1" > /dev/null 2>&1; do
    echo "Waiting for database connection..."
    sleep 2
done

echo "Database connection established!"

# Exécuter les migrations Symfony
echo "Running Symfony migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Migrations completed successfully!"

# Arrêter le proxy
kill $PROXY_PID

exit 0
