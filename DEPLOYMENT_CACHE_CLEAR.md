# Cache Clear Commands für Deployment

Nach dem Deployment oder nach Änderungen an .env-Variablen müssen folgende Befehle ausgeführt werden:

## Auf dem Server ausführen:

```bash
# 1. Config-Cache leeren (WICHTIG für .env Änderungen!)
php artisan config:clear

# 2. View-Cache leeren
php artisan view:clear

# 3. Route-Cache leeren (falls vorhanden)
php artisan route:clear

# 4. Optimierung neu aufbauen (Production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. OPcache leeren (falls PHP OPcache aktiv ist)
# Entweder:
# - Apache/Nginx neu starten
# - Oder eine cachetool verwenden
# - Oder php artisan optimize:clear
php artisan optimize:clear
```

## Für CUSTOMER_LOGIN_ENABLED=false:

1. In `/path/to/project/.env` hinzufügen/ändern:
   ```
   CUSTOMER_LOGIN_ENABLED=false
   CUSTOMER_REGISTRATION_ENABLED=false
   ```

2. Caches leeren:
   ```bash
   php artisan config:clear
   php artisan view:clear
   php artisan optimize:clear
   ```

3. Browser-Cache leeren (Strg+F5) oder Private/Inkognito-Fenster testen

## Überprüfung:

Testen Sie die Config-Werte:
```bash
php artisan tinker
>>> config('app.customer_login_enabled')
=> false
>>> config('app.customer_registration_enabled')
=> false
```

Falls immer noch `true` zurückgegeben wird, wurde die .env nicht korrekt geladen.

## Troubleshooting:

Falls die Buttons immer noch angezeigt werden:

1. **Prüfen Sie die .env Datei:**
   ```bash
   grep CUSTOMER_ .env
   ```

2. **Config-Werte überprüfen:**
   ```bash
   php artisan config:show app.customer_login_enabled
   php artisan config:show app.customer_registration_enabled
   ```

3. **Cache wirklich löschen:**
   ```bash
   rm -rf bootstrap/cache/*.php
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

4. **Webserver neu starten:**
   ```bash
   sudo systemctl restart apache2
   # oder
   sudo systemctl restart nginx
   sudo systemctl restart php8.3-fpm
   ```
