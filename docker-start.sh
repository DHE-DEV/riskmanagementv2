#!/bin/bash

PROJECT_NAME=$(basename $(pwd))
DOMAIN="${PROJECT_NAME}.test"

echo "🚀 Starting $PROJECT_NAME at http://$DOMAIN"

if [ ! -f .env ]; then
    cp .env.docker .env
fi

docker-compose up --build -d

if ! mysqladmin ping -h127.0.0.1 -uappuser -p33!22!11 --silent 2>/dev/null; then
    echo "❌ MariaDB not reachable"
    exit 1
fi

docker-compose exec php composer install --no-dev --optimize-autoloader 2>/dev/null || true
docker-compose exec php php artisan key:generate --force 2>/dev/null || true
docker-compose exec php php artisan migrate --force 2>/dev/null || true
docker-compose exec php php artisan config:cache 2>/dev/null || true
docker-compose exec php php artisan route:cache 2>/dev/null || true
docker-compose exec php php artisan view:cache 2>/dev/null || true

docker-compose exec php chown -R www:www /var/www/html/storage 2>/dev/null || true
docker-compose exec php chown -R www:www /var/www/html/bootstrap/cache 2>/dev/null || true

echo "✅ $PROJECT_NAME ready at https://$DOMAIN"
