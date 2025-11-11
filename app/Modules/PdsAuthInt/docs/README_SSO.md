# PdsAuthInt SSO System - Vollständige Implementierung

## Übersicht / Overview

Dieses Projekt implementiert ein sicheres Single Sign-On (SSO) System zwischen zwei Laravel-Anwendungen mit JWT-basierten Tokens, JIT-Provisioning und Multi-Tenancy-Unterstützung.

This project implements a secure Single Sign-On (SSO) system between two Laravel applications with JWT-based tokens, JIT provisioning, and multi-tenancy support.

---

## Architektur / Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    SSO Flow Diagram                              │
└─────────────────────────────────────────────────────────────────┘

Service 1 (pds-homepage)          Service 2 (riskmanagementv2)
Identity Provider (IdP)           Service Provider (SP)
Laravel 11 / Google App Engine    Laravel 12

┌──────────────┐                  ┌──────────────┐
│   Customer   │                  │   Customer   │
│    Login     │                  │  Dashboard   │
└──────┬───────┘                  └──────▲───────┘
       │                                  │
       │ 1. Authenticated                │ 6. Redirect with
       │                                  │    session
       ▼                                  │
┌──────────────┐                  ┌──────┴───────┐
│ IdPController│                  │ SPController │
│              │                  │              │
│ 2. Create JWT│                  │ 5. JIT       │
│    with user │                  │    Provision │
│    data      │                  │    & Login   │
└──────┬───────┘                  └──────▲───────┘
       │                                  │
       │ 3. POST JWT                     │
       │    (Backend-to-Backend)         │ 4. Return OTT
       │                                  │
       └──────────────────┬───────────────┘
                          │
                    ┌─────▼──────┐
                    │   Cache    │
                    │  (OTT +    │
                    │   Claims)  │
                    └────────────┘
```

---

## Komponenten / Components

### Service 1: pds-homepage (Identity Provider)

**Rolle:** Authentifiziert Kunden und stellt JWT-Tokens aus

**Verzeichnis:** `/home/dh/Code/laravel/tmp-cruisedesign/pds-homepage`

**Technologie:** Laravel 11, Google App Engine

**Endpunkt:**
- `POST /pdsauthint/redirect` - Initiiert SSO-Prozess

**Konfiguration:**
- Verwendet `PASSPORT_PRIVATE_KEY` und `PASSPORT_PUBLIC_KEY` aus `.env`
- Alternativ: Schlüssel-Dateien in `storage/app/sso/`

### Service 2: riskmanagementv2 (Service Provider)

**Rolle:** Empfängt und verifiziert Authentifizierungen

**Verzeichnis:** `/home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2`

**Technologie:** Laravel 12

**Endpunkte:**
- `POST /api/pdsauthint/exchange` - Tauscht JWT gegen OTT
- `GET /pdsauthint/login?ott=...` - Führt Login mit OTT durch

**Konfiguration:**
- Verwendet `SSO_PUBLIC_KEY` oder `PASSPORT_PUBLIC_KEY` aus `.env`
- Alternativ: Public Key Datei in `storage/app/sso/`

---

## Modulstruktur / Module Structure

Beide Services verwenden dieselbe modulare Struktur:

```
app/Modules/PdsAuthInt/
├── Providers/
│   └── PdsAuthIntServiceProvider.php   # Service Provider
├── Http/
│   └── Controllers/
│       ├── IdPController.php            # (Service 1)
│       └── SPController.php             # (Service 2)
├── routes/
│   ├── web.php                          # Web-Routen
│   └── api.php                          # API-Routen (nur Service 2)
└── config/
    └── pdsauthint.php                   # Modul-Konfiguration
```

---

## Installation

### 1. Composer-Pakete installieren

```bash
# Service 1 (pds-homepage)
cd /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage
composer require firebase/php-jwt

# Service 2 (riskmanagementv2)
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
composer require firebase/php-jwt
```

### 2. Schlüssel-Konfiguration

#### Option A: Vorhandene PASSPORT-Keys verwenden (empfohlen)

**Service 1 (pds-homepage):**
- Nutzt automatisch `PASSPORT_PRIVATE_KEY` und `PASSPORT_PUBLIC_KEY` aus `.env`
- Keine weitere Aktion erforderlich!

**Service 2 (riskmanagementv2):**

Fügen Sie zur `.env` hinzu:

```env
# Kopieren Sie den PASSPORT_PUBLIC_KEY Wert von Service 1
SSO_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
...
-----END PUBLIC KEY-----"

