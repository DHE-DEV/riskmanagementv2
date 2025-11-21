# SSO Testing Guide - riskmanagementv2

Anleitung zum Testen der SSO-Integration zwischen pds-homepage und riskmanagementv2

## Übersicht

Das SSO-System verwendet einen 2-Schritt-Flow:
1. **JWT Exchange** (API): pds-homepage sendet JWT → riskmanagementv2 validiert und gibt OTT zurück
2. **Login mit OTT** (Web): User wird zu `/pdsauthint/login?ott=...` weitergeleitet → JIT Provisioning → Login → Redirect zum Dashboard

## Voraussetzungen

### 1. Konfiguration prüfen

```bash
php artisan config:show pdsauthint
```

**Erwartete Werte:**
- `role`: sp
- `public_key`: -----BEGIN PUBLIC KEY----- ... (sollte geladen sein)
- `use_env_keys`: true
- `jwt_issuer`: pds-homepage
- `jwt_audience`: riskmanagementv2
- `ott_ttl`: 60
- `customer_guard`: customer
- `customer_dashboard_route`: customer.dashboard

### 2. Migration ausgeführt

Die Customer-Tabelle muss die SSO-Felder haben:
- `agent_id` (string)
- `service1_customer_id` (string)
- Unique constraint auf (agent_id, service1_customer_id)

```bash
php artisan migrate:status
# Sollte zeigen: 2025_11_11_063922_add_sso_fields_to_customers_table
```

## Testing-Methoden

### Methode A: Vollständiger SSO-Flow (End-to-End Test)

**Voraussetzung**: Beide Services laufen

1. **Services starten** (falls lokal):
```bash
# Terminal 1: pds-homepage
cd /path/to/pds-homepage
php artisan serve --port=8000

# Terminal 2: riskmanagementv2
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
php artisan serve --port=8001
```

2. **Logs in Echtzeit verfolgen**:
```bash
# In riskmanagementv2
tail -f storage/logs/laravel.log | grep "SSO:"
```

3. **SSO-Flow triggern**:
   - In pds-homepage als Customer einloggen
   - SSO-Link/Button klicken (z.B. "Zu Service 2 wechseln")
   - Sollte automatisch zu riskmanagementv2 weiterleiten und einloggen

4. **Erwartetes Ergebnis**:
   - User ist in riskmanagementv2 eingeloggt
   - Redirect zum Customer Dashboard
   - Customer wurde ggf. via JIT angelegt

### Methode B: API-Test mit curl (Schnelltest)

**Schritt 1: JWT generieren (in pds-homepage)**
```bash
php artisan tinker
```

```php
use Firebase\JWT\JWT;

$payload = [
    'iss' => 'pds-homepage',           // Muss exakt so sein
    'aud' => 'riskmanagementv2',       // Muss exakt so sein
    'sub' => '123',                     // Customer ID aus pds-homepage
    'agent_id' => 'test-agent-789',    // Agent/Agency ID
    'email' => 'sso-test@example.com',
    'phone' => '+49123456789',
    'address' => 'Teststraße 123',
    'account_type' => 'premium',
    'exp' => time() + 300,             // 5 Minuten gültig
    'iat' => time(),
];

$privateKey = config('pdsauthint.private_key');
$jwt = JWT::encode($payload, $privateKey, 'RS256');
echo $jwt . "\n";
// Kopiere den JWT-Token
```

**Schritt 2: JWT Exchange (in riskmanagementv2)**
```bash
# Ersetze [JWT_TOKEN] mit dem generierten Token
curl -X POST http://127.0.0.1:8001/api/pdsauthint/exchange \
  -H "Content-Type: application/json" \
  -d '{"jwt":"[JWT_TOKEN]"}' \
  | jq .
```

**Erwartete Antwort:**
```json
{
  "success": true,
  "ott": "abcdef123456...",
  "redirect_url": "http://127.0.0.1:8001/pdsauthint/login?ott=abcdef123456...",
  "expires_in": 60
}
```

**Schritt 3: Login mit OTT**
```bash
# Öffne die redirect_url im Browser:
# http://127.0.0.1:8001/pdsauthint/login?ott=abcdef123456...

# ODER teste mit curl (zeigt nur Redirect):
curl -L -v "http://127.0.0.1:8001/pdsauthint/login?ott=[OTT_TOKEN]"
```

