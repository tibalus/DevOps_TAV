#!/bin/bash
set -e

echo "Starting migration process..."

# Sauvegarder la clé de service GCP
# Vérifier si c'est du base64 ou du JSON direct
if echo "$AG_GCP_SA_KEY" | grep -q "^{"; then
    # C'est du JSON direct
    echo "$AG_GCP_SA_KEY" > /tmp/gcp-key.json
else
    # C'est du base64, on décode
    echo "$AG_GCP_SA_KEY" | base64 -d > /tmp/gcp-key.json
fi

export GOOGLE_APPLICATION_CREDENTIALS=/tmp/gcp-key.json

# Démarrer Cloud SQL Proxy en arrière-plan
echo "Starting Cloud SQL Proxy..."
echo "Connection name: ${INSTANCE_NAME}"
cloud_sql_proxy -instances=${INSTANCE_NAME}=tcp:3306 &
PROXY_PID=$!

# Attendre que le proxy soit prêt
echo "Waiting for Cloud SQL Proxy to be ready..."
sleep 10

# Vérifier la connexion à la base de données
echo "Testing database connection..."
echo "Connecting to: 127.0.0.1:3306 as user ${AG_DB_USER}"
until mysql -h 127.0.0.1 -P 3306 -u ${AG_DB_USER} -p${AG_DB_PASSWORD} -e "SELECT 1" > /dev/null 2>&1; do
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