# SSO Configuration
SSO_USE_ENV_KEYS=true
SSO_SERVICE2_EXCHANGE_URL=http://127.0.0.1:8000/api/pdsauthint/exchange
SSO_SERVICE2_LOGIN_URL=http://127.0.0.1:8000/pdsauthint/login
```

#### Option B: Neue Schlüssel generieren

Siehe `KEY_GENERATION.md` für detaillierte Anweisungen.

### 3. Migration ausführen (nur Service 2)

```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
php artisan migrate
```

### 4. Cache leeren

```bash
# Service 1
cd /home/dh/Code/laravel/tmp-cruisedesign/pds-homepage
php artisan config:clear && php artisan route:clear && php artisan cache:clear

# Service 2
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
php artisan config:clear && php artisan route:clear && php artisan cache:clear
```

---

## Verwendung / Usage

### Frontend Integration (Service 1)

```javascript
// Beispiel: SSO-Button in Service 1
async function initiateSSO() {
    try {
        const response = await fetch('/pdsauthint/redirect', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            // Weiterleitung zu Service 2 mit OTT
            window.location.href = data.login_url;
        } else {
            console.error('SSO failed:', data.message);
        }
    } catch (error) {
        console.error('SSO error:', error);
    }
}
```

### API Flow

#### 1. Customer initiiert SSO (Service 1)

**Request:**
```http
POST /pdsauthint/redirect HTTP/1.1
Host: pds-homepage.example.com
Cookie: laravel_session=...
```

**Response:**
```json
{
    "success": true,
    "login_url": "http://riskmanagementv2.example.com/pdsauthint/login?ott=ABC123..."
}
```

#### 2. Backend-to-Backend Exchange (automatisch)

Service 1 ruft Service 2 auf:

**Request:**
```http
POST /api/pdsauthint/exchange HTTP/1.1
Host: riskmanagementv2.example.com
Content-Type: application/json

