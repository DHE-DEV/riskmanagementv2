# PdsAuthInt Module - Service Provider (SP)

## Übersicht / Overview

Dieses Modul implementiert die Service Provider (SP) Funktionalität für das SSO-System zwischen pds-homepage und riskmanagementv2.

This module implements the Service Provider (SP) functionality for the SSO system between pds-homepage and riskmanagementv2.

---

## Rolle / Role

**Service Provider (SP)** - Empfängt und verifiziert Authentifizierungen vom Identity Provider und führt Just-in-Time (JIT) Provisioning durch.

**Service Provider (SP)** - Receives and verifies authentication from Identity Provider and performs Just-in-Time (JIT) provisioning.

---

## Verzeichnisstruktur / Directory Structure

```
app/Modules/PdsAuthInt/
├── Providers/
│   └── PdsAuthIntServiceProvider.php    # Service Provider Registration
├── Http/
│   └── Controllers/
│       └── SPController.php             # Service Provider Controller
├── routes/
│   ├── api.php                          # API Routes
│   └── web.php                          # Web Routes
├── config/
│   └── pdsauthint.php                   # Module Configuration
├── docs/                                # Documentation
│   ├── README_SSO.md                    # Complete System Documentation
│   ├── INSTALLATION.md                  # Installation Guide
│   ├── KEY_GENERATION.md                # RSA Key Setup
│   └── SSO_IMPLEMENTATION_SUMMARY.md    # Implementation Summary
└── README.md                            # This file
```

---

## Endpunkte / Endpoints

### 1. POST /api/pdsauthint/exchange (API)

**Beschreibung / Description:**
Empfängt JWT vom IdP, validiert es und generiert ein One-Time Token (OTT).
Receives JWT from IdP, validates it, and generates a One-Time Token (OTT).

**Request:**
```http
POST /api/pdsauthint/exchange HTTP/1.1
Host: riskmanagementv2.example.com
Content-Type: application/json

{
    "jwt": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Response (Erfolg / Success):**
```json
{
    "success": true,
    "ott": "ABC123...",
    "redirect_url": "http://riskmanagementv2.example.com/pdsauthint/login?ott=ABC123..."
}
```

**Response (Fehler / Error):**
```json
{
    "error": "Invalid token",
    "message": "JWT signature validation failed"
}
```

### 2. GET /pdsauthint/login (Web)

**Beschreibung / Description:**
Führt JIT-Provisioning durch und loggt den Kunden ein.
Performs JIT provisioning and logs in the customer.

**Request:**
```http
GET /pdsauthint/login?ott=ABC123... HTTP/1.1
Host: riskmanagementv2.example.com
```

**Response (Erfolg / Success):**
```
HTTP/1.1 302 Found
Location: /customer/dashboard
Set-Cookie: laravel_session=...
```

**Response (Fehler / Error):**
```html
<div class="alert alert-danger">
    Login-Link ungültig oder abgelaufen.
</div>
```

---

## Konfiguration / Configuration

### Datei / File: `config/pdsauthint.php`

```php
return [
    'role' => 'sp',

    // Public Key für JWT-Validierung
    // Nutzt PASSPORT_PUBLIC_KEY von Service 1
    'public_key' => env('SSO_PUBLIC_KEY') ?: env('PASSPORT_PUBLIC_KEY') ?: storage_path('app/sso/sso-public.key'),

    // Verwende Umgebungsvariablen
    'use_env_keys' => (bool) env('SSO_USE_ENV_KEYS', true),

    // JWT Settings
    'jwt_issuer' => 'pds-homepage',
    'jwt_audience' => 'riskmanagementv2',

    // OTT Settings
    'ott_ttl' => 60, // 1 Minute
    'ott_cache_prefix' => 'sso_ott_',

    // Customer Settings
    'customer_guard' => 'customer',
    'customer_dashboard_route' => 'customer.dashboard',
];
```

### Umgebungsvariablen / Environment Variables

**Erforderlich / Required:**
```env
# In .env
# Kopieren Sie den PASSPORT_PUBLIC_KEY Wert von Service 1
SSO_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
...
-----END PUBLIC KEY-----"
```

**Optional:**
```env
SSO_USE_ENV_KEYS=true  # true = use env vars, false = use files
```

---

## Funktionsweise / How It Works

### SSO Flow (SP Perspektive)

```
1. Service 1 (IdP) sendet Backend-zu-Backend POST Request
   Service 1 (IdP) sends backend-to-backend POST request
   POST /api/pdsauthint/exchange { "jwt": "..." }

