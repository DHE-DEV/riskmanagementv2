# Plugin Domain API – Anleitung

## Übersicht

Die Plugin Domain API ermöglicht es Plugin-Kunden, ihre erlaubten Domains **programmatisch** zu verwalten. Domains bestimmen, welche Websites das Global Travel Monitor Plugin per iframe einbetten dürfen.

Die API unterstützt das Anlegen, Abrufen, Aktualisieren und Löschen einzelner Domains sowie **Massenoperationen** für den Import und die Löschung von bis zu 1.000 Domains pro Aufruf.

---

## Authentifizierung

Alle API-Aufrufe erfordern Ihren **Plugin-Key** als Bearer-Token im HTTP-Header:

```
Authorization: Bearer pk_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Den Key finden Sie in Ihrem Plugin-Dashboard unter `https://global-travel-monitor.eu/plugin/dashboard`.

> **Hinweis:** Der Plugin-Key ist derselbe Key, den Sie auch für die iframe-Integration verwenden.

---

## Base-URL

```
https://api.global-travel-monitor.de/v1/plugin
```

Alternativ: `https://global-travel-monitor.eu/api/v1/plugin`

---

## Rate Limit

Standardmäßig sind **120 Requests pro Minute** erlaubt. Bei Überschreitung erhalten Sie einen `429 Too Many Requests`-Response. Prüfen Sie den `Retry-After`-Header für die Wartezeit in Sekunden.

---

## Domain-Normalisierung

Domains werden beim Speichern automatisch normalisiert:

- Protokoll wird entfernt (`https://example.com` → `example.com`)
- `www.`-Prefix wird entfernt (`www.example.com` → `example.com`)
- Pfade werden entfernt (`example.com/page` → `example.com`)
- Ports werden entfernt (`example.com:8080` → `example.com`)
- Alles wird zu Kleinbuchstaben konvertiert

---

## Domain-Identifikation (UUID)

Jede Domain erhält eine eindeutige **UUID**. Alle Operationen auf einzelne Domains (Abrufen, Aktualisieren, Löschen) verwenden diese UUID als Identifikator.

---

## Endpunkte

### Domains auflisten

```
GET /v1/plugin/domains
```

Gibt eine paginierte Liste aller registrierten Domains zurück.

**Query-Parameter:**

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|-------------|
| `per_page` | integer | 50 | Einträge pro Seite (max. 200) |
| `page` | integer | 1 | Seitennummer |
| `search` | string | – | Volltextsuche im Domain-Namen |
| `is_active` | boolean | – | Filter: nur aktive (`true`) oder inaktive (`false`) Domains |

**Beispiel:**

```bash
curl -H "Authorization: Bearer pk_live_xxx" \
  "https://api.global-travel-monitor.de/v1/plugin/domains?per_page=100&search=example"
```

**Response (200):**

```json
{
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "domain": "example.com",
      "is_active": true,
      "created_at": "2026-03-15T10:30:00+00:00",
      "updated_at": "2026-03-15T10:30:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 100,
    "total": 1
  }
}
```

---

### Einzelne Domain abrufen

```
GET /v1/plugin/domains/{uuid}
```

**Beispiel:**

```bash
curl -H "Authorization: Bearer pk_live_xxx" \
  "https://api.global-travel-monitor.de/v1/plugin/domains/550e8400-e29b-41d4-a716-446655440000"
```

**Response (200):**

```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "domain": "example.com",
    "is_active": true,
    "created_at": "2026-03-15T10:30:00+00:00",
    "updated_at": "2026-03-15T10:30:00+00:00"
  }
}
```

---

### Domain hinzufügen

```
POST /v1/plugin/domains
```

**Request-Body (JSON):**

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|-------------|
| `domain` | string | Ja | Die Domain (z.B. `example.com`) |
| `is_active` | boolean | Nein | Aktiv-Status (Standard: `true`) |

**Beispiel:**

```bash
curl -X POST -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"domain": "neue-website.de"}' \
  "https://api.global-travel-monitor.de/v1/plugin/domains"
```

**Response (201):**

```json
{
  "data": {
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "domain": "neue-website.de",
    "is_active": true,
    "created_at": "2026-03-31T12:00:00+00:00",
    "updated_at": "2026-03-31T12:00:00+00:00"
  }
}
```

**Fehler (409):** Domain bereits registriert.

---

### Domains im Bulk importieren

```
POST /v1/plugin/domains/bulk
```

Importiert bis zu **1.000 Domains** in einem Aufruf. Bereits vorhandene Domains werden übersprungen, ungültige Domains werden gemeldet.

**Request-Body (JSON):**

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|-------------|
| `domains` | string[] | Ja | Array von Domain-Strings (max. 1.000) |

**Beispiel:**

```bash
curl -X POST -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "domains": [
      "website1.de",
      "website2.com",
      "app.website3.de",
      "ungültig..domain"
    ]
  }' \
  "https://api.global-travel-monitor.de/v1/plugin/domains/bulk"
```

**Response (201):**

