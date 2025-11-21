# SSO Logging System - Admin Guide

## Übersicht

Ein umfassendes Logging-System für SSO (Single Sign-On) Authentifizierung mit detaillierter Schritt-für-Schritt Nachverfolgung und Admin-Interface zur Analyse.

---

## Features

### 🔍 **Detailliertes Logging**
- Jeder SSO-Request wird mit einer eindeutigen `request_id` verfolgt
- Alle Schritte im SSO-Flow werden protokolliert
- Performance-Tracking mit Zeitstempel für jeden Schritt (Millisekunden)
- Automatische Erfassung von: IP-Adresse, User Agent, URL, HTTP-Methode
- JWT-Payload und Token-Details
- Request/Response Daten
- Vollständige Error Stack Traces

### 📊 **Admin Interface**
- **List View**: Filterbarer Überblick aller SSO-Logs
- **Detail View**: Timeline-Visualisierung eines kompletten SSO-Flows
- **Statistics Dashboard**: Metriken, Charts und Analysen
- Responsive Design mit Tailwind CSS
- Farbcodierte Status-Indikatoren

### ⚡ **Nicht-blockierend**
- Logging-Fehler unterbrechen nicht den SSO-Flow
- Alle Logging-Operationen in Try-Catch Blöcken
- Service registriert als Singleton für Performance

---

## Zugriff auf Admin Interface

### URLs

Nach dem Deployment erreichbar unter:

```
https://stage.global-travel-monitor.eu/admin/sso-logs          # Liste aller Logs
https://stage.global-travel-monitor.eu/admin/sso-logs/stats    # Statistik Dashboard
https://stage.global-travel-monitor.eu/admin/sso-logs/{id}     # Detail View
```

### Authentifizierung

**Erforderlich:** Laravel Sanctum Authentication + Email Verification

Routes sind geschützt mit:
```php
middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
```

---

## Admin Interface Funktionen

### 1. Liste aller SSO-Logs (`/admin/sso-logs`)

**Features:**
- Tabellarische Ansicht aller SSO-Log-Einträge
- Pagination (25 Einträge pro Seite)
- Sortierung nach Datum (neueste zuerst)

**Filter:**
- **Datum:** Von/Bis Datumsbereich
- **Status:** success, error, warning, info
- **Schritt:** Bestimmter SSO-Schritt (exchange_request, jwt_validation, etc.)
- **Request ID:** Eindeutige SSO-Anfrage ID
- **Customer ID:** Spezifischer Kunde
- **Agent ID:** Spezifische Agentur
- **IP-Adresse:** Client IP

**Anzeige pro Eintrag:**
- Request ID (klickbar für Details)
- Zeitstempel
- Schritt (Step)
- Status (farbcodiert)
- Nachricht
- Kunde & Agent
- IP-Adresse
- Dauer (ms)

**Status-Farben:**
- 🟢 **Grün** = Success
- 🔴 **Rot** = Error
- 🟡 **Gelb** = Warning
- 🔵 **Blau** = Info

### 2. Detail View (`/admin/sso-logs/{request_id}`)

**Features:**
- Zeigt kompletten SSO-Flow für eine Request ID
- Timeline-Visualisierung mit farbcodierten Dots
- Verbindungslinien zwischen Schritten
- Zusammenfassung: Gesamtschritte, Dauer, Kunde, Agent

**Expandierbare Sections:**
- 🔴 **Error Details:** Fehlermeldung + Stack Trace
- 📥 **Request Data:** JSON-formatiert
- 📤 **Response Data:** JSON-formatiert
- 🔑 **JWT Payload:** Decoded Token Daten
- 📝 **Additional Data:** Extra Informationen

**Timeline Features:**
- Chronologische Anordnung
- Zeitstempel für jeden Schritt
- Status-Icon (✓ success, ✗ error, ⚠ warning, ℹ info)
- Dauer in Millisekunden
- Klickbare Sections zum Aufklappen

### 3. Statistics Dashboard (`/admin/sso-logs/stats`)

#### **Key Metrics (Kacheln)**
- 📊 **Heutige Versuche**
- 📅 **Diese Woche**
- 📆 **Dieser Monat**
- ✅ **Erfolgsrate** (%)

#### **Charts (Chart.js)**

