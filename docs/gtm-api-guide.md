# Global Travel Monitor (GTM) API – Kundenanleitung

## Übersicht

Die GTM API bietet Zugriff auf globale Sicherheits- und Reiserisiko-Events. Sie ermöglicht die Abfrage aktueller Events gefiltert nach Priorität, Land, Event-Typ und Region sowie Länder-Übersichten mit Anzahl aktiver Events.

---

## Authentifizierung

Alle API-Aufrufe erfordern einen **Bearer-Token** im HTTP-Header:

```
Authorization: Bearer {API_TOKEN}
```

Den Token erhalten Sie von Ihrem Ansprechpartner bei Passolution.

---

## Base-URL

```
https://[domain]/api/v1/gtm
```

---

## Rate Limit

API-Anfragen unterliegen einer Ratenbegrenzung. Bei Überschreitung erhalten Sie einen `429`-Response. Prüfen Sie den `Retry-After`-Header für die Wartezeit in Sekunden.

---

## Pagination

Listen-Endpoints unterstützen Pagination über die Query-Parameter `page` und `per_page`. Pagination-Metadaten sind im `meta`-Objekt jeder Antwort enthalten.

---

## Event-Limit

Der Events-Endpoint liefert maximal **100 aktuell aktive Events**. Es werden nur Events zurückgegeben, die freigegeben (`approved`), aktiv und nicht archiviert sind, deren Startdatum in der Vergangenheit liegt und deren Enddatum entweder `null` (andauernd) oder in der Zukunft liegt.

---

## Events

### Events auflisten

```
GET /events
```

**Query-Parameter:**

| Parameter | Typ | Pflicht | Beschreibung |
|-----------|-----|---------|--------------|
| `priority` | string | Nein | Filter nach Priorität: `high`, `medium`, `low`, `info` |
| `country` | string | Nein | Filter nach Ländercode – ISO alpha-2 (z.B. `DE`) oder alpha-3 (z.B. `DEU`) |
| `event_type` | string | Nein | Filter nach Event-Typ-Code (z.B. `natural_disaster`) |
| `region` | integer | Nein | Filter nach Region-ID (numerische ID) |
| `per_page` | integer | Nein | Einträge pro Seite (Standard: 25, Maximum: 100) |
| `page` | integer | Nein | Seitennummer (Standard: 1) |

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://[domain]/api/v1/gtm/events?priority=high&country=TR&region=3&per_page=10"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Earthquake in Turkey",
      "description": "A 6.2 magnitude earthquake struck southeastern Turkey.",
      "priority": "high",
      "start_date": "2025-03-15T08:30:00Z",
      "end_date": null,
      "latitude": 37.7749,
      "longitude": 35.3214,
      "event_types": [
        {
          "code": "natural_disaster",
          "name": "Natural Disaster",
          "color": "#e74c3c",
          "icon": "earthquake"
        }
      ],
      "event_type": {
        "code": "natural_disaster",
        "name": "Natural Disaster",
        "color": "#e74c3c",
        "icon": "earthquake"
      },
      "category": {
        "id": 2,
        "name": "Naturkatastrophe",
        "color": "#e74c3c"
      },
      "countries": [
        {
          "iso_code": "TR",
          "iso3_code": "TUR",
          "name_de": "Tuerkei",
          "name_en": "Turkey",
          "continent": "Asia"
        }
      ],
      "country": {
        "iso_code": "TR",
        "iso3_code": "TUR",
        "name_de": "Tuerkei",
        "name_en": "Turkey",
        "continent": "Asia"
      },
      "created_at": "2025-03-15T09:00:00Z",
      "updated_at": "2025-03-15T10:15:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 142,
    "last_page": 15
  }
}
```

---

### Einzelnes Event anzeigen

```
GET /events/{uuid}
```

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://[domain]/api/v1/gtm/events/550e8400-e29b-41d4-a716-446655440000"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Earthquake in Turkey",
    "description": "A 6.2 magnitude earthquake struck southeastern Turkey.",
    "priority": "high",
    "start_date": "2025-03-15T08:30:00Z",
    "end_date": null,
    "latitude": 37.7749,
    "longitude": 35.3214,
    "event_types": [
      {
        "code": "natural_disaster",
        "name": "Natural Disaster",
        "color": "#e74c3c",
        "icon": "earthquake"
      }
    ],
    "event_type": {
      "code": "natural_disaster",
      "name": "Natural Disaster",
      "color": "#e74c3c",
      "icon": "earthquake"
    },
    "category": {
      "id": 2,
      "name": "Naturkatastrophe",
      "color": "#e74c3c"
    },
    "countries": [
      {
        "iso_code": "TR",
        "iso3_code": "TUR",
        "name_de": "Tuerkei",
        "name_en": "Turkey",
        "continent": "Asia"
      }
    ],
    "country": {
      "iso_code": "TR",
      "iso3_code": "TUR",
      "name_de": "Tuerkei",
      "name_en": "Turkey",
      "continent": "Asia"
    },
    "created_at": "2025-03-15T09:00:00Z",
    "updated_at": "2025-03-15T10:15:00Z"
  }
}
```

