# Event API – Kundenanleitung

## Übersicht

Die Event API ermöglicht es externen Partnern, Events auf dem Risk Management Dashboard abzurufen sowie — bei entsprechender Freischaltung — eigene Events zu erstellen und zu verwalten.

> **Wichtig:** Das Erstellen, Aktualisieren und Löschen von Events erfordert eine separate Freischaltung Ihres Accounts durch Passolution. Ohne diese Freischaltung können Sie die API nur zum Lesen von Events nutzen. Bei einem Versuch ohne Freischaltung erhalten Sie einen `403 Forbidden` Response.

---

## Authentifizierung

Alle API-Aufrufe erfordern einen **Bearer-Token** im HTTP-Header:

```
Authorization: Bearer {API_TOKEN}
```

Den Token erhalten Sie von Ihrem Ansprechpartner bei Passolution. Er ist 1 Jahr gültig.

---

## Base-URL

```
https://api.global-travel-monitor.eu/v1
```

Alternativ ist die API auch unter `https://global-travel-monitor.eu/api/v1` erreichbar. Wir empfehlen die Verwendung der API-Subdomain für neue Integrationen.

---

## Rate Limit

Standardmäßig sind **60 Requests pro Minute** erlaubt. Bei Überschreitung erhalten Sie einen `429 Too Many Requests` Response.

---

## Referenzdaten

Bevor Sie Events erstellen, fragen Sie die gültigen Event-Typen und Ländercodes ab.

### Event-Typen abrufen

```
GET /api/v1/event-types
```

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  https://api.global-travel-monitor.eu/v1/event-types
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "code": "earthquake",
      "name": "Erdbeben",
      "color": "#FF0000",
      "icon": "fa-house-crack"
    },
    {
      "code": "flood",
      "name": "Überschwemmung",
      "color": "#0066CC",
      "icon": "fa-water"
    }
  ]
}
```

### Länder abrufen

```
GET /api/v1/countries
```

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  https://api.global-travel-monitor.eu/v1/countries
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "iso_code": "DE",
      "iso3_code": "DEU",
      "name_de": "Deutschland",
      "name_en": "Germany"
    },
    {
      "iso_code": "TH",
      "iso3_code": "THA",
      "name_de": "Thailand",
      "name_en": "Thailand"
    }
  ]
}
```

---

## Events

### Event erstellen

> Erfordert Freischaltung der Event-Erstellung für Ihren Account.

```
POST /api/v1/events
```

**Request-Body (JSON):**

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `title` | string | Ja | Titel des Events (max. 255 Zeichen) |
| `description` | string | Nein | Beschreibung (max. 10.000 Zeichen, HTML erlaubt: p, br, strong, em, ul, ol, li, a) |
| `priority` | string | Nein | Priorität: `info`, `low`, `medium` (Standard), `high` |
| `start_date` | datetime | Ja | Startdatum (ISO 8601, z.B. `2026-02-11T08:00:00Z`) |
| `end_date` | datetime | Nein | Enddatum (muss gleich oder nach start_date liegen) |
| `event_type_codes` | array | Ja | Event-Typ-Codes (mindestens 1, aus `/event-types`) |
| `country_codes` | array | Ja | ISO-2-Ländercodes (mindestens 1, z.B. `["DE", "AT"]`) |
| `latitude` | number | Nein | Breitengrad (-90 bis 90) |
| `longitude` | number | Nein | Längengrad (-180 bis 180) |
| `tags` | array | Nein | Schlagwörter (z.B. `["flooding", "bangkok"]`) |
| `external_id` | string | Nein | Ihre interne Referenz-ID (max. 255 Zeichen) |

**Beispiel:**

```bash
curl -X POST https://api.global-travel-monitor.eu/v1/events \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Überschwemmung Bangkok",
    "description": "<p>Schwere Überschwemmungen im Großraum Bangkok.</p>",
    "priority": "high",
    "start_date": "2026-02-11T08:00:00Z",
    "end_date": "2026-02-18T08:00:00Z",
    "event_type_codes": ["flood"],
    "country_codes": ["TH"],
    "latitude": 13.7563,
    "longitude": 100.5018,
    "tags": ["flooding", "bangkok"],
    "external_id": "EXT-2026-001"
  }'
```

**Response (201 Created):**

```json
{
  "success": true,
  "message": "Event created and published successfully.",
  "data": {
    "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "title": "Überschwemmung Bangkok",
    "description": "Schwere Überschwemmungen im Großraum Bangkok.",
    "priority": "high",
    "start_date": "2026-02-11T08:00:00+00:00",
    "end_date": "2026-02-18T08:00:00+00:00",
    "latitude": 13.7563,
    "longitude": 100.5018,
    "review_status": "approved",
    "is_active": true,
    "tags": ["flooding", "bangkok"],
    "event_types": [
      {
        "code": "flood",
        "name": "Überschwemmung",
        "color": "#0066CC",
        "icon": "fa-water"
      }
    ],
    "countries": [
      {
        "iso_code": "TH",
        "name_de": "Thailand",
        "name_en": "Thailand"
      }
    ],
    "created_at": "2026-02-11T10:30:00+00:00",
    "updated_at": "2026-02-11T10:30:00+00:00"
  }
}
```