**1. Versuche pro Stunde (Letzte 24h)**
- Liniendiagramm
- X-Achse: Stunden (00:00 - 23:00)
- Y-Achse: Anzahl Versuche
- Zeigt SSO-Traffic-Muster

**2. Status Verteilung**
- Doughnut Chart
- Anteile: Success, Error, Warning, Info
- Prozentuale Verteilung

#### **Tabellen**

**1. Top 10 Agents (nach SSO-Nutzung)**
- Agent ID
- Anzahl Versuche
- Erfolgsrate
- Letzte Nutzung

**2. Häufigste Fehler (Top 10)**
- Fehlermeldung
- Anzahl Vorkommen
- Betroffene Schritte
- Letztes Auftreten

**3. Letzte Fehler (10 neueste)**
- Zeitstempel
- Request ID (klickbar)
- Schritt
- Fehlermeldung
- Agent/Kunde

---

## Geloggte SSO-Schritte

### **exchangeToken() Flow** (Service Provider erhält JWT)

| Schritt | Name | Beschreibung |
|---------|------|--------------|
| 1 | `exchange_request` | Eingehender Request mit JWT |
| 2 | `jwt_validation` | JWT Decoding mit RS256 |
| 3 | `jwt_payload_validation` | Issuer, Audience, Expiration Checks |
| 4 | `ott_generation` | One-Time Token Generierung |
| 5 | `cache_storage` | OTT in Cache speichern |
| 6 | `response_sent` | Erfolgreiche Response mit OTT |

**Bei Fehlern:**
- Vollständige Exception Messages
- Stack Traces
- Failed Validation Details

### **handleLogin() Flow** (Kunde loggt sich mit OTT ein)

| Schritt | Name | Beschreibung |
|---------|------|--------------|
| 1 | `login_request` | Eingehender Login-Request |
| 2 | `ott_validation` | OTT aus Cache abrufen |
| 3 | `customer_lookup` | Kunde in DB suchen |
| 4 | `customer_creation` | Neuen Kunde anlegen (JIT) |
| 5 | `customer_update` | Bestehenden Kunde aktualisieren |
| 6 | `login_attempt` | Auth::guard()->login() |
| 7 | `session_creation` | Session initialisieren |
| 8 | `redirect` | Weiterleitung zum Dashboard |

**Bei Fehlern:**
- Validation Errors
- Database Errors
- Authentication Failures
- Alle mit Stack Traces

---

## Beispiel-Analysen

### **Debugging eines fehlgeschlagenen SSO-Versuchs**

1. **Gehe zu:** `/admin/sso-logs`
2. **Filter:** Status = "error"
3. **Klicke auf Request ID** der fehlgeschlagenen Anfrage
4. **Analysiere Timeline:**
   - Bei welchem Schritt ist es gescheitert?
   - Was ist die Fehlermeldung?
   - Gibt es einen Stack Trace?
5. **Prüfe Details:**
   - JWT Payload korrekt?
   - Request Data vollständig?
   - Ist der Kunde in der DB?

### **Performance-Analyse**

1. **Gehe zu:** `/admin/sso-logs`
2. **Filter:** Date Range = "Letzte 24h"
3. **Sortiere nach:** Dauer (Duration)
4. **Identifiziere langsame Requests:**
   - Welche Schritte sind langsam?
   - Gibt es Performance-Probleme?
   - Database Bottlenecks?

### **Agent-Nutzung überwachen**

1. **Gehe zu:** `/admin/sso-logs/stats`
2. **Prüfe "Top 10 Agents":**
   - Welche Agents nutzen SSO am meisten?
   - Welche Erfolgsrate haben sie?
   - Gibt es problematische Agents?

### **Fehler-Trends erkennen**

1. **Gehe zu:** `/admin/sso-logs/stats`
2. **Prüfe "Häufigste Fehler":**
   - Welche Fehler treten wiederholt auf?
   - Bei welchen Schritten?
   - Gibt es Muster?

---

## Datenbank-Schema

### Tabelle: `sso_logs`