{
    "jwt": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Response:**
```json
{
    "success": true,
    "ott": "ABC123...",
    "redirect_url": "http://riskmanagementv2.example.com/pdsauthint/login?ott=ABC123..."
}
```

#### 3. Customer Login (Service 2)

**Request:**
```http
GET /pdsauthint/login?ott=ABC123... HTTP/1.1
Host: riskmanagementv2.example.com
```

**Erfolg:** Redirect zu `customer.dashboard` mit aktiver Session

**Fehler:** Fehlermeldung anzeigen

---

## Sicherheitsfeatures / Security Features

### 1. RS256 Asymmetrische Signatur
- Private Key nur bei Service 1 (IdP)
- Public Key bei Service 2 (SP) zur Verifikation
- Keine Shared Secrets

### 2. JWT Claims Validierung
- `iss` (Issuer): Muss `pds-homepage` sein
- `aud` (Audience): Muss `riskmanagementv2` sein
- `exp` (Expiration): 5 Minuten TTL
- `sub` (Subject): Customer ID von Service 1

### 3. One-Time Token (OTT)
- 60 Zeichen Zufallsstring
- 60 Sekunden TTL im Cache
- Wird beim Abruf gelöscht (`Cache::pull`)
- Kann nur einmal verwendet werden

### 4. Multi-Tenancy
- Unique Constraint: `(agent_id, service1_customer_id)`
- Verhindert Kollisionen zwischen Agenten
- Jeder Customer ist einem Agent zugeordnet

### 5. Backend-to-Backend Kommunikation
- JWT läuft niemals durch den Browser
- Verhindert Token-Diebstahl
- Sichere Server-zu-Server HTTP-Requests

---

## Just-in-Time (JIT) Provisioning

### Was ist JIT?

Automatische Benutzererstellung beim ersten Login.

### Ablauf:

1. **Erster Login:**
   - Customer existiert noch nicht in Service 2
   - System erstellt automatisch neuen Account
   - Alle Daten aus JWT werden übernommen

2. **Folgende Logins:**
   - Customer existiert bereits
   - Daten werden aktualisiert (email, phone, address, account_type)
   - Bestehende Daten bleiben erhalten

### Beispiel:

```php
// In SPController.php - handleLogin()

$service1_id = $claims['sub'];
$agent_id = $claims['agent_id'];

$customer = Customer::where('service1_customer_id', $service1_id)
    ->where('agent_id', $agent_id)
    ->first();

if (!$customer) {
    // JIT: Neuen Customer erstellen
    $customer = Customer::create([
        'service1_customer_id' => $service1_id,
        'agent_id' => $agent_id,
        'email' => $claims['email'],
        'phone' => $claims['phone'] ?? null,
        'address' => $claims['address'] ?? null,
        'account_type' => $claims['account_type'] ?? 'standard',
        'name' => $claims['email'], // oder andere Logik
        'password' => Hash::make(Str::random(32)), // Zufallspasswort
    ]);
} else {
    // Update: Bestehende Daten aktualisieren
    $customer->update([
        'email' => $claims['email'],
        'phone' => $claims['phone'] ?? null,
        'address' => $claims['address'] ?? null,
        'account_type' => $claims['account_type'] ?? 'standard',
    ]);
}

// Login durchführen
Auth::guard('customer')->login($customer);
```

---

## Datenbank-Schema

### Migration für Service 2

```php
Schema::table('customers', function (Blueprint $table) {
    $table->string('agent_id')->nullable();
    $table->string('service1_customer_id')->nullable();
    $table->string('phone')->nullable();
    $table->json('address')->nullable();
    $table->string('account_type')->default('standard');

    // Unique Constraint für Multi-Tenancy
    $table->unique(['agent_id', 'service1_customer_id'], 'unique_agent_customer');
});
```

### Customer Model

```php
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

protected $casts = [
    'address' => 'array',
];
```

---

## Konfiguration

### Service 1 (config/pdsauthint.php)

```php
return [
    'role' => 'idp',
    'private_key' => env('PASSPORT_PRIVATE_KEY') ?: storage_path('app/sso/sso-private.key'),
    'public_key' => env('PASSPORT_PUBLIC_KEY') ?: storage_path('app/sso/sso-public.key'),
    'use_env_keys' => (bool) env('SSO_USE_ENV_KEYS', true),
    'service2_exchange_url' => env('SSO_SERVICE2_EXCHANGE_URL'),
    'jwt_issuer' => 'pds-homepage',
    'jwt_audience' => 'riskmanagementv2',
    'jwt_ttl' => 300, // 5 Minuten
    'service2_login_url' => env('SSO_SERVICE2_LOGIN_URL'),
];
```

### Service 2 (config/pdsauthint.php)

```php
return [
    'role' => 'sp',
    'public_key' => env('SSO_PUBLIC_KEY') ?: env('PASSPORT_PUBLIC_KEY') ?: storage_path('app/sso/sso-public.key'),
    'use_env_keys' => (bool) env('SSO_USE_ENV_KEYS', true),
    'jwt_issuer' => 'pds-homepage',
    'jwt_audience' => 'riskmanagementv2',
    'ott_ttl' => 60, // 1 Minute
    'ott_cache_prefix' => 'sso_ott_',
    'customer_guard' => 'customer',
    'customer_dashboard_route' => 'customer.dashboard',
];
```

---

## Fehlerbehandlung / Error Handling

### Häufige Fehler

#### 1. "Private key not found"

**Ursache:** PASSPORT_PRIVATE_KEY nicht in .env oder Datei fehlt

**Lösung:**
```bash
# Prüfen Sie die .env
grep PASSPORT_PRIVATE_KEY .env

# Oder erstellen Sie die Datei
mkdir -p storage/app/sso
# Schlüssel generieren (siehe KEY_GENERATION.md)
```

#### 2. "JWT signature validation failed"

**Ursache:** Public Key stimmt nicht mit Private Key überein

**Lösung:**
- Stellen Sie sicher, dass Service 2 den GLEICHEN Public Key wie Service 1 verwendet
- Kopieren Sie `PASSPORT_PUBLIC_KEY` von Service 1 zu Service 2

#### 3. "Token expired"

**Ursache:** JWT ist älter als 5 Minuten

**Lösung:**
- Normal - User muss SSO-Prozess neu starten
- Erhöhen Sie `jwt_ttl` in config falls nötig

#### 4. "Login link invalid or expired"

**Ursache:** OTT wurde bereits verwendet oder ist abgelaufen (>60 Sekunden)

**Lösung:**
- Normal - User muss SSO-Prozess neu starten
- Erhöhen Sie `ott_ttl` in config falls nötig

### Logging

Alle SSO-Aktionen werden geloggt:

```bash
# Service 1 Logs
tail -f storage/logs/laravel.log | grep PdsAuthInt

# Service 2 Logs
tail -f storage/logs/laravel.log | grep SSO
```

---

## Testing

### Manuelle Tests

#### 1. Test JWT Generation (Service 1)

```bash
# In pds-homepage, als eingeloggter Customer
curl -X POST http://localhost/pdsauthint/redirect \
  -H "Cookie: laravel_session=YOUR_SESSION" \
  -H "X-CSRF-TOKEN: YOUR_TOKEN"
```

#### 2. Test JWT Validation (Service 2)

```bash
# Mit JWT von Schritt 1
curl -X POST http://127.0.0.1:8000/api/pdsauthint/exchange \
  -H "Content-Type: application/json" \
  -d '{"jwt": "YOUR_JWT_HERE"}'
```

#### 3. Test Complete Flow

1. Login in Service 1 als Customer
2. Klick auf SSO-Button
3. Prüfen Sie Redirect zu Service 2
4. Prüfen Sie, ob Session in Service 2 aktiv ist

### Unit Tests (Beispiel)

```php
// tests/Feature/SSOTest.php

public function test_jwt_exchange_validates_signature()
{
    $invalidJWT = 'invalid.jwt.token';

    $response = $this->postJson('/api/pdsauthint/exchange', [
        'jwt' => $invalidJWT
    ]);

    $response->assertStatus(401)
        ->assertJson(['error' => 'Invalid token']);
}

public function test_ott_can_only_be_used_once()
{
    $ott = 'test_ott_token';

    // Ersten Request
    $this->get("/pdsauthint/login?ott={$ott}")
        ->assertRedirect();

    // Zweiten Request mit gleichem OTT
    $this->get("/pdsauthint/login?ott={$ott}")
        ->assertStatus(400);
}
```

---

## Deployment

### Google App Engine (Service 1)

Die Konfiguration ist bereits für Google App Engine optimiert:

1. `PASSPORT_PRIVATE_KEY` und `PASSPORT_PUBLIC_KEY` in App Engine Environment Variables setzen
2. `SSO_USE_ENV_KEYS=true` setzen
3. Keine Dateien notwendig

### Standard Laravel (Service 2)

```bash
# 1. Environment konfigurieren
cp .env.example .env
php artisan key:generate

# 2. SSO Public Key setzen
# SSO_PUBLIC_KEY in .env einfügen

# 3. Datenbank migrieren
php artisan migrate

# 4. Cache optimieren
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Server starten
php artisan serve --port=8000
```

---

## Weitere Dokumentation

- `KEY_GENERATION.md` - Schlüsselgenerierung
- `INSTALLATION.md` - Detaillierte Installationsanleitung
- `SERVICE_PROVIDER_REGISTRATION.md` - Service Provider Registrierung
- `DOKUMENTATION_DE.md` - Deutsche Dokumentation (in Arbeit)
- `DOCUMENTATION_EN.md` - English Documentation (in Arbeit)

---

## Support & Kontakt

Bei Fragen oder Problemen:

1. Prüfen Sie die Logs: `storage/logs/laravel.log`
2. Aktivieren Sie Debug-Modus: `LOG_LEVEL=debug` in `.env`
3. Prüfen Sie die Konfiguration: `php artisan config:show pdsauthint`

---

## Lizenz / License

Proprietär / Proprietary - Passolution GmbH

---

## Changelog

### Version 1.0.0 (2025-11-11)

- ✅ Initiale Implementierung
- ✅ JWT-basiertes SSO mit RS256
- ✅ JIT Provisioning
- ✅ Multi-Tenancy Support
- ✅ PASSPORT_KEY Integration für Google App Engine
- ✅ Modulare Architektur
- ✅ Umfassende Fehlerbehandlung
- ✅ Bilingual documentation (DE/EN)
