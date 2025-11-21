# Fix SSO Logs 500 Error

## Problem
`https://stage.global-travel-monitor.eu/admin/sso-logs` gibt 500 Server Error zurück.

## Mögliche Ursachen & Lösungen

### 1. Migration nicht ausgeführt (Wahrscheinlichste Ursache)

Die `sso_logs` Tabelle existiert noch nicht in der Datenbank.

**Lösung:**
```bash
# Via SSH auf dem Server:
ssh forge@stage.global-travel-monitor.eu
cd /home/forge/stage.global-travel-monitor.eu

# Migration ausführen
php artisan migrate

# Oder nur diese spezifische Migration:
php artisan migrate --path=database/migrations/2025_11_21_112201_create_sso_logs_table.php
```

**Prüfen ob Tabelle existiert:**
```bash
php artisan tinker
>>> Schema::hasTable('sso_logs');
# Sollte true zurückgeben
```

---

### 2. Cache nicht geleert

Routes, Config oder Views sind im Cache und erkennen neue Dateien nicht.

**Lösung:**
```bash
ssh forge@stage.global-travel-monitor.eu
cd /home/forge/stage.global-travel-monitor.eu

# Alle Caches leeren
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optional: OPcache reset (falls verfügbar)
php artisan optimize:clear
```

---

### 3. Composer Autoload nicht aktualisiert

Neue Klassen (SsoLog Model, SsoLogService, SsoLogController) sind nicht im Autoloader.

**Lösung:**
```bash
ssh forge@stage.global-travel-monitor.eu
cd /home/forge/stage.global-travel-monitor.eu

# Composer autoload neu generieren
composer dump-autoload

# Falls composer nicht gefunden wird:
/usr/local/bin/composer dump-autoload
```

---

### 4. Service Provider nicht geladen

Der SsoLogService ist nicht registriert.

**Prüfen:**
```bash
php artisan tinker
>>> app()->make(\App\Services\SsoLogService::class);
# Sollte Service-Instanz zurückgeben
```

**Lösung (falls Fehler):**
- Prüfen ob `app/Providers/AppServiceProvider.php` die Änderungen hat
- Config Cache leeren: `php artisan config:clear`

---

### 5. Logs prüfen

**Laravel Log:**
```bash
ssh forge@stage.global-travel-monitor.eu
cd /home/forge/stage.global-travel-monitor.eu

# Letzte Fehler anzeigen
tail -50 storage/logs/laravel.log
```

**Nginx Error Log:**
```bash
sudo tail -50 /var/log/nginx/error.log
```

**PHP-FPM Log:**
```bash
sudo tail -50 /var/log/php8.2-fpm.log
# oder php8.1-fpm.log, abhängig von PHP Version
```

---

### 6. Deployment prüfen

**Via Laravel Forge:**
1. Gehe zu Forge Dashboard
2. Wähle Site: `stage.global-travel-monitor.eu`
3. Klicke auf "Deployments" Tab
4. Prüfe ob letzter Deploy erfolgreich war
5. Falls fehlgeschlagen: Deployment erneut ausführen

**Deploy Script überprüfen:**
Stelle sicher, dass das Deployment Script folgendes enthält:
```bash
cd /home/forge/stage.global-travel-monitor.eu
git pull origin staging
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

### 7. Berechtigungen prüfen

**Prüfen:**
```bash
ssh forge@stage.global-travel-monitor.eu
cd /home/forge/stage.global-travel-monitor.eu

# Storage Ordner sollte schreibbar sein
ls -la storage/
ls -la storage/logs/

# Falls Berechtigungen falsch:
sudo chown -R forge:forge storage/
sudo chmod -R 775 storage/
```

---

## Schnell-Fix Kommando

Führe alle wichtigen Schritte auf einmal aus:

```bash
ssh forge@stage.global-travel-monitor.eu
cd /home/forge/stage.global-travel-monitor.eu

# All-in-One Fix
composer dump-autoload && \
php artisan migrate --force && \
php artisan config:clear && \
php artisan route:clear && \
php artisan view:clear && \
php artisan cache:clear && \
echo "✓ Alle Schritte ausgeführt"
```

---

## Debug-Modus aktivieren (Temporär)

Um mehr Details über den Fehler zu sehen:

**Via SSH:**
```bash
ssh forge@stage.global-travel-monitor.eu
cd /home/forge/stage.global-travel-monitor.eu

# .env bearbeiten
nano .env

# Ändere:
APP_DEBUG=true

# Speichern und schließen (Ctrl+X, Y, Enter)
```

**Wichtig:** Nach dem Debugging wieder deaktivieren:
```bash
APP_DEBUG=false
```

**Via Laravel Forge:**
1. Gehe zu Site → Environment
2. Finde `APP_DEBUG=false`
3. Ändere temporär zu `APP_DEBUG=true`
4. Nach Debugging zurück zu `false`

---

## Verifizierung

Nach dem Fix teste folgende URLs:

1. **Route List prüfen:**
```bash
php artisan route:list | grep sso-logs
```
Sollte zeigen:
```
GET|HEAD  admin/sso-logs
GET|HEAD  admin/sso-logs/stats
GET|HEAD  admin/sso-logs/{requestId}
```

2. **Tabelle prüfen:**
```bash
php artisan tinker
>>> \App\Models\SsoLog::count();
# Sollte 0 zurückgeben (oder Anzahl der Logs)
```

3. **Controller prüfen:**
```bash
php artisan route:list --path=admin/sso-logs
```

4. **Browser testen:**
- `https://stage.global-travel-monitor.eu/admin/sso-logs`

---

## Häufigste Lösung

In 90% der Fälle hilft:

```bash
ssh forge@stage.global-travel-monitor.eu
cd /home/forge/stage.global-travel-monitor.eu
php artisan migrate --force
php artisan cache:clear
```

---

## Support

Falls der Fehler weiterhin besteht:

1. **Log-Ausgabe holen:**
```bash
tail -100 storage/logs/laravel.log
```

2. **Fehlermeldung kopieren** und analysieren

3. **Prüfen:**
- Welche PHP Version? `php -v`
- Welche Laravel Version? `php artisan --version`
- Ist Forge deploy erfolgreich?

---

**Erstellt:** 2025-11-21
**Version:** 1.0
