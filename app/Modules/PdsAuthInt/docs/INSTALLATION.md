# Installation Instructions / Installationsanleitung

## Deutsch

### 1. JWT-Bibliothek installieren (beide Services)

Die `lcobucci/jwt` Bibliothek wird für die JWT-Token-Erstellung und -Validierung benötigt.

**Service 1 (pds-homepage):**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage
composer require lcobucci/jwt lcobucci/clock
```

**Service 2 (riskmanagementv2):**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
composer require lcobucci/jwt lcobucci/clock
```

### 2. RSA-Schlüssel generieren

Folgen Sie den Anweisungen in `KEY_GENERATION.md`.

### 3. Service Provider registriert (bereits erledigt ✓)

Die Service Provider wurden bereits in beiden `AppServiceProvider.php` Dateien registriert.

### 4. Migration ausführen (nur Service 2)

```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
php artisan migrate
```

### 5. Umgebungsvariablen konfigurieren

**Service 1 (pds-homepage) - .env:**
```env
# SSO Configuration
SSO_SERVICE2_EXCHANGE_URL=http://127.0.0.1:8000/api/pdsauthint/exchange
SSO_SERVICE2_LOGIN_URL=http://127.0.0.1:8000/pdsauthint/login
```

**Service 2 (riskmanagementv2) - .env:**
```env
# Keine speziellen SSO-Umgebungsvariablen erforderlich
# Die Konfiguration verwendet Standard-Pfade
```

### 6. Cache leeren (beide Services)

**Service 1:**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

**Service 2:**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 7. Routen überprüfen

**Service 1:**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage
php artisan route:list | grep pdsauthint
```

Erwartete Ausgabe:
```
POST      pdsauthint/redirect .............. pdsauthint.redirect › IdPController@redirectToServiceProvider
```

**Service 2:**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
php artisan route:list | grep pdsauthint
```

Erwartete Ausgabe:
```
GET|HEAD  pdsauthint/login ................. pdsauthint.login › SPController@handleLogin
POST      api/pdsauthint/exchange .......... pdsauthint.api.exchange › SPController@exchangeToken
```

### 8. .gitignore aktualisieren (beide Services)

Fügen Sie folgende Zeile zur `.gitignore` hinzu:
```
storage/app/sso/*.key
```

---

## English

### 1. Install JWT Library (both services)

The `lcobucci/jwt` library is required for JWT token creation and validation.

**Service 1 (pds-homepage):**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage
composer require lcobucci/jwt lcobucci/clock
```

**Service 2 (riskmanagementv2):**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
composer require lcobucci/jwt lcobucci/clock
```

### 2. Generate RSA Keys

Follow the instructions in `KEY_GENERATION.md`.

### 3. Service Provider Registered (already done ✓)

The service providers have already been registered in both `AppServiceProvider.php` files.

### 4. Run Migration (Service 2 only)

```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
php artisan migrate
```

### 5. Configure Environment Variables

**Service 1 (pds-homepage) - .env:**
```env
# SSO Configuration
SSO_SERVICE2_EXCHANGE_URL=http://127.0.0.1:8000/api/pdsauthint/exchange
SSO_SERVICE2_LOGIN_URL=http://127.0.0.1:8000/pdsauthint/login
```

**Service 2 (riskmanagementv2) - .env:**
```env
# No special SSO environment variables required
# Configuration uses default paths
```

### 6. Clear Cache (both services)

**Service 1:**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

**Service 2:**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 7. Verify Routes

**Service 1:**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage
php artisan route:list | grep pdsauthint
```

Expected output:
```
POST      pdsauthint/redirect .............. pdsauthint.redirect › IdPController@redirectToServiceProvider
```

**Service 2:**
```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
php artisan route:list | grep pdsauthint
```

Expected output:
```
GET|HEAD  pdsauthint/login ................. pdsauthint.login › SPController@handleLogin
POST      api/pdsauthint/exchange .......... pdsauthint.api.exchange › SPController@exchangeToken
```

### 8. Update .gitignore (both services)

Add the following line to `.gitignore`:
```
storage/app/sso/*.key
```

---

## Quick Installation Script / Schnellinstallations-Skript

You can run this bash script to automate most of the installation:

```bash
#!/bin/bash

# Service 1 (pds-homepage)
echo "Installing dependencies for Service 1..."
cd /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage
composer require lcobucci/jwt lcobucci/clock
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Service 2 (riskmanagementv2)
echo "Installing dependencies for Service 2..."
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
composer require lcobucci/jwt lcobucci/clock
php artisan migrate
php artisan config:clear
php artisan route:clear
php artisan cache:clear

echo "Installation completed!"
echo "Don't forget to:"
echo "1. Generate RSA keys (see KEY_GENERATION.md)"
echo "2. Configure .env files (see INSTALLATION.md)"
echo "3. Add storage/app/sso/*.key to .gitignore"
```
