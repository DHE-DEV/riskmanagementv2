# SSO Konfiguration für riskmanagementv2 (Service 2)

## Übersicht

Service 2 empfängt SSO-Authentifizierungen von Service 1.

**Service 1 (IdP):** https://auth-f95c036-dot-web1-dot-dataservice-development.ey.r.appspot.com
**Service 2 (SP):** https://gtm-livetest.on-forge.com/

---

## 1. Umgebungsvariablen konfigurieren

Fügen Sie folgende Werte zur `.env` Datei von **riskmanagementv2** hinzu:

```env
# ============================================
# SSO Configuration (Service Provider)
# ============================================

# Public Key von Service 1 (pds-homepage)
# Dieser Key wird verwendet, um JWTs von Service 1 zu validieren
SSO_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAnJO2LKg6h0S0cIb90ePS
hNHhDbqzzzkKKZuN3pxNuz45O/pQICiD5AeylBLEcLwX8sYWt0066aShLFzp35Ev
R390WNQQ2AI860Ya1f1PUF3fNd6bPXzkpSwRt2K7j2th5LwK1w80UERSdF7owUDP
QuMMURFDNuDt4R2bkSTFl29bBSOS0jrXTwBBXdicXsoogs5ACUItLLexlAu50Z6P
JSNbgj7ZsZtY4Thfhl7spGneHvn9A/qkXEfpTdqQYtqAbHTFrXnCWFvHzpi6sYR3
BCy+ceQUQHtn/pAdsTf56xjOIXSz9RMK9KyfUhe3PiewKWbCPSYgO2wrv8n880Fv
NtFx40ORYWy+LJpU8rH1i4xHLXtno9E7X8UipAtCtdFEWyhEpRg0TeVaHZgW/RW+
F8JpX0RuzpVFTsi9IldDpkrQ4H5/7+fYNNUyv68dWGG2Yf9P0oNql1RZsLQvPkg8
CYSgrvmyfvc5SWONEOauvV5sgZJsszpNvFmdEARwxP52+/JOpkpfQbHjmrG9vMh3
jMcW1iBaKvqEiNJtPL1bBXf7v3BqOU9H0VV7vVKuldimcEXNn6+ZvY1SAhYc8ESH
uOvxLYgRj0Qfb3RTHtY5hqE1n8gLur6lXyZDt+rm4y19PHBuMi4Up77v7snYeSAM
p2RwOOL+7Swx8rxl8I86AC0CAwEAAQ==
-----END PUBLIC KEY-----"

# Verwende Umgebungsvariablen für Keys (empfohlen)
SSO_USE_ENV_KEYS=true
```

---

## 2. Service 1 (pds-homepage) konfigurieren

In der `.env` von **pds-homepage** müssen folgende Werte gesetzt werden:

```env
# ============================================
# SSO Configuration (Identity Provider)
# ============================================

# Service 2 URLs
SSO_SERVICE2_EXCHANGE_URL=https://gtm-livetest.on-forge.com/api/pdsauthint/exchange
SSO_SERVICE2_LOGIN_URL=https://gtm-livetest.on-forge.com/pdsauthint/login

# Verwende bestehende PASSPORT Keys
SSO_USE_ENV_KEYS=true
```

**Hinweis:** `PASSPORT_PRIVATE_KEY` und `PASSPORT_PUBLIC_KEY` sind bereits in der `.env` vorhanden und werden automatisch verwendet.

---

## 3. Composer-Pakete installieren

```bash
cd /home/dh/Code/laravel/tmp-cruisedesign/riskmanagementv2
composer require firebase/php-jwt
```

---

## 4. Migration ausführen

```bash
php artisan migrate
```

Diese Migration fügt folgende Spalten zur `customers` Tabelle hinzu:
- `agent_id`
- `service1_customer_id`
- `phone`
- `address` (JSON)
- `account_type`
- Unique constraint auf `(agent_id, service1_customer_id)`

---

## 5. Cache leeren

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

---

## 6. Routen überprüfen

```bash
php artisan route:list | grep pdsauthint
```

Erwartete Ausgabe:
```
GET|HEAD  pdsauthint/login ................. pdsauthint.login › SPController@handleLogin
POST      api/pdsauthint/exchange .......... pdsauthint.api.exchange › SPController@exchangeToken
```

---

## 7. Customer Model überprüfen

Stellen Sie sicher, dass `app/Models/Customer.php` folgende Felder als `$fillable` hat:

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
    // ... weitere Felder
];

protected $casts = [
    'address' => 'array',
];
```

---

## 8. Auth Guard konfigurieren (falls nicht vorhanden)

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

## 9. Customer Dashboard Route

Stellen Sie sicher, dass die Route `customer.dashboard` existiert.

Prüfen:
```bash
php artisan route:list | grep customer.dashboard
```

Falls nicht vorhanden, erstellen Sie diese Route in `routes/web.php`:
```php
Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])
    ->name('customer.dashboard')
    ->middleware(['auth:customer']);
```

---

## Testing

### 1. Test JWT Exchange Endpoint

```bash
# Dieser Test sollte 401 zurückgeben (kein gültiges JWT)
curl -X POST https://gtm-livetest.on-forge.com/api/pdsauthint/exchange \
  -H "Content-Type: application/json" \
  -d '{"jwt": "test"}'
