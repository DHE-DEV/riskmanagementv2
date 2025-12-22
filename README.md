# Global Travel Monitor

Risk Management und Event Monitoring System.

## Plugin Onboarding & Widget

Das Plugin-System ermöglicht es Kunden, das Global Travel Monitor Widget auf ihren eigenen Websites einzubinden.

### Funktionsweise

**Option 1: Direkte Plugin-Registrierung (empfohlen)**
1. Interessent besucht `/plugin/register`
2. Füllt das kombinierte Formular aus (Account + Firma + Adresse + Domain)
3. Erhält sofort API-Key und Einbindecode per E-Mail
4. Wird automatisch eingeloggt und zum Dashboard weitergeleitet

**Option 2: Bestehende Kunden**
1. Kunde loggt sich ein unter `/customer/login`
2. Wird automatisch zu `/plugin/onboarding` weitergeleitet
3. Gibt Firmendaten und Domain an
4. Erhält API-Key und Einbindecode

Nach der Registrierung erhalten Kunden:
- Einen einzigartigen API-Key (Format: `pk_live_XXXXXXXXXX`)
- Den Einbindecode per E-Mail und im Dashboard

### Neue/Geänderte Dateien

#### Migrationen
- `database/migrations/2025_12_22_100000_create_plugin_clients_table.php`
- `database/migrations/2025_12_22_100001_create_plugin_keys_table.php`
- `database/migrations/2025_12_22_100002_create_plugin_domains_table.php`
- `database/migrations/2025_12_22_100003_create_plugin_usage_events_table.php`

#### Models
- `app/Models/PluginClient.php` - Haupt-Entity für Plugin-Kunden
- `app/Models/PluginKey.php` - API-Keys (deaktivierbar)
- `app/Models/PluginDomain.php` - Whitelist-Domains
- `app/Models/PluginUsageEvent.php` - Usage-Tracking

#### Controllers
- `app/Http/Controllers/Plugin/RegistrationController.php` - Kombinierte Registrierung
- `app/Http/Controllers/Plugin/OnboardingController.php` - Onboarding-Flow (bestehende Kunden)
- `app/Http/Controllers/Plugin/DashboardController.php` - Dashboard & Verwaltung
- `app/Http/Controllers/Plugin/WidgetController.php` - Widget.js Auslieferung
- `app/Http/Controllers/Api/Plugin/HandshakeController.php` - API Handshake

#### Middleware
- `app/Http/Middleware/EnsurePluginOnboarded.php` - Redirect zu Onboarding

#### FormRequests
- `app/Http/Requests/Plugin/PluginRegistrationRequest.php` - Kombinierte Registrierung
- `app/Http/Requests/Plugin/OnboardingRequest.php`
- `app/Http/Requests/Plugin/AddDomainRequest.php`

#### Mail
- `app/Mail/PluginKeyMail.php` - Willkommens-E-Mail mit Key

#### Views
- `resources/views/plugin/register.blade.php` - Kombiniertes Registrierungsformular
- `resources/views/plugin/onboarding.blade.php`
- `resources/views/plugin/dashboard.blade.php`
- `resources/views/emails/plugin-key.blade.php`

#### Config
- `config/cors.php` - CORS-Konfiguration für Widget-API

#### Tests
- `tests/Feature/Plugin/PluginOnboardingTest.php`
- `tests/Feature/Plugin/PluginHandshakeTest.php`

### Installation & Setup

```bash
# Migrationen ausführen
php artisan migrate

# Tests ausführen
php artisan test --filter=Plugin
```

### API Endpoints

#### POST /api/plugin/handshake
Widget-Lizenzprüfung und Usage-Tracking.

**Request:**
```json
{
    "key": "pk_live_XXXXXXXX",
    "domain": "example.com",
    "path": "/current-page",
    "event_type": "page_load",
    "meta": {}
}
```

**Response (Erfolg):**
```json
{
    "allowed": true,
    "config": {}
}
```

**Response (Fehler):**
```json
{
    "allowed": false,
    "error": "Domain not authorized"
}
```

#### GET /plugin/widget.js
JavaScript-Widget für die Einbindung auf Kundenwebsites.

### Widget-Einbindung

```html
<!-- Global Travel Monitor Plugin -->
<script src="https://global-travel-monitor.de/plugin/widget.js" data-key="pk_live_XXXXXXXX"></script>

<!-- Embed-Optionen: -->
<iframe src="https://global-travel-monitor.de/embed/events" width="100%" height="600"></iframe>
<iframe src="https://global-travel-monitor.de/embed/map" width="100%" height="600"></iframe>
<iframe src="https://global-travel-monitor.de/embed/dashboard" width="100%" height="800"></iframe>
```

### Dashboard-Features

- **API-Key Anzeige**: Aktueller aktiver Key
- **Key Regenerieren**: Neuen Key erstellen (alter wird deaktiviert)
- **Domain-Verwaltung**: Domains hinzufügen/entfernen
- **Nutzungsstatistik**: Aufrufe der letzten 30 Tage

### Datenmodell

```
plugin_clients
├── id
├── customer_id (FK -> customers)
├── company_name
├── contact_name
├── email
├── status (active/inactive/suspended)
└── timestamps

plugin_keys
├── id
├── plugin_client_id (FK)
├── public_key (unique, Format: pk_live_XXXXXXXX)
├── is_active
└── timestamps

plugin_domains
├── id
├── plugin_client_id (FK)
├── domain
└── timestamps

plugin_usage_events
├── id
├── plugin_client_id (FK)
├── public_key
├── domain
├── path
├── event_type
├── meta (JSON)
├── ip_hash
├── user_agent
└── created_at
```

### Security

- **Rate Limiting**: 60 Requests/Minute pro IP auf `/api/plugin/handshake`
- **IP-Hashing**: IPs werden als SHA256-Hash mit App-Key gespeichert
- **Domain-Whitelist**: Nur registrierte Domains können das Widget nutzen
- **CORS**: Konfiguriert für Cross-Origin Widget-Anfragen

### Routes

| Route | Methode | Auth | Beschreibung |
|-------|---------|------|--------------|
| `/plugin/register` | GET | Gast | Registrierungsformular |
| `/plugin/register` | POST | Gast | Registrierung absenden |
| `/plugin/onboarding` | GET | Customer | Onboarding (bestehende Kunden) |
| `/plugin/onboarding` | POST | Customer | Onboarding absenden |
| `/plugin/dashboard` | GET | Customer | Dashboard |
| `/plugin/add-domain` | POST | Customer | Domain hinzufügen |
| `/plugin/remove-domain/{id}` | DELETE | Customer | Domain entfernen |
| `/plugin/regenerate-key` | POST | Customer | Key neu generieren |
| `/plugin/widget.js` | GET | - | Widget-JavaScript |
| `/api/plugin/handshake` | POST | - | Lizenz-Handshake (Rate: 60/min) |