2. SPController::exchangeToken()
   ├── Lädt Public Key (PASSPORT_PUBLIC_KEY aus .env)
   │   Loads public key (PASSPORT_PUBLIC_KEY from .env)
   │
   ├── Validiert JWT-Signatur mit RS256
   │   Validates JWT signature with RS256
   │
   ├── Verifiziert Claims:
   │   Verifies claims:
   │   - iss = 'pds-homepage'
   │   - aud = 'riskmanagementv2'
   │   - exp > now (nicht abgelaufen)
   │
   ├── Generiert One-Time Token (OTT)
   │   Generates One-Time Token (OTT)
   │   - 60 Zeichen Zufallsstring
   │   - 60 Sekunden gültig
   │
   ├── Speichert Claims im Cache
   │   Stores claims in cache
   │   Cache::put('sso_ott_ABC123', $claims, 60)
   │
   └── Gibt OTT und Redirect-URL zurück
       Returns OTT and redirect URL

3. Service 1 gibt Login-URL an Browser weiter
   Service 1 forwards login URL to browser

4. Browser öffnet GET /pdsauthint/login?ott=ABC123
   Browser opens GET /pdsauthint/login?ott=ABC123

5. SPController::handleLogin()
   ├── Holt Claims aus Cache und löscht sie
   │   Gets claims from cache and deletes them
   │   $claims = Cache::pull('sso_ott_ABC123')
   │
   ├── JIT Provisioning:
   │   ├── Sucht Customer: agent_id + service1_customer_id
   │   │   Searches for customer: agent_id + service1_customer_id
   │   │
   │   ├── Falls nicht gefunden:
   │   │   If not found:
   │   │   └── Erstellt neuen Customer mit allen Daten
   │   │       Creates new customer with all data
   │   │
   │   └── Falls gefunden:
   │       If found:
   │       └── Aktualisiert Daten (email, phone, address, account_type)
   │           Updates data (email, phone, address, account_type)
   │
   ├── Login durchführen
   │   Perform login
   │   Auth::guard('customer')->login($customer)
   │
   └── Redirect zu Dashboard
       Redirect to dashboard
```

---

## Installation / Setup

### 1. Composer Pakete / Composer Packages

```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
composer require firebase/php-jwt
```

### 2. Migration ausführen / Run Migration

```bash
php artisan migrate
```

Diese Migration fügt folgende Spalten zur `customers` Tabelle hinzu:
This migration adds the following columns to the `customers` table:

- `agent_id` (string, nullable)
- `service1_customer_id` (string, nullable)
- `phone` (string, nullable)
- `address` (json, nullable)
- `account_type` (string, default: 'standard')
- Unique constraint: `['agent_id', 'service1_customer_id']`

### 3. Public Key konfigurieren / Configure Public Key

Kopieren Sie den `PASSPORT_PUBLIC_KEY` Wert von Service 1 (pds-homepage) und fügen Sie ihn zur `.env` hinzu:

Copy the `PASSPORT_PUBLIC_KEY` value from Service 1 (pds-homepage) and add it to `.env`:

```env
SSO_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA...
...
-----END PUBLIC KEY-----"
```

### 4. Service Provider ist bereits registriert

✅ Der Service Provider wurde bereits in `app/Providers/AppServiceProvider.php` registriert.

✅ The Service Provider has already been registered in `app/Providers/AppServiceProvider.php`.

### 5. Cache leeren / Clear Cache

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 6. Routen überprüfen / Verify Routes

```bash
php artisan route:list | grep pdsauthint
```

Erwartete Ausgabe / Expected output:
```
GET|HEAD  pdsauthint/login ................. pdsauthint.login › SPController@handleLogin
POST      api/pdsauthint/exchange .......... pdsauthint.api.exchange › SPController@exchangeToken
```

---

## Customer Model Anpassungen

### Fillable Fields

Stellen Sie sicher, dass das `Customer` Model folgende Felder als `$fillable` hat:

```php
// app/Models/Customer.php

protected $fillable = [
    'agent_id',
    'service1_customer_id',
    'name',
    'email',
    'phone',
    'address',
    'account_type',
    'password',
];
```

### Casts

```php
protected $casts = [
    'address' => 'array',
];
```

### Guard Configuration

Prüfen Sie `config/auth.php`:

```php
'guards' => [
    'customer' => [
        'driver' => 'session',
        'provider' => 'customers',
    ],
],

'providers' => [
    'customers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Customer::class,
    ],
],
```

---

## Just-in-Time (JIT) Provisioning

### Was ist JIT?

Automatische Erstellung von Kunden-Accounts beim ersten SSO-Login.

### Ablauf:

**Erster Login:**
```php
// Customer existiert noch nicht
$customer = Customer::create([
    'service1_customer_id' => $claims['sub'],
    'agent_id' => $claims['agent_id'],
    'email' => $claims['email'],
    'phone' => $claims['phone'] ?? null,
    'address' => $claims['address'] ?? null,
    'account_type' => $claims['account_type'] ?? 'standard',
    'name' => $claims['email'],
    'password' => Hash::make(Str::random(32)),
]);
```

**Folgende Logins:**
```php
// Customer existiert bereits
$customer->update([
    'email' => $claims['email'],
    'phone' => $claims['phone'] ?? null,
    'address' => $claims['address'] ?? null,
    'account_type' => $claims['account_type'] ?? 'standard',
]);
```

### Multi-Tenancy

Kunden werden über `(agent_id, service1_customer_id)` identifiziert:

```php
$customer = Customer::where('agent_id', $claims['agent_id'])
    ->where('service1_customer_id', $claims['sub'])
    ->first();