```

Erwartete Antwort:
```json
{
    "error": "Invalid token",
    "message": "JWT signature validation failed"
}
```

### 2. Test Complete Flow

1. Login in Service 1: https://auth-f95c036-dot-web1-dot-dataservice-development.ey.r.appspot.com
2. Initiiere SSO (Button/Link zu `/pdsauthint/redirect`)
3. Prüfe, ob Redirect zu Service 2 funktioniert
4. Prüfe, ob Customer in Service 2 eingeloggt ist

### 3. Logs überprüfen

```bash
# In Service 2
tail -f storage/logs/laravel.log | grep SSO
```

---

## Vollständige .env Konfiguration (Service 2)

```env
# Bestehende Konfiguration...

# ============================================
# SSO Configuration (Service Provider)
# ============================================

SSO_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAnJO2LKg6h0S0cIb90ePS
hNHhDbqzzzkKKZuN3pxNuz45O/pQICiD5AeylBLEcLwX8sYWt0066aShLFzp35Ev
R390WNQQ2AI860Ya1f1PUF3fNd6bPXzkpSwRt2K7j2th5LwK1w80UERSdF7owUDP
QuMMURFDNuDt4R2bkSTFl29bBSOS0jrXTwBBXdicXsoogs5ACUItLLexlAu50Z6P
JSNbgj7ZsZtY4Thfhl7spGneHvn9A/qkXEfpTdqQYtqAbHTFrXnCWFvHzpi6sYR3
BCy+ceQUQHtn/pAdsTf56xjOIXSz9RMK9KyfUhe3PiewKWbCPSYgO2wrv8n880Fv
NtFx40ORYWy+LJpU8rH1i4xHLXtno9E7X8UipAtCtdFEWyhEpRg0TeVaHZgW/RW+
F8JpX0RuzpVFTsi9IldDpkrQ4H5/7+fYNNUyv68dWGG2Yf9P0oNql1RZsLQvPkg8
CYSgrvmyfvc5SWONEOauvV5sgZJsszpNvFmdEARwxP52+/JOpkpfQbHjmrG9vMh3
jMcW1iBaKvqEiNJtPL1bBXf7v3BqOU9H0VV7vVKuldimcEXNn6+ZvY1SAhYc8ESH
uOvxLYgRj0Qfb3RTHtY5hqE1n8gLur6lXyZDt+rm4y19PHBuMi4Up77v7snYeSAM
p2RwOOL+7Swx8rxl8I86AC0CAwEAAQ==
-----END PUBLIC KEY-----"

SSO_USE_ENV_KEYS=true
```

---

## Vollständige .env Konfiguration (Service 1)

```env
# Bestehende PASSPORT Keys bleiben unverändert...
PASSPORT_PRIVATE_KEY="..."
PASSPORT_PUBLIC_KEY="..."

# ============================================
# SSO Configuration (Identity Provider)
# ============================================

SSO_SERVICE2_EXCHANGE_URL=https://gtm-livetest.on-forge.com/api/pdsauthint/exchange
SSO_SERVICE2_LOGIN_URL=https://gtm-livetest.on-forge.com/pdsauthint/login
SSO_USE_ENV_KEYS=true
```

---

## Deployment Checklist

### Service 2 (riskmanagementv2):

- [ ] `SSO_PUBLIC_KEY` in `.env` hinzugefügt
- [ ] `SSO_USE_ENV_KEYS=true` in `.env` gesetzt
- [ ] `composer require firebase/php-jwt` ausgeführt
- [ ] Migration ausgeführt (`php artisan migrate`)
- [ ] Cache geleert (`php artisan config:clear`, etc.)
- [ ] Customer Model hat `$fillable` Felder
- [ ] `customer` Guard ist konfiguriert
- [ ] `customer.dashboard` Route existiert
- [ ] Routen überprüft (`php artisan route:list | grep pdsauthint`)

### Service 1 (pds-homepage):

- [ ] `SSO_SERVICE2_EXCHANGE_URL` in `.env` gesetzt
- [ ] `SSO_SERVICE2_LOGIN_URL` in `.env` gesetzt
- [ ] `SSO_USE_ENV_KEYS=true` in `.env` gesetzt
- [ ] `composer require firebase/php-jwt` ausgeführt (falls nicht vorhanden)
- [ ] Cache geleert
- [ ] Frontend-Integration implementiert (SSO-Button)

---

## Support

Bei Problemen:
1. Prüfen Sie die Logs: `storage/logs/laravel.log`
2. Aktivieren Sie Debug-Modus: `LOG_LEVEL=debug`
3. Siehe Dokumentation: `app/Modules/PdsAuthInt/README.md`

---

## Zusammenfassung

**Wichtigste Schritte:**

1. **Service 2:** `SSO_PUBLIC_KEY` in `.env` einfügen
2. **Service 1:** `SSO_SERVICE2_EXCHANGE_URL` und `SSO_SERVICE2_LOGIN_URL` in `.env` setzen
3. Beide: `composer require firebase/php-jwt`
4. Service 2: Migration ausführen
5. Beide: Cache leeren
6. Testen!