```json
{
  "data": {
    "created": [
      {
        "uuid": "770e8400-e29b-41d4-a716-446655440002",
        "domain": "website1.de",
        "is_active": true,
        "created_at": "2026-03-31T12:00:00+00:00",
        "updated_at": "2026-03-31T12:00:00+00:00"
      },
      {
        "uuid": "770e8400-e29b-41d4-a716-446655440003",
        "domain": "website2.com",
        "is_active": true,
        "created_at": "2026-03-31T12:00:00+00:00",
        "updated_at": "2026-03-31T12:00:00+00:00"
      },
      {
        "uuid": "770e8400-e29b-41d4-a716-446655440004",
        "domain": "app.website3.de",
        "is_active": true,
        "created_at": "2026-03-31T12:00:00+00:00",
        "updated_at": "2026-03-31T12:00:00+00:00"
      }
    ],
    "skipped": [],
    "invalid": ["ungültig..domain"]
  },
  "meta": {
    "created_count": 3,
    "skipped_count": 0,
    "invalid_count": 1
  }
}
```

---

### Domain aktualisieren

```
PUT /v1/plugin/domains/{uuid}
```

Aktualisiert eine Domain. Kann zum Umbenennen oder Aktivieren/Deaktivieren verwendet werden.

**Request-Body (JSON):**

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|-------------|
| `domain` | string | Nein | Neuer Domain-Name |
| `is_active` | boolean | Nein | Aktiv-Status ändern |

**Beispiel – Domain deaktivieren:**

```bash
curl -X PUT -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"is_active": false}' \
  "https://api.global-travel-monitor.de/v1/plugin/domains/550e8400-e29b-41d4-a716-446655440000"
```

**Response (200):**

```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "domain": "example.com",
    "is_active": false,
    "created_at": "2026-03-15T10:30:00+00:00",
    "updated_at": "2026-03-31T14:00:00+00:00"
  }
}
```

---

### Domain löschen

```
DELETE /v1/plugin/domains/{uuid}
```

Löscht eine einzelne Domain. Es muss immer mindestens eine Domain verbleiben.

**Beispiel:**

```bash
curl -X DELETE -H "Authorization: Bearer pk_live_xxx" \
  "https://api.global-travel-monitor.de/v1/plugin/domains/550e8400-e29b-41d4-a716-446655440000"
```

**Response:** `204 No Content`

**Fehler (422):** Mindestens eine Domain muss verbleiben.

---

### Domains im Bulk löschen

```
DELETE /v1/plugin/domains/bulk
```

Löscht bis zu **1.000 Domains** anhand ihrer UUIDs in einem Aufruf. Es muss mindestens eine Domain verbleiben.

**Request-Body (JSON):**

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|-------------|
| `uuids` | string[] | Ja | Array von Domain-UUIDs (max. 1.000) |

**Beispiel:**

```bash
curl -X DELETE -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "uuids": [
      "550e8400-e29b-41d4-a716-446655440000",
      "660e8400-e29b-41d4-a716-446655440001"
    ]
  }' \
  "https://api.global-travel-monitor.de/v1/plugin/domains/bulk"
```

**Response (200):**

```json
{
  "data": {
    "deleted_count": 2
  }
}
```

**Fehler (422):** Mindestens eine Domain muss verbleiben.

---

## Fehlercodes

| HTTP-Code | Bedeutung | Typische Ursache |
|-----------|-----------|-----------------|
| `401` | Nicht authentifiziert | Fehlender oder ungültiger API-Key |
| `403` | Zugriff verweigert | Plugin-Konto nicht aktiv |
| `404` | Nicht gefunden | Domain-UUID existiert nicht |
| `409` | Konflikt | Domain bereits registriert |
| `422` | Validierungsfehler | Ungültiges Domain-Format oder Mindestanzahl unterschritten |
| `429` | Rate Limit | Zu viele Requests – warten und erneut versuchen |

Alle Fehler-Responses folgen dem Format:

```json
{
  "error": "Beschreibung des Fehlers.",
  "details": {}
}
```

Das `details`-Feld ist nur bei Validierungsfehlern (422) vorhanden und enthält feldspezifische Fehlermeldungen.

---

## Typische Anwendungsfälle

### Initiale Einrichtung mit vielen Domains

```bash
# Alle Domains aus einer Textdatei importieren
DOMAINS=$(cat domains.txt | jq -R -s 'split("\n") | map(select(length > 0))')

curl -X POST -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d "{\"domains\": $DOMAINS}" \
  "https://api.global-travel-monitor.de/v1/plugin/domains/bulk"
```

### Domains synchronisieren (vollständiger Abgleich)

```bash
# 1. Alle bestehenden Domains abrufen
EXISTING=$(curl -s -H "Authorization: Bearer pk_live_xxx" \
  "https://api.global-travel-monitor.de/v1/plugin/domains?per_page=200")

# 2. Nicht mehr benötigte Domains löschen
curl -X DELETE -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"uuids": ["uuid1", "uuid2"]}' \
  "https://api.global-travel-monitor.de/v1/plugin/domains/bulk"

# 3. Neue Domains importieren
curl -X POST -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"domains": ["new1.com", "new2.com"]}' \
  "https://api.global-travel-monitor.de/v1/plugin/domains/bulk"
```

### Domain vorübergehend deaktivieren

```bash
curl -X PUT -H "Authorization: Bearer pk_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{"is_active": false}' \
  "https://api.global-travel-monitor.de/v1/plugin/domains/{uuid}"
```

---

## Support & Kontakt

Bei Fragen oder Problemen:

- **Plugin-Dashboard**: https://global-travel-monitor.eu/plugin/dashboard
- **E-Mail**: support@passolution.de

---

*Letzte Aktualisierung: März 2026*