---

## Länder

### Länder mit aktiven Events auflisten

```
GET /countries
```

Gibt eine Liste aller Länder zurück, die mindestens ein aktives Event haben, zusammen mit der Anzahl aktiver Events. Sortiert nach Anzahl (absteigend). Nicht paginiert.

**Beispiel:**

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  "https://[domain]/api/v1/gtm/countries"
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "iso_code": "DE",
      "iso3_code": "DEU",
      "name_de": "Deutschland",
      "name_en": "Germany",
      "continent": "Europe",
      "continent_de": "Europa",
      "lat": 51.1657,
      "lng": 10.4515,
      "is_eu_member": true,
      "is_schengen_member": true,
      "active_events_count": 3
    },
    {
      "iso_code": "TR",
      "iso3_code": "TUR",
      "name_de": "Tuerkei",
      "name_en": "Turkey",
      "continent": "Asia",
      "continent_de": "Asien",
      "lat": 38.9637,
      "lng": 35.2433,
      "is_eu_member": false,
      "is_schengen_member": false,
      "active_events_count": 7
    }
  ]
}
```

---

## Datenmodelle

### Event

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | string (UUID) | Eindeutige ID des Events |
| `title` | string | Kurzer Titel des Events |
| `description` | string / null | Detaillierte Beschreibung |
| `priority` | string | Priorität: `high`, `medium`, `low`, `info` |
| `start_date` | datetime / null | Startdatum (ISO 8601) |
| `end_date` | datetime / null | Enddatum (null = andauernd) |
| `latitude` | number / null | Breitengrad |
| `longitude` | number / null | Längengrad |
| `event_types` | array | Liste der zugewiesenen Event-Typen |
| `event_type` | object / null | Primärer Event-Typ |
| `category` | object / null | Kategorie des Events |
| `countries` | array | Liste betroffener Länder |
| `country` | object / null | Primäres Land |
| `created_at` | datetime | Erstellungszeitpunkt |
| `updated_at` | datetime | Letzter Änderungszeitpunkt |

### Event-Typ

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `code` | string | Maschinenlesbarer Code (z.B. `natural_disaster`) |
| `name` | string | Anzeigename |
| `color` | string | Hex-Farbcode für UI |
| `icon` | string | Icon-Bezeichnung |

### Kategorie

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | integer | ID der Kategorie |
| `name` | string | Name der Kategorie |
| `color` | string | Hex-Farbcode |

### Land (Event-Kontext)

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `iso_code` | string | ISO 3166-1 alpha-2 Code (z.B. `DE`) |
| `iso3_code` | string | ISO 3166-1 alpha-3 Code (z.B. `DEU`) |
| `name_de` | string | Ländername (deutsch) |
| `name_en` | string | Ländername (englisch) |
| `continent` | string | Kontinent |

### Land (Countries-Endpoint)

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `iso_code` | string | ISO 3166-1 alpha-2 Code |
| `iso3_code` | string | ISO 3166-1 alpha-3 Code |
| `name_de` | string | Ländername (deutsch) |
| `name_en` | string | Ländername (englisch) |
| `continent` | string / null | Kontinent (englisch) |
| `continent_de` | string / null | Kontinent (deutsch) |
| `lat` | number / null | Breitengrad (Zentroid) |
| `lng` | number / null | Längengrad (Zentroid) |
| `is_eu_member` | boolean | EU-Mitglied |
| `is_schengen_member` | boolean | Schengen-Mitglied |
| `active_events_count` | integer | Anzahl aktiver Events |

---

## Fehlercodes

| HTTP-Code | Bedeutung |
|-----------|-----------|
| `200` | Erfolgreich |
| `401` | Nicht authentifiziert (Token fehlt oder ungültig) |
| `403` | Zugriff verweigert |
| `404` | Ressource nicht gefunden |
| `422` | Validierungsfehler (ungültige Filter-Parameter) |
| `429` | Rate Limit überschritten |

**Beispiel Fehler-Response:**

```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**Beispiel Validierungsfehler (422):**

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "priority": ["The selected priority is invalid."],
    "per_page": ["The per page field must be between 1 and 100."]
  }
}
```

---

## Support

Bei Fragen zur API wenden Sie sich an Ihren Ansprechpartner bei Passolution.
