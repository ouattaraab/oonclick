#!/bin/bash
# Script de déploiement oon.click sur Hostinger
# Usage : ./deploy.sh [production|staging]

set -e

ENV=${1:-production}
echo "Déploiement en cours : $ENV"

# 1. Pull dernière version
git pull origin main

# 2. Installation des dépendances (no-dev en production)
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Optimisations Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 4. Migrations (avec confirmation en production)
if [ "$ENV" = "production" ]; then
    php artisan migrate --force
else
    php artisan migrate
fi

# 5. Seeder (uniquement si première installation)
# php artisan db:seed --class=PlatformConfigSeeder --force

# 6. Storage link
php artisan storage:link

# 7. Clear caches de fichiers périmés
php artisan cache:clear
php artisan queue:restart

echo "Déploiement terminé !"