> **Hinweis:** Wenn für Ihren Account die Auto-Freigabe nicht aktiviert ist, lautet der `review_status` `pending_review` und `is_active` ist `false`. Das Event wird erst nach manueller Freigabe durch Passolution auf dem Dashboard sichtbar.

---

### Eigene Events auflisten

```
GET /api/v1/events
```

Standardmäßig werden nur **eigene Events** zurückgegeben — also Events, die über Ihren API-Token erstellt wurden. Mit dem Parameter `scope` können Sie zusätzlich **Passolution-Events** und **Events von Partner-Gruppen** abrufen.

Der `scope`-Parameter unterstützt **kommagetrennte Werte**, um mehrere Quellen gleichzeitig abzufragen.

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `scope` | string | Kommagetrennte Liste von Scope-Werten (Standard: `own`) |
| `per_page` | integer | Einträge pro Seite (Standard: 25) |
| `page` | integer | Seitennummer |

**Scope-Werte:**

| Wert | Beschreibung |
|------|--------------|
| `own` | Nur Ihre eigenen Events (Standard) |
| `passolution` | Nur von Passolution bereitgestellte Events (aktiv und freigegeben) |
| `all` | Ihre eigenen Events + Passolution-Events zusammen |
| `{gruppen-slug}` | Events der API-Kunden in der angegebenen Event-Gruppe (aktiv, freigegeben, nicht archiviert). Wenn die Gruppe `include_passolution_events` aktiviert hat, werden zusätzlich Passolution-Events mitgeliefert. |

> **Hinweis:** Partner-Events (über Gruppen) und Passolution-Events werden nur angezeigt, wenn sie aktiv, freigegeben und nicht archiviert sind.

**Beispiele:**

```bash
# Eigene Events (Standard)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.eu/v1/events?per_page=10&page=1"

# Nur Passolution-Events
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.eu/v1/events?scope=passolution"

# Alle Events (eigene + Passolution)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.eu/v1/events?scope=all"

# Eigene + Passolution (kommagetrennt, entspricht scope=all)
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.eu/v1/events?scope=own,passolution"

# Events einer Partner-Gruppe
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.eu/v1/events?scope=meine-partner-gruppe"

# Eigene Events + Partner-Gruppe kombiniert
curl -H "Authorization: Bearer {TOKEN}" \
  "https://api.global-travel-monitor.eu/v1/events?scope=own,meine-partner-gruppe"
```

---

### Einzelnes Event anzeigen

```
GET /api/v1/events/{uuid}
```

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  https://api.global-travel-monitor.eu/v1/events/a1b2c3d4-e5f6-7890-abcd-ef1234567890
```

---

### Event aktualisieren

> Erfordert Freischaltung der Event-Erstellung für Ihren Account.

```
PUT /api/v1/events/{uuid}
```

Es müssen nur die zu ändernden Felder gesendet werden.

**Beispiel:**

```bash
curl -X PUT https://api.global-travel-monitor.eu/v1/events/a1b2c3d4-e5f6-7890-abcd-ef1234567890 \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Überschwemmung Bangkok - Entwarnung",
    "priority": "low"
  }'
```

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Event updated successfully.",
  "data": { ... }
}
```

---

### Event löschen

> Erfordert Freischaltung der Event-Erstellung für Ihren Account.

```
DELETE /api/v1/events/{uuid}
```

**Beispiel:**

```bash
curl -X DELETE https://api.global-travel-monitor.eu/v1/events/a1b2c3d4-e5f6-7890-abcd-ef1234567890 \
  -H "Authorization: Bearer {TOKEN}"
```

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Event deleted successfully."
}
```

---

## Fehlercodes

| HTTP-Code | Bedeutung |
|-----------|-----------|
| `200` | Erfolgreich |
| `201` | Erfolgreich erstellt |
| `401` | Nicht authentifiziert (Token fehlt oder ungültig) |
| `403` | Zugriff verweigert (Token hat keine Berechtigung oder Account deaktiviert) |
| `404` | Event nicht gefunden |
| `422` | Validierungsfehler (ungültige Daten) |
| `429` | Rate Limit überschritten |
| `500` | Serverfehler |

**Beispiel Validierungsfehler (422):**

```json
{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."],
    "event_type_codes": ["At least one event type code is required."]
  }
}
```

---

## Review-Workflow

Je nach Konfiguration Ihres Accounts gibt es zwei Modi:

1. **Auto-Freigabe aktiviert:** Events werden sofort veröffentlicht (`review_status: approved`, `is_active: true`)
2. **Auto-Freigabe deaktiviert:** Events werden zur Prüfung eingereicht (`review_status: pending_review`, `is_active: false`) und erst nach Freigabe durch das Passolution-Team sichtbar

---

## Logo auf dem Dashboard

Wenn ein Firmenlogo in Ihrem API-Account hinterlegt ist, wird dieses als Quellen-Logo neben Ihren Events auf dem Dashboard angezeigt. Ohne Logo erscheint Ihr Firmenname als Text.

---

## Support

Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.