```sql
CREATE TABLE sso_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(100) NOT NULL,
    step VARCHAR(50) NOT NULL,
    status ENUM('success', 'error', 'warning', 'info') NOT NULL,
    method VARCHAR(10) NULL,
    url TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    jwt_payload JSON NULL,
    jwt_token TEXT NULL,
    ott VARCHAR(255) NULL,
    customer_id BIGINT UNSIGNED NULL,
    agent_id VARCHAR(100) NULL,
    service1_customer_id VARCHAR(100) NULL,
    error_message TEXT NULL,
    error_trace LONGTEXT NULL,
    request_data JSON NULL,
    response_data JSON NULL,
    duration_ms INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_request_id (request_id),
    INDEX idx_step (step),
    INDEX idx_status (status),
    INDEX idx_customer_id (customer_id),
    INDEX idx_agent_id (agent_id),
    INDEX idx_created_at (created_at),
    INDEX idx_request_id_created_at (request_id, created_at),
    INDEX idx_customer_id_created_at (customer_id, created_at),
    INDEX idx_status_created_at (status, created_at),

    FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);
```

### Indizes erklärt

- **request_id**: Schnelles Abrufen aller Schritte eines SSO-Flows
- **step**: Filtern nach bestimmten Schritten
- **status**: Filtern nach Erfolg/Fehler
- **customer_id**: Alle SSO-Versuche eines Kunden
- **agent_id**: Alle SSO-Versuche einer Agentur
- **Composite Indexes**: Optimiert für häufige Filter-Kombinationen

---

## Wartung & Cleanup

### Alte Logs löschen

**Manuell:**
```bash
php artisan tinker
>>> app(App\Services\SsoLogService::class)->cleanupOldLogs(30);
```

**Automatisch (via Scheduler):**

In `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Lösche Logs älter als 90 Tage, täglich um 2 Uhr
    $schedule->call(function () {
        app(App\Services\SsoLogService::class)->cleanupOldLogs(90);
    })->dailyAt('02:00');
}
```

### Empfohlene Aufbewahrungszeiten

- **Development:** 30 Tage
- **Staging:** 60 Tage
- **Production:** 90-180 Tage (abhängig von Compliance-Anforderungen)

---

## Troubleshooting

### Problem: Keine Logs erscheinen

**Ursache:** Migration nicht ausgeführt

**Lösung:**
```bash
cd /home/forge/stage.global-travel-monitor.eu
php artisan migrate:status
php artisan migrate
```

### Problem: Admin Interface zeigt 404

**Ursache:** Routes nicht registriert oder Cache

**Lösung:**
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list | grep sso-logs
```

### Problem: Logging-Fehler brechen SSO

**Ursache:** Try-Catch fehlt (sollte nicht passieren)

**Lösung:**
- Prüfe `storage/logs/laravel.log` auf Logging-Fehler
- Vergewissere dich, dass `sso_logs` Tabelle existiert
- Prüfe Datenbankverbindung

### Problem: Charts werden nicht angezeigt

**Ursache:** Chart.js nicht geladen

**Lösung:**
```html
<!-- In stats.blade.php sollte sein: -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

---

## Best Practices

### 1. Regelmäßige Überprüfung
- Täglich Stats Dashboard prüfen
- Erfolgsrate überwachen
- Neue Fehler-Muster erkennen

### 2. Proaktives Monitoring
- Bei Erfolgsrate < 95%: Sofort untersuchen
- Bei neuen Fehlertypen: Root Cause analysieren
- Performance-Degradation frühzeitig erkennen

### 3. Datenschutz
- JWT Tokens enthalten sensitive Daten
- Logs sollten nur für autorisierte Admins zugänglich sein
- Regelmäßig alte Logs löschen
- DSGVO: Customer-Daten nach Löschung auch aus Logs entfernen

### 4. Performance
- Indizes regelmäßig prüfen: `ANALYZE TABLE sso_logs;`
- Logs in separater DB (optional für große Installationen)
- Partitionierung nach Datum erwägen bei > 1M Einträgen

---

## Zusätzliche Ressourcen

- **Usage Guide:** `SSO_LOGGING_USAGE.md`
- **SSO Config:** `SSO_CONFIGURATION.md`
- **Test Guide:** `SSO_TEST_GUIDE.md`
- **Deployment:** `DEPLOYMENT_GUIDE_SSO_FILE_KEYS.md`

---

**Erstellt am:** 2025-11-21
**Version:** 1.0
**Autor:** Claude Code