```

Dies stellt sicher, dass Kunden mit derselben E-Mail bei verschiedenen Agenten getrennt bleiben.

---

## Sicherheit / Security

### JWT Validierung

1. **Signatur-Validierung**
   - RS256 Algorithmus
   - Public Key Verifikation

2. **Claims-Validierung**
   - `iss` (Issuer): Muss 'pds-homepage' sein
   - `aud` (Audience): Muss 'riskmanagementv2' sein
   - `exp` (Expiration): Darf nicht abgelaufen sein

3. **One-Time Token**
   - 60 Sekunden gültig
   - Wird nach Verwendung gelöscht (`Cache::pull`)
   - Kann nur einmal verwendet werden

### Best Practices

1. **Public Key schützen**
   - Als Umgebungsvariable speichern
   - Nicht in Git committen

2. **HTTPS verwenden**
   - In Produktion immer HTTPS

3. **Cache überwachen**
   - OTT-Ablauf protokollieren
   - Ungewöhnliche Cache-Aktivitäten

---

## Logging

Alle SSO-Aktionen werden geloggt:

```bash
# Logs anzeigen
tail -f storage/logs/laravel.log | grep SSO
```

### Log-Beispiele

**JWT Exchange Erfolg:**
```
[INFO] OTT generated successfully
    agent_id: 456
    customer_id: 123
    ttl: 60
```

**JWT Validierung fehlgeschlagen:**
```
[WARNING] JWT validation failed
    error: Signature verification failed
    jwt: eyJ0eXAiOiJKV1Q...
```

**JIT Customer erstellt:**
```
[INFO] JIT: New customer created
    service1_customer_id: 123
    agent_id: 456
    email: customer@example.com
```

---

## Troubleshooting

### "Public key not found"

**Lösung:**
- Prüfen Sie, ob `SSO_PUBLIC_KEY` oder `PASSPORT_PUBLIC_KEY` in `.env` vorhanden ist
- Kopieren Sie den Public Key von Service 1

### "JWT signature validation failed"

**Lösung:**
- Stellen Sie sicher, dass der Public Key mit dem Private Key von Service 1 übereinstimmt
- Prüfen Sie die Key-Format (PEM)

### "Login link invalid or expired"

**Ursachen:**
- OTT ist älter als 60 Sekunden
- OTT wurde bereits verwendet
- Cache ist nicht verfügbar

**Lösung:**
- User muss SSO-Prozess neu starten
- Prüfen Sie Cache-Konfiguration

### "Duplicate entry for key 'unique_agent_customer'"

**Ursache:**
- Customer mit gleicher `(agent_id, service1_customer_id)` existiert bereits

**Lösung:**
- Sollte nicht passieren, da JIT zuerst sucht
- Prüfen Sie Customer-Tabelle auf Inkonsistenzen

---

## Testing

### 1. Test JWT Exchange

```bash
# Mit einem echten JWT von Service 1
curl -X POST http://127.0.0.1:8000/api/pdsauthint/exchange \
  -H "Content-Type: application/json" \
  -d '{"jwt": "YOUR_JWT_HERE"}'
```

### 2. Test Complete Flow

1. Login in Service 1 (pds-homepage)
2. Initiiere SSO
3. Prüfe Redirect zu Service 2
4. Prüfe Customer-Erstellung in Datenbank:

```sql
SELECT * FROM customers
WHERE service1_customer_id IS NOT NULL
ORDER BY created_at DESC
LIMIT 1;
```

### 3. Test Multi-Tenancy

Erstellen Sie Kunden mit verschiedenen Agenten und prüfen Sie, dass sie getrennt bleiben.

---

## Abhängigkeiten / Dependencies

### Composer Pakete

- `firebase/php-jwt` - JWT-Validierung

### Laravel Features

- `Cache` Facade - OTT-Speicherung
- `Auth` Facade - Customer-Login
- `Log` Facade - Logging

### Database Migration

- Migration für `customers` Tabelle
- Unique constraint auf `(agent_id, service1_customer_id)`

---

## Weitere Dokumentation

Siehe `docs/` Verzeichnis:

- **README_SSO.md** - Vollständige Systemdokumentation
- **INSTALLATION.md** - Installationsanleitung
- **KEY_GENERATION.md** - RSA-Schlüssel Setup
- **SSO_IMPLEMENTATION_SUMMARY.md** - Implementierungs-Zusammenfassung

---

## Support

Bei Fragen oder Problemen:

1. Prüfen Sie die Logs: `storage/logs/laravel.log`
2. Aktivieren Sie Debug-Modus: `LOG_LEVEL=debug`
3. Konsultieren Sie die Dokumentation in `docs/`

---

## Version

**Version:** 1.0.0
**Datum:** 2025-11-11
**Laravel:** 12
**PHP:** 8.2+