**Schritt 4: Customer prüfen**
```bash
php artisan tinker
```

```php
// Customer sollte angelegt worden sein
$customer = \App\Models\Customer::where('agent_id', 'test-agent-789')
    ->where('service1_customer_id', '123')
    ->first();

echo "Customer gefunden: " . ($customer ? "JA" : "NEIN") . "\n";
if ($customer) {
    echo "Name: " . $customer->name . "\n";
    echo "Email: " . $customer->email . "\n";
    echo "Agent ID: " . $customer->agent_id . "\n";
    echo "Service1 Customer ID: " . $customer->service1_customer_id . "\n";
}
```

## Log-Output verstehen

### Erfolgreicher JWT Exchange

```
[timestamp] SSO: ====== SSO TOKEN EXCHANGE START (Service 2 - SP) ======
[timestamp] SSO: Received JWT exchange request
[timestamp] SSO: Loading public key for JWT validation
[timestamp] SSO: Using environment variable for public key
[timestamp] SSO: Public key loaded from environment
[timestamp] SSO: Attempting to decode JWT with RS256
[timestamp] SSO: JWT decoded successfully
[timestamp] SSO: Verifying JWT issuer
[timestamp] SSO: Verifying JWT audience
[timestamp] SSO: Generating OTT
[timestamp] SSO: OTT generated and stored in cache
[timestamp] SSO: ====== SSO EXCHANGE SUCCESS (Service 2 - SP) ======
```

### Erfolgreicher Login

```
[timestamp] SSO: ====== SSO LOGIN START (Service 2 - SP) ======
[timestamp] SSO: Received login request with OTT
[timestamp] SSO: Retrieving claims from cache
[timestamp] SSO: Claims retrieved from cache
[timestamp] SSO: Starting JIT provisioning
[timestamp] SSO: Existing customer found - updating  (ODER: No existing customer found - creating new customer)
[timestamp] SSO: Customer updated successfully  (ODER: New customer created successfully)
[timestamp] SSO: Logging in customer
[timestamp] SSO: Customer logged in successfully
```

## Troubleshooting

### Fehler: "Public key not found"

**Symptom**:
```json
{
  "error": "Configuration error",
  "message": "Public key not found"
}
```

**Lösung**:
1. Prüfe .env: `SSO_PUBLIC_KEY` oder `PASSPORT_PUBLIC_KEY` gesetzt?
2. Prüfe: `php artisan config:show pdsauthint.public_key`
3. Cache leeren: `php artisan config:clear`

### Fehler: "Invalid token" / "JWT signature validation failed"

**Symptom**:
```json
{
  "error": "Invalid token",
  "message": "JWT signature validation failed"
}
```

**Mögliche Ursachen**:
1. **Public/Private Key Mismatch**:
   - pds-homepage verwendet anderen Private Key als der Public Key in riskmanagementv2
   - Lösung: Keys synchronisieren

2. **Falscher Algorithmus**:
   - Muss RS256 sein
   - Prüfe JWT Generation in pds-homepage

3. **Fehlerhafter JWT**:
   - JWT beim Kopieren beschädigt
   - Lösung: Neu generieren

**Debug-Schritte**:
```bash
# Logs prüfen
tail -50 storage/logs/laravel.log | grep "SSO:"

# Keys vergleichen
php artisan tinker
>>> strlen(config('pdsauthint.public_key'))  // Sollte > 400 sein
>>> config('pdsauthint.use_env_keys')        // Sollte true sein
```

### Fehler: "Invalid issuer" oder "Invalid audience"

**Symptom**:
```json
{
  "error": "Invalid token",
  "message": "Invalid issuer"
}
```

**Lösung**:
Der JWT muss genau diese Claims haben:
- `iss`: "pds-homepage"
- `aud`: "riskmanagementv2"

Prüfe beim JWT-Erstellen, dass diese Werte exakt übereinstimmen.

### Fehler: "Invalid or expired OTT"

**Symptom**: Nach dem Login-Redirect kommt Fehlermeldung

