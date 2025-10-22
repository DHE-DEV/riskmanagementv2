#!/usr/bin/env bash
set -e

export COMPOSER_ALLOW_SUPERUSER=1

# 1) In den richtigen Ordner wechseln
if [ -n "$FORGE_RELEASE_PATH" ]; then
  cd "$FORGE_RELEASE_PATH"
  echo "🚀 Zero-Downtime Deployment: Working in release path"
elif [ -d "/home/forge/global-travel-monitor.de/current" ]; then
  cd "/home/forge/global-travel-monitor.de/current"
else
  cd "/home/forge/global-travel-monitor.de"
fi

echo "Working dir: $(pwd)"
test -f composer.json || { echo "composer.json fehlt hier!"; ls -la; exit 1; }

# 2) Composer Dependencies installieren
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# 3) Maintenance Mode nur bei NON-Zero-Downtime
if [ -z "$FORGE_RELEASE_PATH" ]; then
  echo "🔧 Enabling maintenance mode..."
  php artisan down || true
fi

# 4) Alle Caches löschen (wichtig für View-Updates!)
echo "🗑️  Clearing all caches..."
php artisan optimize:clear

# 5) API Key generieren (falls nötig)
php artisan key:generate --force || true

# 6) Datenbank-Migrationen ausführen
echo "🗄️  Running database migrations..."
php artisan migrate --force

# 7) Alle Optimierungen neu aufbauen
echo "⚡ Optimizing application..."
php artisan optimize

# 8) Storage Link erstellen (falls nicht vorhanden)
echo "🔗 Creating storage link..."
php artisan storage:link || true

# 9) Maintenance Mode nur bei NON-Zero-Downtime deaktivieren
if [ -z "$FORGE_RELEASE_PATH" ]; then
  echo "✅ Disabling maintenance mode..."
  php artisan up || true
fi

echo ""
echo "🎉 Deployment erfolgreich abgeschlossen!"
echo "📅 Deployed at: $(date)"
echo "📂 Release: ${FORGE_RELEASE_PATH:-current}"