**Mögliche Ursachen**:
1. **OTT abgelaufen**: TTL ist nur 60 Sekunden
2. **OTT bereits verwendet**: OTTs sind einmalig (Cache::pull)
3. **Cache-Problem**: Redis/File-Cache funktioniert nicht

**Lösung**:
```bash
# Cache testen
php artisan tinker
>>> use Illuminate\Support\Facades\Cache;
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');  // Sollte "value" zurückgeben
>>> Cache::pull('test'); // Sollte "value" zurückgeben und löschen
>>> Cache::get('test');  // Sollte null sein
```

### Fehler: Customer wird nicht angelegt

**Symptom**: Login schlägt fehl, kein Customer in DB

**Debug**:
```bash
# Logs prüfen
tail -100 storage/logs/laravel.log | grep -A 5 "JIT provisioning"

# DB-Zugriff testen
php artisan tinker
>>> \App\Models\Customer::count()
>>> \App\Models\Customer::create([
    'agent_id' => 'test',
    'service1_customer_id' => 'test',
    'name' => 'Test',
    'email' => 'test@test.com',
    'password' => bcrypt('test')
]);
```

**Mögliche Ursachen**:
1. Migration nicht ausgeführt
2. Felder nicht in `$fillable`
3. Unique Constraint verletzt

## Production Checklist

Vor dem Deployment auf Production:

- [ ] Keys in .env korrekt gesetzt (PASSPORT_PUBLIC_KEY)
- [ ] `SSO_USE_ENV_KEYS=true` gesetzt
- [ ] Migration ausgeführt (`php artisan migrate`)
- [ ] Config Cache geleert (`php artisan config:clear`)
- [ ] HTTPS für alle SSO-Endpoints
- [ ] pds-homepage hat korrekte URLs für Production:
  - `SSO_SERVICE2_EXCHANGE_URL=https://your-domain.com/api/pdsauthint/exchange`
  - `SSO_SERVICE2_LOGIN_URL=https://your-domain.com/pdsauthint/login`
- [ ] Rate Limiting auf `/api/pdsauthint/exchange` aktiviert
- [ ] Logs überwacht (SSO-Flow läuft korrekt)
- [ ] Customer Guard korrekt konfiguriert
- [ ] Dashboard Route erreichbar nach Login

## Nützliche Kommandos

```bash
# Konfiguration anzeigen
php artisan config:show pdsauthint

# Cache leeren (nach .env Änderungen)
php artisan config:clear
php artisan cache:clear

# Routes anzeigen
php artisan route:list | grep pdsauthint

# Logs live verfolgen (nur SSO-relevante)
tail -f storage/logs/laravel.log | grep "SSO:"

# Logs live verfolgen (alle)
tail -f storage/logs/laravel.log

# Migration Status
php artisan migrate:status

# Customers anzeigen
php artisan tinker
>>> \App\Models\Customer::all(['id', 'agent_id', 'service1_customer_id', 'email'])
```

## Endpoints Übersicht

### API Endpoints

**POST `/api/pdsauthint/exchange`**
- **Zweck**: JWT gegen OTT austauschen
- **Input**: `{"jwt": "eyJ..."}`
- **Output**: `{"success": true, "ott": "...", "redirect_url": "...", "expires_in": 60}`
- **Auth**: Keine (JWT ist die Auth)

### Web Endpoints

**GET `/pdsauthint/login?ott=abc123...`**
- **Zweck**: Login mit OTT durchführen
- **Input**: Query-Parameter `ott` (60 Zeichen)
- **Output**: Redirect zum Customer Dashboard
- **Side Effects**:
  - Customer wird via JIT angelegt/aktualisiert
  - Customer wird eingeloggt
  - OTT wird aus Cache gelöscht (einmalige Verwendung)

## Weitere Ressourcen

- **Vollständige Dokumentation**: `app/Modules/PdsAuthInt/docs/SSO_IMPLEMENTATION_SUMMARY.md`
- **Controller Code**: `app/Modules/PdsAuthInt/Http/Controllers/SPController.php`
- **Config**: `app/Modules/PdsAuthInt/config/pdsauthint.php`
- **Routes**:
  - API: `app/Modules/PdsAuthInt/routes/api.php`
  - Web: `app/Modules/PdsAuthInt/routes/web.php`
